<?php
/*
 * (c) flatgreen <flatgreen@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flatgreen\Waux;

use InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class HttpClient
{
    private FilesystemAdapter $cache;

    public string $html;
    /** @var string[] $http_options */
    public array $http_options;
    /** @var mixed[] $cache_options */
    public array $cache_options;

    /**
     * This class can make request GET or POST, with response in a cache
     *
     * @see https://www.php.net/manual/fr/context.http.php
     * @param mixed[] $cache_options
     * @param mixed[] $http_options
     */
    // FIXME http_options à tester
    // https://stackoverflow.com/questions/28134446/how-to-add-multiple-headers-to-file-get-content-in-php
    public function __construct(array $cache_options, array $http_options = [])
    {
        $cache_duration = $cache_options['cache_duration'] ?? 0;
        $cache_directory = $cache_options['cache_directory'] ?? null;
        $this->cache_options = ['cache_directory' => $cache_directory, 'cache_duration' => $cache_duration];
        $this->cache = new FilesystemAdapter('webmediaextract', $cache_duration, $cache_directory);

        $this->http_options = array_merge([
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ], $http_options);
    }

    /**
     * Get Html from url, cached.
     *
     * @param string $url
     */
    public function get(string $url): string
    {
        return $this->cacheRequest($url, $this->http_options);
    }

    /**
     * make a request and use the cache
     *
     * @param string $url
     * @param array<mixed> $http_options
     * @throws \Exception http error
     * @throws \InvalidArgumentException invalid url
     */
    private function cacheRequest(string $url, array $http_options): string
    {
        if (!filter_var($url, \FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('Invalid url %s', $url));
        }
        /** @var string|false $content */
        $content = $this->cache->get(md5($url . serialize($http_options)), function (ItemInterface $item) use ($url, $http_options) {
            $context = stream_context_create(['http' => $http_options]);
            $content = @file_get_contents($url, false, $context);
            if ($content === false) {
                $item->expiresAfter(10);
            }
            return $content;
        });
        if ($content === false) {
            throw new \Exception('HTTP request failed. Error: ' . error_get_last()['message']);
        }
        return $content;
    }

    /**
    * like file_get_contents with post, cached
    *
    * @param string $url
    * @param array<mixed> $query
    * @return string
    */
    public function post(string $url, array $query)
    {
        $postdata = http_build_query($query);
        $http_options_post = array_merge($this->http_options, [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        ]);
        return $this->cacheRequest($url, $http_options_post);
    }
}
