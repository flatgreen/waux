<?php

namespace Flatgreen\Waux\Extractor;

use Layered\PageMeta\Event\PageScrapeEvent;

interface ExtractorInterface
{
    // TODO in php>=8.1 we can override this constant
    // public const VALID_URL_REGEXP = '';
    public function __invoke(PageScrapeEvent $event): void;
}
