<?php

namespace Flatgreen\Waux;

use Layered\PageMeta\UrlPreview;
use Flatgreen\Waux\Extractor\ExtractorInterface;
use Flatgreen\Waux\Extractor\HtmlMedia;

class Waux
{
    /** @var string[] $list_ie */
    private $list_ie = [];
    private UrlPreview $preview;
    private string $extractor = ''; // vraiment ?

    /**
     * @param mixed[] $cache_options
     * @param mixed[] $http_options
     */
    public function __construct(
        array $cache_options = ['cache_directory' => null, 'cache_duration' => 86000],
        array $http_options = []
    ) {
        $this->list_ie = $this->createListClassIE();
        $this->preview = new UrlPreview($cache_options, $http_options);
    }

    /**
     * From files find the list of *IE.php extractors class name
     *
     * @return string[]
     */
    private function createListClassIE()
    {
        $all_ie = glob(__DIR__ . DIRECTORY_SEPARATOR . 'Extractor' . DIRECTORY_SEPARATOR . '*IE.php');
        if ($all_ie === false) {
            return [];
        }
        foreach ($all_ie as $file) {
            $class = __NAMESPACE__ . '\\Extractor\\' . basename($file, '.php');
            $this->list_ie[] = $class;
        }
        return $this->list_ie;
    }

    public function getExtractorClass(string $url): string
    {
        $extractor = '';
        foreach ($this->list_ie as $IE_cls) {
            $search = preg_match($IE_cls::VALID_URL_REGEXP, $url);
            if ($search !== false && $search == 1) {
                $extractor = $IE_cls;
                break;
            }
        }
        return $this->extractor = $extractor;
    }

    /**
     * Use UrlPreview with local extractor (if necessary)
     *
     * @param string $url
     * @return self
     */
    public function extract(string $url): self
    {
        if (empty($this->extractor)) {
            $this->extractor = $this->getExtractorClass($url);
        }

        if (!empty($this->extractor)) {
            $extractor = $this->extractor;
            /** @var ExtractorInterface  $extractor_class*/
            $extractor_class = new $extractor();
            $this->preview->addListener('page.scrape', $extractor_class);
        } else {
            $html_media = new HtmlMedia();
            $this->preview->addListener('page.scrape', $html_media);
        }

        $this->preview->loadUrl($url);
        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getAll(): array
    {
        try {
            return $this->preview->getAll();
        } catch (\Throwable $th) {
            throw new \LogicException($th->getMessage() . "'extract' method must be use before 'getAll'");
        }
    }
}
