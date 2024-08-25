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

    public function isActive(string $slug)
    {
        $active = property_exists($this->flags, $slug);

        if (isset($this->reporter)) {
            $this->reporter->reportFlag($slug, $active, $active ? $this->flags->{$slug} : null);
        }

        return $active;
    }

    public function get(string $slug, $defaultValue = null)
    {
        $value = property_exists($this->flags, $slug) ? $this->flags->{$slug} : $defaultValue;

        if (isset($this->reporter)) {
            $this->reporter->reportFlag($slug, $active, $value, $defaultValue);
        }

        return $value;
    }

    public function getAllActiveFlags()
    {
        return $this->flags;
    }
}
