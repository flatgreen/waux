<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

use function Flatgreen\Waux\url_to_title;

/**
 * Scarpe html page with audio|video html tag
 */
class HtmlMedia extends ExtractorAbstract implements ExtractorInterface
{
    public const EXTRACTOR_NAME = 'HtmlMedia';
    public const MAJ = "2025-02-16";

    private string $webpage_url;

    public function __invoke(PageScrapeEvent $event): void
    {

        $crawler = $event->getCrawler();
        $current_scraped_data = $event->getData();
        $this->webpage_url = $current_scraped_data['page']['url'];
        $title = ($current_scraped_data['page']['title']) ?? '';
        $thumbnail = ($current_scraped_data['page']['image']) ?? ($current_scraped_data['site']['icon']) ?? null;

        $all_media = [];
        foreach(['audio', 'video'] as $media_type) {
            $crawler_media = $crawler->filter($media_type);
            if ($crawler_media->count() > 0) {
                // for one media type, one array
                $media = $crawler_media->each(function (Crawler $node, $i) use ($thumbnail) {
                    $thumbnail = $node->attr('poster') ?? $thumbnail;
                    return [
                        'url' => $this->getSrcUrl($node),
                        'thumbnail' => $thumbnail
                    ];
                });
                $all_media = array_merge($all_media, $media);
            }
        }

        if (empty($all_media)) {
            return;
        }
        if (count($all_media) == 1) {
            $event->addData('page', array_merge($this->finalEntry($all_media[0]), ['title' => $title]));
        } else {
            foreach($all_media as $media) {
                $entries[] = $this->finalEntry($media);
            }
            $event->addData(
                'page',
                [
                    'web_extractor' => self::EXTRACTOR_NAME,
                    'webpage_url' => $this->webpage_url,
                    'thumbnail' => $thumbnail ?? null,
                    '_type' => 'playlist',
                    'entries' => $entries
                ]
            );
        }
    }

    /**
     * @param mixed[] $one_media
     * @return mixed[]
     */
    private function finalEntry(array $one_media): array
    {
        return [
            'title' => url_to_title($one_media['url']),
            'web_extractor' => self::EXTRACTOR_NAME,
            'webpage_url' => $this->webpage_url,
            'thumbnail' => $one_media['thumbnail'],
            'url' => $one_media['url'],
        ];
    }

    private function getSrcUrl(Crawler $crawler_media): string|null
    {
        // search 'src' attribute, first in 'source' else in 'audio|video' tag
        $source_tags = $crawler_media->filter('source');
        if ($source_tags->count() > 0) {
            $media_url = $source_tags->attr('src');
        } else {
            $media_url = $crawler_media->attr('src');
        }
        return (isset($media_url)) ? UriResolver::resolve($media_url, $this->webpage_url) : null;
    }
}
