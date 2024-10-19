<?php

namespace Tggl\Client;

use Exception;
use exussum12\xxhash\V32;

function array_some(array $array, callable $fn)
{
    foreach ($array as $value) {
        if ($fn($value)) {
            return true;
        }
    }
    return false;
}

class Rule
{
    public string $key;
    public string $operator;
    public bool $negate;
    public float $rangeStart;
    public float $rangeEnd;
    public int $seed;
    /** @var string[] */
    public array $values;
    /** @var string | float */
    public $value;
    /** @var int[] */
    public array $version;
    public int $timestamp;
    public string $iso;

    public static function fromConfig($config)
    {
        $rule = new Rule();

        $rule->key = $config->key;
        $rule->operator = $config->operator;

        if (property_exists($config, 'negate')) {
            $rule->negate = $config->negate;
        }

        if (property_exists($config, 'rangeStart')) {
            $rule->rangeStart = $config->rangeStart;
        }

        if (property_exists($config, 'rangeEnd')) {
            $rule->rangeEnd = $config->rangeEnd;
        }

        if (property_exists($config, 'seed')) {
            $rule->seed = $config->seed;
        }

        if (property_exists($config, 'values')) {
            $rule->values = $config->values;
        }

        if (property_exists($config, 'value')) {
            $rule->value = $config->value;
        }

        if (property_exists($config, 'version')) {
            $rule->version = $config->version;
        }

        if (property_exists($config, 'timestamp')) {
            $rule->timestamp = $config->timestamp;
        }

        if (property_exists($config, 'iso')) {
            $rule->iso = $config->iso;
        }

        return $rule;
    }

    public function eval($context): bool
    {
        $value = property_exists($context, $this->key) ? $context->{$this->key} : null;

        if ($this->operator === Operator::Empty) {
            $isEmpty = $value === null || $value === '';
            return $isEmpty !== $this->negate;
        }

        if ($value === null) {
            return false;
        }

        if ($this->operator === Operator::StrEqual) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return in_array($value, $this->values, true) !== $this->negate;
        }

        if ($this->operator === Operator::StrEqualSoft) {
            if (gettype($value) !== 'string' && gettype($value) !== 'integer' && gettype($value) !== 'double') {
                return false;
            }
            return in_array(strtolower(strval($value)), $this->values, true) !== $this->negate;
        }

        if ($this->operator === Operator::StrContains) {
            if (gettype($value) !== 'string') {
                return false;
            }

            return array_some($this->values, function ($val) use ($value) {
                    return strpos($value, $val) !== false;
                }) !== $this->negate;
        }


        if ($this->operator === Operator::StrStartsWith) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return array_some($this->values, function ($val) use ($value) {
                    return substr($value, 0, strlen($val)) === $val;
                }) !== $this->negate;
        }

        if ($this->operator === Operator::StrEndsWith) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return array_some($this->values, function ($val) use ($value) {
                    return substr($value, -strlen($val)) === $val;
                }) !== $this->negate;
        }

        if ($this->operator === Operator::StrAfter) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return ($value >= $this->value) !== ($this->negate ?? false);
        }

        if ($this->operator === Operator::StrBefore) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return ($value <= $this->value) !== ($this->negate ?? false);
        }


        if ($this->operator === Operator::RegExp) {
            if (gettype($value) !== 'string') {
                return false;
            }
            return ((bool)preg_match('/' . $this->value . '/', $value)) !== $this->negate;
        }

        if ($this->operator === Operator::True) {
            return $value === !$this->negate;
        }

        if ($this->operator === Operator::Eq) {
            if (gettype($value) !== 'integer' && gettype($value) !== 'double') {
                return false;
            }
            return ($value === $this->value) !== $this->negate;
        }

        if ($this->operator === Operator::Lt) {
            if (gettype($value) !== 'integer' && gettype($value) !== 'double') {
                return false;
            }
            return ($value < $this->value) !== $this->negate;
        }

        if ($this->operator === Operator::Gt) {
            if (gettype($value) !== 'integer' && gettype($value) !== 'double') {
                return false;
            }
            return ($value > $this->value) !== $this->negate;
        }

        if ($this->operator === Operator::ArrOverlap) {
            if (gettype($value) !== 'array') {
                return false;
            }

            return array_some($value, function ($val) {
                    return in_array($val, $this->values, true);
                }) !== $this->negate;
        }

        if ($this->operator === Operator::DateAfter) {
            if (gettype($value) === 'string') {
                $val =
                    substr($value, 0, strlen('2000-01-01T23:59:59')) .
                    substr('2000-01-01T23:59:59', strlen($value));
                return ($this->iso <= $val) !== ($this->negate ?? false);
            }

            if (gettype($value) === 'integer' || gettype($value) === 'double') {
                if ($value < 631152000000) {
                    return ($value * 1000 >= $this->timestamp) !== ($this->negate ?? false);
                }

                return ($value >= $this->timestamp) !== ($this->negate ?? false);
            }

            return false;
        }

        if ($this->operator === Operator::DateBefore) {
            if (gettype($value) === 'string') {
                $val =
                    substr($value, 0, strlen('2000-01-01T00:00:00')) .
                    substr('2000-01-01T00:00:00', strlen($value));
                return ($this->iso >= $val) !== ($this->negate ?? false);
            }

            if (gettype($value) === 'integer' || gettype($value) === 'double') {
                if ($value < 631152000000) {
                    return ($value * 1000 <= $this->timestamp) !== ($this->negate ?? false);
                }

                return ($value <= $this->timestamp) !== ($this->negate ?? false);
            }

            return false;
        }

        if ($this->operator === Operator::SemverEq) {
            if (gettype($value) !== 'string') {
                return false;
            }

            $semVer = array_map('intval', explode('.', $value));

            for ($i = 0; $i < count($this->version); $i++) {
                if ($i >= count($semVer) || $semVer[$i] !== $this->version[$i]) {
                    return $this->negate;
                }
            }

            return !$this->negate;
        }

        if ($this->operator === Operator::SemverGte) {
            if (gettype($value) !== 'string') {
                return false;
            }

            $semVer = array_map('intval', explode('.', $value));

            for ($i = 0; $i < count($this->version); $i++) {
                if ($i >= count($semVer)) {
                    return $this->negate;
                }

                if ($semVer[$i] > $this->version[$i]) {
                    return !$this->negate;
                }

                if ($semVer[$i] < $this->version[$i]) {
                    return $this->negate;
                }
            }

            return !$this->negate;
        }

        if ($this->operator === Operator::SemverLte) {
            if (gettype($value) !== 'string') {
                return false;
            }

            $semVer = array_map('intval', explode('.', $value));

            for ($i = 0; $i < count($this->version); $i++) {
                if ($i >= count($semVer)) {
                    return $this->negate;
                }

                if ($semVer[$i] < $this->version[$i]) {
                    return !$this->negate;
                }

                if ($semVer[$i] > $this->version[$i]) {
                    return $this->negate;
                }
            }

            return !$this->negate;
        }

        if ($this->operator === Operator::Percentage) {
            if (gettype($value) !== 'string' && gettype($value) !== 'integer' && gettype($value) !== 'double') {
                return false;
            }

            $probability = hexdec((new V32($this->seed))->hash(strval($value))) / 0xFFFFFFFF;

            if ($probability === 1) {
                $probability -= 1.0e-8;
            }

            return ($probability >= $this->rangeStart && $probability < $this->rangeEnd) !== ($this->negate ?? false);
        }

        throw new Exception("Unsupported operator {$this->operator}");
    }
}
