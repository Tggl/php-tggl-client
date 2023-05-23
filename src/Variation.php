<?php

namespace Tggl\Client;

class Variation
{
    public bool $active;
    public $value;

    public static function fromConfig($config)
    {
        $variation = new Variation();
        $variation->value = $config->active ? $config->value : null;
        $variation->active = $config->active;

        return $variation;
    }
}