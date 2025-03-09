<?php

namespace Flatgreen\Waux;

use DateTimeImmutable;

/**
 * From '08 Juillet 2013 09:12' return the timestamp
 *
 * @param string $datetime
 * @return integer
 */
function french_datetime_to_timestamp(string $datetime): int
{
    $find = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $replace = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    $datetime = str_replace($find, $replace, strtolower($datetime));
    $date = DateTimeImmutable::createFromFormat('j M Y', $datetime, new \DateTimeZone('Europe/Paris'));
    return (($date !== false) ? $date->getTimestamp() : time());
}

/**
 * Convertion d'une durée en seconde
 *
 * @param string|null $time h:min:sec ou min:sec
 * @return integer
 */
function time_to_seconds(?string $time): int
{
    if ($time === null) {
        return 0;
    }
    $arr = explode(':', $time);
    if (count($arr) === 3) {
        return (int)$arr[0] * 3600 + (int)$arr[1] * 60 + (int)$arr[2];
    }
    return (int)$arr[0] * 60 + (int)$arr[1];
}

/**
 * Convert object to array
 *
 * @author https://stackoverflow.com/a/2476954/2686054
 * @param mixed $object
 * @return mixed
 */
function object_to_array($object)
{
    if(!is_object($object) && !is_array($object)) {
        return $object;
    }

    return array_map(__NAMESPACE__ . '\object_to_array', (array) $object);
}

function url_to_title(string $url): string
{
    $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
    $name = pathinfo(basename($path), PATHINFO_FILENAME);
    $name = str_replace(['_', '.', '-'], ' ', $name);
    return $name;
}

/**
 * from 'P0Y0M0DT0H54M38S' to second
 *
 * @param string $duration_iso
 * @return integer
 */
function duration_ISO_to_timestamp(string $duration_iso): int
{
    $duration_date = new \DateInterval($duration_iso);
    $duration = date_create('@0')->add($duration_date)->getTimestamp();
    return $duration;
}
