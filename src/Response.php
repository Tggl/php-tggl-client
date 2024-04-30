<?php

namespace Tggl\Client;

class Response
{
    protected array $flags = [];

    public function __construct(array $flags = [])
    {
        $this->flags = $flags;
    }

    public function isActive(string $slug): bool
    {
        return array_key_exists($slug, $this->flags);
    }

    public function get(string $slug, $defaultValue = null)
    {
        return array_key_exists($slug, $this->flags) ? $this->flags[$slug] : $defaultValue;
    }

    public function getAllActiveFlags()
    {
        return $this->flags;
    }
}