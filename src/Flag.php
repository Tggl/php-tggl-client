<?php

namespace Tggl\Client;

class Flag
{
    public Variation $defaultVariation;
    /** @var Condition[] */
    public array $conditions;

    public static function fromConfig($config)
    {
        $flag = new Flag();

        $flag->defaultVariation = Variation::fromConfig($config->defaultVariation);
        $flag->conditions = array_map(function ($config) {
            return Condition::fromConfig($config);
        }, $config->conditions);

        return $flag;
    }

    public function eval($context)
    {
        foreach ($this->conditions as $condition) {
            if ($condition->eval($context)) {
                return $condition->variation;
            }
        }

        return $this->defaultVariation;
    }
}