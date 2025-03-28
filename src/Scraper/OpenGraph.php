<?php

namespace Layered\PageMeta\Scraper;

use Layered\PageMeta\Event\PageScrapeEvent;

/**
 * Scrape OpenGraph data
 */
class OpenGraph
{
    /** @var string[] $pageTypes */
    public static array $pageTypes = [
        'video.other'				=>	'video',
        'instapp:photo'				=>	'photo',
        'ebay-objects:item'			=>	'product',
        'ebay-objects:ecommerce'	=>	'website',
        'medium-com:collection'		=>	'website'
    ];

    public static function scrape(PageScrapeEvent $event): void
    {
        $crawler = $event->getCrawler();
        $crawler_og = $crawler->filter('meta[property^="og:"]');

        if (count($crawler_og) > 0) {
            /** @var mixed[] $data */
            $data = [];
            $site = [
                'site_name'	=>	''
            ];
            $page = [
                'url'			=>	$crawler->getUri(),
                'type'			=>	'',
                'title'			=>	'',
                'description'	=>	'',
                'image'			=>	'',
                'video'			=>	''
            ];
            $extra = [];

            $crawler_og->each(function ($node) use (&$data) {
                $property = substr($node->attr('property'), 3);
                $content = trim($node->attr('content'));

                if (strpos($property, ':') !== false) {
                    $property = explode(':', $property, 2);

                    if (!isset($data[$property[0]])) {
                        $data[$property[0]] = [];
                    } elseif (!is_array($data[$property[0]])) {
                        $data[$property[0]] = [
                            self::guessFieldType($data[$property[0]])	=>	$data[$property[0]]
                        ];
                    }

                    $data[$property[0]][$property[1]] = $content;

                } else {
                    $data[$property] = $content;
                }
            });

            foreach ($data as $key => $value) {
                if (isset($site[$key])) {
                    $site[$key] = $value;
                } elseif (isset($page[$key])) {
                    $page[$key] = $value;
                } else {
                    $extra[$key] = $value;
                }
            }

            // rename 'site_name' to 'name'
            $site['name'] = $site['site_name'];
            unset($site['site_name']);

            $page['type'] = self::$pageTypes[$page['type']] ?? $page['type'];

            if (isset($extra['locale'])) {
                $site['language'] = current(explode('_', $extra['locale']));
            }

            // pass along the scraped info
            $event->addData('site', $site);
            $event->addData('page', $page);
            $event->addData('extra', $extra);
        }
    }

    protected static function guessFieldType(string $string): string
    {
        $type = 'text';

        if (filter_var($string, FILTER_VALIDATE_URL)) {
            $type = 'url';
        } elseif (filter_var($string, FILTER_VALIDATE_EMAIL)) {
            $type = 'email';
        }

        return $type;
    }

}
