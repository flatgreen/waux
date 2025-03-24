<?php

namespace Flatgreen\Waux\Extractor;

use Flatgreen\RFrance\RFrance;
use Layered\PageMeta\Event\PageScrapeEvent;

/**
 * Scrape R a  d i o f r a n c e with Flatgreen\RFrance,
 * one item or playlist (serie and emission)
 *
 * parameters: ['RadioFrance']['max_items'], int, default -1
 */
class RadioFranceIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'RadioFrance';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.radiofrance\.fr\//';
    // public const VALID_URL_REGEXP = '/^https:\/\/www\.radiofrance\.fr\/.+\/podcasts/';
    public const MAJ = '2025-02-16';

    public function __invoke(PageScrapeEvent $event): void
    {
        $current_scraped_data = $event->getData();
        $webpage_url = $current_scraped_data['page']['url'];
        $crawler = $event->getCrawler();
        $cache_options = $event->getClient()->cache_options;

        $max_item = ($event->getParameters()[self::EXTRACTOR_NAME]['max_items']) ?? -1;

        $FC = new RFrance($cache_options['cache_directory'], $cache_options['cache_duration']);
        $FC->setCrawler($crawler);
        $ret = $FC->extract($webpage_url, $max_item);

        if (!empty($FC->error) || $ret === false) {
            throw new \Exception($FC->error);
        } else {
            $data = json_decode($FC->toInfoJson(), true);
            $data['web_extractor'] = self::EXTRACTOR_NAME;
            $event->addData('page', $data);
        }
    }
}
