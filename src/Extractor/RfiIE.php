<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;

use function Flatgreen\Waux\duration_ISO_to_timestamp;

/**
 * Only one item for rfi.fr
 */
class RfiIE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'Rfi';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.rfi\.fr\/fr\/podcasts\//';
    public const MAJ = '2025-03-9';

    public function __invoke(PageScrapeEvent $event): void
    {
        $current_scraped_data = $event->getData();
        $webpage_url = $current_scraped_data['page']['url'];

        // info dans <script type="application/ld+json">
        $infos = json_decode($event->getCrawler()->filter('script[type="application/ld+json"]')->text(''), true);
        if (!empty($infos)) {
            $infos = $infos[0];
            $event->addData(
                'page',
                [
                    'id' => md5($webpage_url),
                    'url' => $infos['audio']['contentUrl'],
                    'timestamp' => strtotime($infos['audio']['uploadDate']),
                    'thumbnail' => $infos['audio']['thumbnailUrl'],
                    'duration' => duration_ISO_to_timestamp($infos['audio']['duration']),
                    'web_extractor' => self::EXTRACTOR_NAME,
                    'webpage_url' => $webpage_url
            ]
            );
        }
    }
}
