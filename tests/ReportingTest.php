<?php

use Tggl\Client\TgglReporting;
use PHPUnit\Framework\TestCase;

class ReportingTest extends TestCase
{
    protected $reporter;

    protected function setUp(): void {
        $this->reporter = new TgglReporting('API_KEY');
        $this->reporter->apiClient = new class {
            public $calls = [];

            public function  call(string $url, bool $post, string $apiKey = null, $body = null)
            {
                $this->calls[] = [
                    'url' => $url,
                    'post' => $post,
                    'apiKey' => $apiKey,
                    'body' => $body,
                ];
            }
        };
    }

    public function testNothingToReport()
    {
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, []);
    }

    public function testSingleFlag()
    {
        $this->reporter->reportFlag('my_feature', null, null);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'clients' => [
                    [
                        'flags' => [
                            'my_feature' => [
                                [
                                    'value' => null,
                                    'default' => null,
                                    'count' => 1
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testSingleFlagWithValueAndDefault()
    {
        $this->reporter->reportFlag('my_feature', 5, 'foo');
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'clients' => [
                    [
                        'flags' => [
                            'my_feature' => [
                                [
                                    'value' => 5,
                                    'default' => 'foo',
                                    'count' => 1
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testMultipleFlags()
    {
        $this->reporter->reportFlag('flagA', true, false);
        $this->reporter->reportFlag('flagB', false, false);
        $this->reporter->reportFlag('flagC', true, false);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'clients' => [
                    [
                        'flags' => [
                            'flagA' => [
                                [
                                    'value' => true,
                                    'default' => false,
                                    'count' => 1
                                ]
                            ],
                            'flagB' => [
                                [
                                    'value' => false,
                                    'default' => false,
                                    'count' => 1
                                ]
                            ],
                            'flagC' => [
                                [
                                    'value' => true,
                                    'default' => false,
                                    'count' => 1
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testSameFlagMultipleTimes()
    {
        $this->reporter->reportFlag('flagA', true, null);
        $this->reporter->reportFlag('flagA', false, null);
        $this->reporter->reportFlag('flagA', false, null);
        $this->reporter->reportFlag('flagA', true, null);
        $this->reporter->reportFlag('flagA', true, null);
        $this->reporter->reportFlag('flagA', true, null);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'clients' => [
                    [
                        'flags' => [
                            'flagA' => [
                                [
                                    'value' => true,
                                    'default' => null,
                                    'count' => 4
                                ],
                                [
                                    'value' => false,
                                    'default' => null,
                                    'count' => 2
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testSameFlagMultipleTimesWithValueAndDefault()
    {
        $this->reporter->reportFlag('flagA', null, null);
        $this->reporter->reportFlag('flagA', null, null);
        $this->reporter->reportFlag('flagA', 'foo', 'bar');
        $this->reporter->reportFlag('flagA', 'foo', 'bar');
        $this->reporter->reportFlag('flagA', 'foo', 'baz');
        $this->reporter->reportFlag('flagA', 'baz', 'bar');
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'clients' => [
                    [
                        'flags' => [
                            'flagA' => [
                                [
                                    'value' => null,
                                    'default' => null,
                                    'count' => 2
                                ],
                                [
                                    'value' => 'foo',
                                    'default' => 'bar',
                                    'count' => 2
                                ],
                                [
                                    'value' => 'foo',
                                    'default' => 'baz',
                                    'count' => 1
                                ],
                                [
                                    'value' => 'baz',
                                    'default' => 'bar',
                                    'count' => 1
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ]]);
    }

    public function testContextWithStringValue()
    {
        $ctx = new stdClass();
        $ctx->foo = 'bar';
        $this->reporter->reportContext($ctx);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'receivedProperties' => [
                    'foo' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['foo'],
                ],
                'receivedValues' => [
                    'foo' => [['bar']],
                ],
            ],
        ]]);
    }

    public function testContextWithStringValueAndLabel()
    {
        $ctx = new stdClass();
        $ctx->userId = 'abc';
        $ctx->userName = 'Elon Musk';
        $this->reporter->reportContext($ctx);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'receivedProperties' => [
                    'userId' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['userId'],
                    'userName' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['userName'],
                ],
                'receivedValues' => [
                    'userId' => [['abc', 'Elon Musk']],
                    'userName' => [['Elon Musk']],
                ],
            ],
        ]]);
    }

    public function testContextWithNonStringValue()
    {
        $ctx = new stdClass();
        $ctx->foo = 0;
        $ctx->bar = true;
        $ctx->baz = null;
        $this->reporter->reportContext($ctx);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'receivedProperties' => [
                    'foo' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['foo'],
                    'bar' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['bar'],
                    'baz' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['baz'],
                ],
            ],
        ]]);
    }

    public function testMultipleContexts()
    {
        $ctx1 = new stdClass();
        $ctx1->foo = 0;
        $this->reporter->reportContext($ctx1);
        $ctx2 = new stdClass();
        $ctx2->foo = 'bar';
        $this->reporter->reportContext($ctx2);
        $ctx3 = new stdClass();
        $ctx3->foo = 'baz';
        $this->reporter->reportContext($ctx3);
        $ctx4 = new stdClass();
        $ctx4->foo = 'bar';
        $this->reporter->reportContext($ctx4);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'receivedProperties' => [
                    'foo' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['foo'],
                ],
                'receivedValues' => [
                    'foo' => [['bar'], ['baz']],
                ],
            ],
        ]]);
    }

    public function testMultipleContextsWithLabel()
    {
        $ctx1 = new stdClass();
        $ctx1->userId = 'abc';
        $ctx1->userName = 'Elon Musk';
        $this->reporter->reportContext($ctx1);
        $ctx2 = new stdClass();
        $ctx2->userId = 'def';
        $ctx2->userName = 'Jeff Bezos';
        $this->reporter->reportContext($ctx2);
        $ctx3 = new stdClass();
        $ctx3->userId = 42;
        $ctx3->userName = 'Buzz Aldrin';
        $this->reporter->reportContext($ctx3);
        $ctx4 = new stdClass();
        $ctx4->userId = 'abc';
        $ctx4->userName = 'Alan Turing';
        $this->reporter->reportContext($ctx4);
        $this->reporter->sendReport();

        $this->assertEquals($this->reporter->apiClient->calls, [[
            'url' => 'https://api.tggl.io/report',
            'post' => true,
            'apiKey' => 'API_KEY',
            'body' => [
                'receivedProperties' => [
                    'userId' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['userId'],
                    'userName' => $this->reporter->apiClient->calls[0]['body']['receivedProperties']['userName'],
                ],
                'receivedValues' => [
                    'userId' => [
                        ['abc', 'Alan Turing'],
                        ['def', 'Jeff Bezos'],
                    ],
                    'userName' => [
                        ['Elon Musk'],
                        ['Jeff Bezos'],
                        ['Buzz Aldrin'],
                        ['Alan Turing'],
                    ],
                ],
            ],
        ]]);
    }
}
