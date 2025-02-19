<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;

use function Flatgreen\Waux\french_datetime_to_timestamp;

/**
 * Scrape one sound from ArteRadio
 *
 * pour une liste : https://www.arteradio.com/emission ou flux ou serie
 * le flux est dans la page : <a ... href="/xml_sound_emission?emissionname=la-chute-de-lapinville" ...
 *
 */
class ArteradioIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'ArteRadio';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.arte-?radio\.com\/son\//';
    public const MAJ = '2025-02-15';

    public function __invoke(PageScrapeEvent $event): void
    {

        $current_scraped_data = $event->getData();	// check data from other scrapers
        $crawler = $event->getCrawler();			// instance of DomCrawler Symfony Component

        // toutes les infos sont dans le seul json (en bas de la page)
        $info_json = $crawler->filter('script[type="application/json"]')->text();
        $info_json = json_decode($info_json, true);

        // pour les infos de la page :
        $info_page = $info_json['props']['pageProps']['sound'];

        // l'image pourrait être dans le json
        $thumbs = $crawler->filter('img.object-cover');
        if ($thumbs->count() > 0) {
            $thumbnail = $thumbs->eq(1)->attr('src');
        } else {
            $thumbnail = $current_scraped_data['page']['image'];
        }

        // take: last day?, first month, last year
        $date_pattern = '/(\d{0,2}\s)?(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)\s.*?(\d{2,4})/';
        $ret = preg_match($date_pattern, $info_page['credits'], $matches);
        if ($ret === 1) {
            $day = empty(trim($matches[1])) ? '1' : $matches[1];
            $month = $matches[2];
            $year = (strlen($matches[3]) == 2) ? '20'. $matches[3] : $matches[3];
            $timestamp = french_datetime_to_timestamp($day . ' ' . $month . ' ' . $year);
        } else {
            $timestamp = time();
        }

        $event->addData('page', [
            'web_extractor' => self::EXTRACTOR_NAME,
            'title' => trim($info_page['title']),
            'webpage_url' => $crawler->getUri(),
            'description' => $info_page['description'] . $info_page['credits'],
            'id' => $info_page['uuid'],
            'duration' => $info_page['durationInSeconds'],
            'url' => $info_page['mp3HifiMedia']['finalUrl'],
            'playlist' => ($info_page['containingCollection']['title']) ?? '',
            'thumbnail' => $thumbnail,
            'uploader' => implode(', ', array_column($info_page['authors'], 'name')),
            'timestamp' => $timestamp,
        ]);
    }
}
