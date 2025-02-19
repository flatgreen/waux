<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;
use Symfony\Component\DomCrawler\Crawler;

use function Flatgreen\Waux\french_datetime_to_timestamp;

/**
 * Scrape R22 french version
 */
class R22IE extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'R22';
    public const VALID_URL_REGEXP = '/^https:\/\/www\.r22\.fr\/antennes\//';
    public const R22_CAPSULES_POST_URL = 'https://www.r22.fr/capsules';
    public const MAJ = '2025-02-15';

    public function __invoke(PageScrapeEvent $event): void
    {

        $current_scraped_data = $event->getData();
        $webpage_url = $current_scraped_data['page']['url'];

        $result_json = $event->getClient()->post(self::R22_CAPSULES_POST_URL, ['language' => 'fr']) ?: '';
        $result = json_decode($result_json, true);

        if (null !== $result) {
            // search items with webpage_url
            $all_songs = array_filter($result['songs'], function ($item) use ($webpage_url) {
                return $item['pageUrl'] === $webpage_url;
            });
            // only one playlist par page (if one !)
            $a_playlist = array_filter($result['playlists'], function ($item) use ($webpage_url) {
                return $item['PageUrl'] === $webpage_url;
            });
            $a_playlist = reset($a_playlist);

            // timestamp only in main page
            $songs_count = count($all_songs);
            foreach($all_songs as $k => $v) {
                $all_songs[$k]['timestamp'] = self::extractTimestamp($event->getCrawler());
            }

            // only one "songs"
            if ($songs_count == 1) {
                $all_songs = reset($all_songs);
                $event->addData('page', $this->item($webpage_url, $all_songs));
                return;
            }

            // A playlist or a collection
            if ($songs_count > 1) {
                $entries = [];
                foreach ($all_songs as $song) {
                    $entries[] = $this->item($webpage_url, $song);
                }
                $event->addData(
                    'page',
                    [
                        'web_extractor' => self::EXTRACTOR_NAME,
                        'webpage_url' => $webpage_url,
                        'thumbnail' => $a_playlist['cover_art_url'] ?? null,
                        '_type' => 'playlist',
                        'entries' => $entries
                    ]
                );
            }
        }
    }

    /**
     * Filter data from one 'songs' entry
     *
     * @param string $webpage_url
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function item(string $webpage_url, array $data)
    {
        return [
            'id' => md5($data['id']),
            'title' => $data['name'],
            'web_extractor' => self::EXTRACTOR_NAME,
            'webpage_url' => $webpage_url,
            'thumbnail' => $data['cover_art_url'],
            'url' => $data['url'],
            'playlist' => $data['antennes'] . ' - ' . $data['programme'],
            'uploader' => $data['auteurices'],
            'timestamp' => $data['timestamp']
        ];
    }

    // only french datetime
    // timestamp : <time datetime="2025-m-14">février 2025</time>
    private static function extractTimestamp(Crawler $crawler): int
    {
        try {
            $time_tag = $crawler->filter('time')->eq(0);
            $time_attr = $time_tag->attr('datetime');
            $fr_month = explode(' ', trim($time_tag->text()))[0];
            $rep = preg_match('/(\d{4})-m-(\d{2})/', $time_attr, $matches);
            $jour = $matches[2] ?? '';
            $annee = $matches[1] ?? '';
            $timestamp = french_datetime_to_timestamp($jour . ' ' . $fr_month . ' ' . $annee);
        } catch (\Throwable $th) {
        } finally {
            return $timestamp ?? time();
        }
    }
}
