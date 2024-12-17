<?php

use Tggl\Client\Flag;
use Tggl\Client\TgglLocalClient;
use PHPUnit\Framework\TestCase;

class LocalClientTest extends TestCase
{
    public function testGetUnknownFlag()
    {
        $client = new TgglLocalClient('foo');

        $this->assertEquals($client->get(new stdClass(), 'my_feature', null), null);
        $this->assertEquals($client->get(new stdClass(), 'my_feature', 'default value'), 'default value');
    }

    public function testGetKnownFlag()
    {
        $my_feature = new stdClass();
        $my_feature->conditions = [];
        $my_feature->defaultVariation = new stdClass();
        $my_feature->defaultVariation->active = true;
        $my_feature->defaultVariation->value = 'foo';

        $client = new TgglLocalClient('foo', [
            'config' => [
                'my_feature' => Flag::fromConfig($my_feature),
            ],
        ]);

        $this->assertEquals($client->get(new stdClass(), 'my_feature', null), 'foo');
        $this->assertEquals($client->get(new stdClass(), 'my_feature', 'bar'), 'foo');
    }

    public function testGetKnownInactiveFlag()
    {
        $my_feature = new stdClass();
        $my_feature->conditions = [];
        $my_feature->defaultVariation = new stdClass();
        $my_feature->defaultVariation->active = false;
        $my_feature->defaultVariation->value = null;

        $client = new TgglLocalClient('foo', [
            'config' => [
                'my_feature' => Flag::fromConfig($my_feature),
            ],
        ]);

        $this->assertEquals($client->get(new stdClass(), 'my_feature', null), null);
        $this->assertEquals($client->get(new stdClass(), 'my_feature', 'default value'), 'default value');
    }


    public function testGetAllActiveFlagsNoFlags()
    {
        $client = new TgglLocalClient('foo');

        $this->assertEquals($client->getAllActiveFlags(new stdClass(), 'my_feature'), []);
    }

    public function testGetAllActiveFlagsActiveFlag()
    {
        $my_feature = new stdClass();
        $my_feature->conditions = [];
        $my_feature->defaultVariation = new stdClass();
        $my_feature->defaultVariation->active = true;
        $my_feature->defaultVariation->value = 'foo';

        $client = new TgglLocalClient('foo', [
            'config' => [
                'my_feature' => Flag::fromConfig($my_feature),
            ],
        ]);

        $this->assertEquals($client->getAllActiveFlags(new stdClass(), 'my_feature'), [
            'my_feature' => 'foo'
        ]);
    }

    public function testGetAllActiveFlagsInactiveFlag()
    {
        $my_feature = new stdClass();
        $my_feature->conditions = [];
        $my_feature->defaultVariation = new stdClass();
        $my_feature->defaultVariation->active = false;
        $my_feature->defaultVariation->value = null;

        $client = new TgglLocalClient('foo', [
            'config' => [
                'my_feature' => Flag::fromConfig($my_feature),
            ],
        ]);

        $this->assertEquals($client->getAllActiveFlags(new stdClass(), 'my_feature'), []);
    }
}
