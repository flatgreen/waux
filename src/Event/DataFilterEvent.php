<?php

namespace Layered\PageMeta\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Dispatched each time data is retuned
 */
class DataFilterEvent extends Event
{
    public const NAME = 'data.filter';

    /** @var mixed[] $data */
    protected array $data;
    protected string $section;
    protected Crawler $crawler;

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data, string $section, Crawler $crawler)
    {
        $this->data = $data;
        $this->section = $section;
        $this->crawler = $crawler;
    }

    /**
     * @param mixed[] $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param mixed[] $data
     */
    public function addData(array $data): void
    {
        $this->data = array_merge($this->data, array_filter($data));
    }

    /**
     * @return mixed[] $data
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getSection(): string
    {
        return $this->section;
    }

    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

}
