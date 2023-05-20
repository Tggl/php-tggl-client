<?php

use Tggl\Client\Flag;
use PHPUnit\Framework\TestCase;

class StandardTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testFlag($config)
    {
        $flag = Flag::fromConfig($config->flag);
        $result = $flag->eval($config->context);

        $this->assertEquals($config->expected->active, $result->active);
        $this->assertEquals($config->expected->value, $result->value);
    }

    public static function provider()
    {
        $config = json_decode(file_get_contents('standard_tests.json'));

        return array_reduce($config, function ($carry, $item) use ($config) {
            $carry[$item->name] = [$item];
            return $carry;
        }, []);
    }
}
