<?php

namespace Layered\PageMeta\Scraper;

use Layered\PageMeta\Event\PageScrapeEvent;

// TODO ne semble pas utilisé
/**
 * Check page markup for tracking snippets
 */
class Tracking
{
    public static function checkTrackers(PageScrapeEvent $event): void
    {
        $crawler = $event->getCrawler();

        if (count($crawler->filter('title'))) {
            $html = $crawler->html();
            $site = [
                'tracking'	=>	[]
            ];

            // Check for Google Analytics code
            if (stripos($html, 'google-analytics') !== false) {
                $site['tracking'][] = 'Google Analytics';
            }

            // Check for Piwik
            if (stripos($html, 'piwik') !== false) {
                $site['tracking'][] = 'Piwik';
            }

            // pass along the scraped info
            $event->addData('site', $site);
        }
    }

}
