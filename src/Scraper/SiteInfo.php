<?php

namespace Layered\PageMeta\Scraper;

use Layered\PageMeta\Event\DataFilterEvent;
use Layered\PageMeta\Event\PageScrapeEvent;
use Symfony\Component\DomCrawler\UriResolver;

/**
 * Make data consistent across sites
 */
class SiteInfo
{
    /** @var string[] $siteNames */
    public static array $siteNames = [
        'nytimes.com'	=>	'NYTimes',
        'ebay.com'		=>	'eBay',
        'ebay.es'		=>	'eBay',
        'ebay.co.uk'	=>	'eBay',
        'amazon.com'	=>	'Amazon',
        'amazon.ca'		=>	'Amazon',
        'amazon.co.uk'	=>	'Amazon',
        'amazon.es'		=>	'Amazon',
        'amazon.de'		=>	'Amazon',
        'amazon.fr'		=>	'Amazon',
        'amazon.it'		=>	'Amazon',
        'facebook.com'	=>	'Facebook',
        'netflix.com'	=>	'Netflix',
        'dribbble.com'	=>	'Dribbble',
        'medium.com'	=>	'Medium',
        'twitter.com'	=>	'Twitter',
        'youtube.com'	=>	'YouTube',
        'instagram.com'	=>	'Instagram',
        'trello.com'	=>	'Trello'
    ];

    public static function addSiteNames(DataFilterEvent $event): void
    {
        $data = $event->getData();

        if ($event->getSection() == 'site' && !$data['name']) {
            $host = str_replace('www.', '', (string)parse_url($data['url'], PHP_URL_HOST));
            if (isset(self::$siteNames[$host])) {
                $data['name'] = self::$siteNames[$host];
                $event->setData($data);
            }
        }
    }

    public static function siteNameFromHtml(PageScrapeEvent $event): void
    {
        $crawler = $event->getCrawler();
        $data = $event->getData();
        $parsedUrl = parse_url($crawler->getUri() ?? '');

        if (empty($data['site']['name']) && !isset($parsedUrl['query']) && !isset($parsedUrl['path'])) {
            $event->addData('site', [
                'name'	=>	$data['page']['title']
            ]);
        }
    }

    public static function mediaUrlToArray(DataFilterEvent $event): void
    {
        $data = $event->getData();

        foreach (['image', 'video'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = [
                    'url'	=>	UriResolver::resolve($data[$field], $event->getCrawler()->getUri())
                ];
                $event->setData($data);
            }
        }
    }


    public static function relativUrlToAbsolute(DataFilterEvent $event): void
    {
        $data = $event->getData();
        foreach (['thumbnail', 'icon'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = UriResolver::resolve($data[$field], $event->getCrawler()->getUri());
                $event->setData($data);
            }
            if (isset($data['_type']) && $data['_type'] == 'playlist') {
                foreach ($data['entries'] as $k => $v) {
                    if (isset($data['entries'][$k][$field])) {
                        $data['entries'][$k][$field] = UriResolver::resolve($data['entries'][$k][$field], $event->getCrawler()->getUri());
                    }
                }
                $event->setData($data);
            }
        }
    }

    public static function appLinks(PageScrapeEvent $event): void
    {
        $crawler = $event->getCrawler();
        /** @var mixed[] $appLinks */
        $appLinks = [];

        $crawler->filter('meta[property^="al:"]')->each(function ($node) use (&$appLinks) {
            $property = substr($node->attr('property'), 3);
            $content = trim($node->attr('content'));

            if (strpos($property, ':') !== false) {
                $property = explode(':', $property, 2);

                if (!isset($appLinks[$property[0]])) {
                    $appLinks[$property[0]] = [];
                }

                $appLinks[$property[0]][$property[1]] = $content;
            } else {
                $appLinks[$property] = $content;
            }
        });

        foreach ($appLinks as $platform => $value) {
            if (in_array($platform, ['ios', 'ipad', 'iphone'])) {
                $appLinks[$platform]['store_url'] = 'https://itunes.apple.com/us/app/' . $appLinks[$platform]['app_name'] . '/id' . $appLinks[$platform]['app_store_id'];
            }
            if ($platform === 'android') {
                $appLinks['android']['store_url'] = 'https://play.google.com/store/apps/details?id=' . $appLinks['android']['package'];
            }
        }

        $event->setData('app_links', $appLinks);
    }

}
