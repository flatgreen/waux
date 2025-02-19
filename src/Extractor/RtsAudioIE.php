<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;

/**
 * Scrape rts.ch audio
 *
 * NO not use : user_agent with 'compatible; Googlebot/2.1; +http://www.google.com/bot.html'
 */
class RtsAudioIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'RtsAudio';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.rts\.ch\/audio-podcast\/\d{4}\/audio\//';
    public const MAJ = '2025-02-17';

    // pour le moment, un épisode seul, d'où '/.../audio/':
    // https://www.rts.ch/audio-podcast/2024/audio/soleil-noir-les-massacres-ep1-28613859.html
    // les émissions sont en '/.../emission':
    // https://www.rts.ch/audio-podcast/2017/emission/chouette-25001368.html
    // à explorer pour une playlist : (bof)
    // https://www.rts.ch/hbv7/ajax/emissions/25001368/audios
    // https://www.rts.ch/hbv7/ajax/emissions/25001368/audios&offset=6
    // il y a aussi des 'thematiques' qui est plus un portail d'émissions

    public function __invoke(PageScrapeEvent $event): void
    {
        $current_scraped_data = $event->getData();
        $crawler = $event->getCrawler();

        $webpage_url = $current_scraped_data['page']['url'];

        // <meta name="rts:content:legacy-id" content="15112045">
        $legacy_id = $crawler->filter('meta[name="rts:content:legacy-id"]')->attr('content');

        $urn_url = 'https://il.srgssr.ch/integrationlayer/2.0/mediaComposition/byUrn/urn:rts:audio:' . $legacy_id . '.json';
        $urn_json = $event->getClient()->get($urn_url);

        // intérêt dans ['chapterList'][0]
        $json = json_decode($urn_json, true);
        $json = $json['chapterList'][0];

        $data = [
            'web_extractor' => self::EXTRACTOR_NAME,
            'id' => $json['id'],
            'title' => $json['title'],
            'description' => $json['description'],
            'webpage_url' => $webpage_url,
            'thumbnail' => $json['imageUrl'],
            'timestamp' => strtotime($json['date']), //"date" => "2024-08-30T00:00:00+02:00"
            'duration' => (int)($json['duration'] / 1000), //2025000,
            'url' => $json['podcastHdUrl'] ?? $json['resourceList'][0]['url'],
            'mimetype' => $json['resourceList'][0]['mimeType'],
            'ext' => strtolower($json['resourceList'][0]['encoding']), // audioCodec ?
        ];
        $event->addData('page', $data);
    }
}
