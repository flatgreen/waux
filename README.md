# Waux - Web AUdio eXtractor

Try to extract media (audio) information from the web !

## Prerequisites
- php >= 8.1

## Installation
- Use the package manager [composer](https://getcomposer.org/) to install Waux.
```bash
composer require flatgreen/waux
```
- Optional: Create a 'cache' directory (with read|write permissions), by default the cache directory is inside the system temporary directory.

## Usage

```php
require_once 'vendor/autoload.php';
use Flatgreen\Waux\Waux;

const CACHE_DURATION = 345600;
const CACHE_DIRECTORY = 'cache';

$cache_options = ['cache_directory' => CACHE_DIRECTORY, 'cache_duration' => CACHE_DURATION];
$http_options = ['header' => 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/124.0'];

$waux = new Waux($cache_options, $http_options);

$url = '...';
var_dump($waux->getExtractorClass($url));
var_dump($waux->extract($url)->getAll());
```

### Extractors
For the moment, just some [web extractors](/src/Extractor/).

## Changelog
[changelog](/CHANGELOG.md)

## License
Waux is licensed under the MIT License (MIT). Please see the [license file](/LICENSE) for more information.