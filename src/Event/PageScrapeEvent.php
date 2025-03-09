<?php

namespace Layered\PageMeta\Event;

use Flatgreen\Waux\HttpClient;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Dispatched each time a page scrape is started
 */
class PageScrapeEvent extends Event
{
    public const NAME = 'page.scrape';

    /** @var mixed[] $data */
    protected array $data;
    protected Crawler $crawler;
    protected HttpClient $client;
    /** @var mixed[] $parameters */
    protected array $parameters;

    /**
     * @param mixed[] $data
     * @param mixed[] $parameters
     */
    public function __construct(array $data, Crawler $crawler, HttpClient $client, array $parameters)
    {
        $this->data = $data;
        $this->crawler = $crawler;
        $this->client = $client;
        $this->parameters = $parameters;
    }

    /**
     * @param mixed[] $data
     */
    public function setData(string $section, array $data): void
    {
        $this->data[$section] = $data;
    }

    /**
     * @param mixed[] $data
     */
    public function addData(string $section, array $data): void
    {
        $this->data[$section] = array_merge($this->data[$section], array_filter($data));
    }

    /**
     * @return mixed[] $data
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    public function getClient(): HttpClient
    {
        return $this->client;
    }

    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

}
