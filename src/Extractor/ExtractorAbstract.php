<?php

namespace Flatgreen\Waux\Extractor;

abstract class ExtractorAbstract
{
    public function __toString()
    {
        return $this::class;
    }
}
