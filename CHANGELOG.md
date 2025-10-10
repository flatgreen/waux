# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),

## [Unreleased]
## [1.4] 2025-10-12
### Added
- new extractor for audioblog.arteradiocom one item only
### Changed
- RadioFrance extractor with min. flatgreen/rfrance 2.5

## [1.3.3] 2025-06-08
### Fixed
- better R22 extractor

## [1.3.2] 2025-03-24
### Fixed
- fix RF extractor with type NewsArticle

## [1.3.1] 2025-03-14
### Fixed
- fix HtmlMedia thumbnail extractor

## [1.3] 2025-03-9
### Added
- Extractor for french site rfi.fr
- method Waux::setParameters(array) to pass extra parameters to an extractor. Must be define before extract().
Ex: ->setParameters(['RadioFrance' => ['max_items' => 10]])->extract($url)
- id for htmlMedia entrie

## [1.2] - 2025-02-20
### Fixed
- fix class path in composer.json
### Added
- more usefull user_agent by default with request

## [1.1] - 2025-02-19
## [1.0] - 2025-02-19
### Added
- First release
