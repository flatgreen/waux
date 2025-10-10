<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;

/**
 * Scrape the audioblog from arteradio at
 * https://audioblog.arteradio.com/
 * 
 * Only one item
 */
class AudioblogArteRadioIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'AudioblogArteRadio';
    // https://audioblog.arteradio.com/blog/256436/podcast/259136/boucan-d-enfer-en-l-eglise-de-marvejols
    public const VALID_URL_REGEXP = '/^https:\/\/audioblog.arteradio.com\/blog\/\d+\/podcast\//';
    public const MAJ = '2025-10-09';
    public const AUDIOBLOG_BACK_NODE = 'https://back-audioblog.arteradio.com/node/';
    public const AUDIOBLOG_BLOG = 'https://audioblog.arteradio.com/blog/';

    public function __invoke(PageScrapeEvent $event): void
    {
        $current_scraped_data = $event->getData();
        $webpage_url = $current_scraped_data['page']['url'];

        preg_match('/https:\/\/audioblog.arteradio.com\/blog\/\d+\/podcast\/(\d+)\//', $webpage_url, $matches);
        $id = $matches[1];

        $info_url = self::AUDIOBLOG_BACK_NODE . $id . '?_format=json';
        $info_json = $event->getClient()->get($info_url);
        $json = json_decode($info_json, true);

        $data = [
            'web_extractor' => self::EXTRACTOR_NAME,
            'id' => $id,
            'title' => trim($json['title']),
            'description' => trim($json['presentation']),
            'webpage_url' => $webpage_url,
            'thumbnail' => $json['image_akamai'],
            'timestamp' => strtotime($json['created']),
            'duration' => (int)$json['duration'],
            'url' => $json['file_url'],
            'playlist' => $json['blog']['title'],
            'uploader' => $json['blog']['title'],
            'playlist_url' => self::AUDIOBLOG_BLOG . $json['blog']['id'],
        ];
        $event->addData('page', $data);
    }
}