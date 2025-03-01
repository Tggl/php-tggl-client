<?php

namespace Tggl\Client;

class TgglResponse
{
    protected $flags;
    protected $reporter;

    public function __construct($flags, $reporter = null)
    {
        $this->flags = $flags;
        $this->reporter = $reporter;
    }

    public function get(string $slug, $defaultValue)
    {
        $active = property_exists($this->flags, $slug);
        $value = $active ? $this->flags->{$slug} : $defaultValue;

        if (isset($this->reporter)) {
            $this->reporter->reportFlag($slug, $value, $defaultValue);
        }

        return $value;
    }

    public function getAllActiveFlags()
    {
        return $this->flags;
    }
}
