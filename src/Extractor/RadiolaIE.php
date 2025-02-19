<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;
use Symfony\Component\DomCrawler\Crawler;

use function Flatgreen\Waux\time_to_seconds;

/**
 * Scrape radiola.be, one item ou in serie or collections
 */
class RadiolaIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'Radiola';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.radiola\.be\/(productions|serie|collections)\//';
    public const MAJ = '20215-02-16';

    public function __invoke(PageScrapeEvent $event): void
    {
        $current_scraped_data = $event->getData();
        $crawler = $event->getCrawler();

        $webpage_url = $current_scraped_data['page']['url'];

        // Les informations autres que celles dans les 'button' sont mises dans $extra.
        $extra = [];

        // la 'description' est seulement donnée sur la page, un épisode ou série
        // parfois, il y a d'autres descriptions différentes dans les pages des épisodes...
        // pour 'collections' c'est dans #descriptionpod (pas fait)
        $amorce = $crawler->filter('#amorce')->text('');
        $description_p = $crawler->filter('#contenu p')->each(
            function (Crawler $crawler, $i) {
                return $crawler->text();
            }
        );
        $description = '<p>' . $amorce . '</p><p>' . implode('</p><p>', $description_p) . '</p>';
        $extra['description'] = $description;

        // Pour le timestamp pour la page scrapée (pour une série, il faudrait parser chaque page des épisodes ...)
        $app_json = json_decode($crawler->filter('script[type="application/ld+json"]')->eq(0)->text(''), true);
        $app_json = $app_json['@graph'];
        $date_published = (array_column($app_json, 'datePublished')[0]) ?? date(DATE_RSS);
        $extra['timestamp'] = strtotime($date_published);

        // un épisode ou une serie|collections ?
        $ret = preg_match(self::VALID_URL_REGEXP, $webpage_url, $type);
        if ($ret === false || $ret === 0) {
            return;
        }
        // $type p ê :
        // - production : un item
        // - serie | collection : une playlist
        // - autre ('podcast') pas pris en charge (par la regexp)
        if ($type[1] === 'productions') {
            $first_button = $crawler->filter('button')->eq(0);
            // est dans une serie ?
            $extra['playlist'] = $crawler->filter('#serie > span')->text('');
            $event->addData('page', $this->extractFromOneButton($first_button, $extra));
        } else {
            // serie|collections
            $all_buttons = $crawler->filter('#listresultat')->eq(0)->filter('button[id]');
            $extra['playlist'] = ($type[1] == 'serie') ? $crawler->filter('h1.h1big')->text() : $crawler->filter('h1.collection-titre')->text();
            $entries = $all_buttons->each(
                function (Crawler $crawler, $i) use ($extra) {
                    return $this->extractFromOneButton($crawler, $extra);
                }
            );
            $event->addData(
                'page',
                [
                    'web_extractor' => self::EXTRACTOR_NAME,
                    'webpage_url' => $webpage_url,
                    '_type' => 'playlist',
                    'entries' => $entries
                ]
            );
        }
    }

    /**
     * Extract info form the 'data-audio-*' in an audio button
     *
     * @param Crawler $crawler
     * @param mixed[] $extra
     * @return mixed[]
     */
    private function extractFromOneButton(Crawler $crawler, array $extra): array
    {
        $data = [
            'id' => md5($crawler->attr('data-audio-id') ?? date(DATE_RFC822)),
            'url' => $crawler->attr('data-audio-url'),
            'uploader' => $crawler->attr('data-audio-auteurices'),
            'duration' => time_to_seconds($crawler->attr('data-audio-duree')),
            'title' => $crawler->attr('data-audio-titre'),
            'webpage_url' => $crawler->attr('data-audio-link'),
            'web_extractor' => self::EXTRACTOR_NAME,
            'thumbnail' => $crawler->attr('data-audio-image')
        ];
        return array_merge($data, $extra);
    }
}
