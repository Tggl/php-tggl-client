<?php

namespace Tggl\Client;

class TgglResponse
{
    protected $flags;

    public function __construct($flags)
    {
        $this->flags = $flags;
    }

    public function isActive(string $slug)
    {
        return property_exists($this->flags, $slug);
    }

    public function get(string $slug, $defaultValue = null)
    {
        return property_exists($this->flags, $slug) ? $this->flags->{$slug} : $defaultValue;
    }
}