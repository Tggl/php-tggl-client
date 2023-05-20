<?php

namespace Tggl\Client;

class Condition
{
    /** @var Rule[] */
    public array $rules;
    public Variation $variation;

    public static function fromConfig($config)
    {
        $condition = new Condition();

        $condition->variation = Variation::fromConfig($config->variation);
        $condition->rules = array_map(function ($config) {
            return Rule::fromConfig($config);
        }, $config->rules);

        return $condition;
    }

    public function eval($context): bool
    {
        foreach ($this->rules as $rule) {
            if (!$rule->eval($context)) {
                return false;
            }
        }

        return true;
    }
}