<?php

namespace Layered\PageMeta;

use Flatgreen\Waux\HttpClient;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Layered\PageMeta\Event\PageScrapeEvent;
use Layered\PageMeta\Event\DataFilterEvent;
use Symfony\Component\DomCrawler\Crawler;

/**
 * UrlPreview
 *
 * @author Andrei Igna <andrei@laye.red>
 */
class UrlPreview
{
    private EventDispatcher $eventDispatcher;
    protected Crawler $crawler;
    protected HttpClient $client;
    /** @var mixed[] $data */
    protected array $data;
    /** @var mixed[] $cache_options */
    private array $cache_options;
    /** @var mixed[] $http_options */
    private array $http_options;

    /**
     * @param mixed[] $cache_options
     * @param mixed[] $http_options
     */
    public function __construct(array $cache_options, array $http_options)
    {
        $this->cache_options = $cache_options;
        $this->http_options = $http_options;

        $this->eventDispatcher = new EventDispatcher();
        // Scrape data from common HTML tags
        $this->addListener('page.scrape', ['\Layered\PageMeta\Scraper\OpenGraph', 'scrape']);
        $this->addListener('page.scrape', ['\Layered\PageMeta\Scraper\SimpleHtml', 'scrape']);

        // Site specific data scrape
        $this->addListener('page.scrape', ['\Layered\PageMeta\Scraper\ArticleInfo', 'scrape']);
        $this->addListener('page.scrape', ['\Layered\PageMeta\Scraper\SiteInfo', 'appLinks']);
        $this->addListener('page.scrape', ['\Layered\PageMeta\Scraper\SiteInfo', 'siteNameFromHtml']);

        // Filter data to a consistent format across sites
        $this->addListener('data.filter', ['\Layered\PageMeta\Scraper\SiteInfo', 'addSiteNames']);
        $this->addListener('data.filter', ['\Layered\PageMeta\Scraper\SiteInfo', 'mediaUrlToArray']);
        $this->addListener('data.filter', ['\Layered\PageMeta\Scraper\SiteInfo', 'relativUrlToAbsolute']);
    }

    /**
     * @param mixed[] $parameters
     */
    public function loadUrl(string $url, array $parameters = []): self
    {
        $this->data = [
            'site'		=>	[
                'url'			=>	$url,
                'name'			=>	'',
                'secure'		=>	false,
                'responsive'	=>	false,
                'author'		=>	'',
                'generator'		=>	'',
                'icon'			=>	'',
                'language'		=>	''
            ],
            'page'		=>	[
                'type'		    => 'website',
                'webpage_url'   => $url,
            ],
            'author'	=>	[],
            'app_links'	=>	[],
            'extra'		=>	[],
            'error'     =>  []
        ];

        // extract site info
        $parsedUrl = parse_url($url);
        $this->data['site']['secure'] = strpos($url, 'https://') !== false;
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $this->data['site']['url'] = (string)$parsedUrl['scheme'] . '://' . (string)$parsedUrl['host'];
        }

        // load content from URL
        try {
            $this->client = new HttpClient($this->cache_options, $this->http_options);
            $this->crawler = new Crawler($this->client->get($url), $url);

            // start scraping page
            $pageScrapeEvent = new PageScrapeEvent($this->data, $this->crawler, $this->client, $parameters);
            $this->data = $this->eventDispatcher->dispatch($pageScrapeEvent, PageScrapeEvent::NAME)->getData();

        } catch (\Throwable $th) {
            $class_trace = array_unique(array_column($th->getTrace(), 'class'));
            $listeners_scrape = array_map(
                function ($o) {
                    $o = (is_array($o)) ? $o[0] : $o;
                    return trim(strval($o), '\\');
                },
                $this->eventDispatcher->getListeners(PageScrapeEvent::NAME)
            );
            $exception_extractor = array_intersect($class_trace, $listeners_scrape);
            if (!empty($exception_extractor)) {
                $this->data['error']['extractor'] = implode(' ', $exception_extractor);
            }
            $this->data['error']['message'] = $th->getMessage();
        }

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function get(string $section): array
    {
        if (empty($this->data['error'])) {
            $dataFilterEvent = new DataFilterEvent($this->data[$section] ?? [], $section, $this->crawler);
            return $this->eventDispatcher->dispatch($dataFilterEvent, DataFilterEvent::NAME)->getData();
        } else {
            return $this->data[$section];
        }
    }

    /**
     * @return mixed[]
     */
    public function getAll(): array
    {
        return [
            'site'		=> $this->get('site'),
            'page'		=> $this->get('page'),
            'author'	=> $this->get('author'),
            'app_links'	=> $this->get('app_links'),
            'error'     => $this->get('error')
        ];
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
        return $this;
    }
}
