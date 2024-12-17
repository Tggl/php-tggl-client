<?php

namespace Tggl\Client;

use Exception;

class TgglReporting
{
    private $app;
    public $appPrefix;
    private string $apiKey;
    private string $url;
    private int $lastReportTime;
    private array $flagsToReport;
    private array $receivedPropertiesToReport;
    private array $receivedValuesToReport;
    public $apiClient;

    public function __construct(string $apiKey, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->url = $options['url'] ?? 'https://api.tggl.io/report';
        $this->app = $options['app'] ?? null;
        $this->appPrefix = $options['appPrefix'] ?? null;
        $this->lastReportTime = time();
        $this->apiClient = new ApiClient();
    }

    public function __destruct()
    {
        $this->sendReport();
    }

    public function sendReport()
    {
        try {
            $payload = [];

            if (!empty($this->flagsToReport)) {
                $flagsToReport = $this->flagsToReport;
                $this->flagsToReport = [];

                $clientId = $this->appPrefix . (!empty($this->app) && !empty($this->appPrefix) ? '/' : '') . $this->app;
                $payload['clients'] = [
                    [
                        'flags' => array_reduce(array_keys($flagsToReport), function ($acc, $key) use ($flagsToReport) {
                            $acc[$key] = array_values($flagsToReport[$key]);
                            return $acc;
                        }, [])
                    ]
                ];

                if (!empty($clientId)) {
                    $payload['client'][0]['id'] = $clientId;
                }
            }

            if (!empty($this->receivedPropertiesToReport)) {
                $receivedProperties = $this->receivedPropertiesToReport;
                $this->receivedPropertiesToReport = [];

                $payload['receivedProperties'] = $receivedProperties;
            }

            if (!empty($this->receivedValuesToReport)) {
                $receivedValues = $this->receivedValuesToReport;
                $this->receivedValuesToReport = [];

                $data = array_reduce(array_keys($receivedValues), function ($acc, $key) use ($receivedValues) {
                    foreach (array_keys($receivedValues[$key]) as $value) {
                        $label = $receivedValues[$key][$value];
                        if (!empty($label)) {
                            $acc[] = [$key, $value, $label];
                        } else {
                            $acc[] = [$key, $value];
                        }
                    }
                    return $acc;
                }, []);

                $pageSize = 2000;

                $payload['receivedValues'] = array_reduce(array_slice($data, 0, $pageSize), function ($acc, $cur) {
                    $acc[$cur[0]] = $acc[$cur[0]] ?? [];
                    $acc[$cur[0]][] = array_map(function ($v) {
                        return substr($v, 0, 240);
                    }, array_slice($cur, 1));
                    return $acc;
                }, []);

                for ($i = $pageSize; $i < count($data); $i += $pageSize) {
                    $this->apiClient->call($this->url, true, $this->apiKey, [
                        'receivedValues' => array_reduce(array_slice($data, $i, $pageSize), function ($acc, $cur) {
                            $acc[$cur[0]] = $acc[$cur[0]] ?? [];
                            $acc[$cur[0]][] = array_map(function ($v) {
                                return substr($v, 0, 240);
                            }, array_slice($cur, 1));
                            return $acc;
                        }, [])
                    ]);
                }
            }

            if (!empty($payload)) {
                $this->apiClient->call($this->url, true, $this->apiKey, $payload);
            }
        } catch (Exception $e) {
            // Do nothing
        }

        $this->lastReportTime = time();
    }

    public function reportFlag(string $slug, $value, $default)
    {
        try {
            $key = json_encode($value) . json_encode($default);

            if (!isset($this->flagsToReport[$slug])) {
                $this->flagsToReport[$slug] = [];
            }

            if (!isset($this->flagsToReport[$slug][$key])) {
                $this->flagsToReport[$slug][$key] = [
                    'value' => $value,
                    'default' => $default,
                    'count' => 0,
                ];
            }

            $this->flagsToReport[$slug][$key]['count']++;
        } catch (Exception $e) {
            // Do nothing
        }

        if ((time() - $this->lastReportTime) >= 5) {
            $this->sendReport();
        }
    }

    private function constantCase(string $str): string {
        return strtoupper(
            preg_replace(
                '/[\W_]+/', '_',
                preg_replace(
                    '/([a-z])([A-Z])/', '$1_$2', $str
                )
            )
        );
    }

    public function reportContext($context)
    {
        try {
            $now = time();

            foreach ($context as $key => $value) {
                if (isset($this->receivedPropertiesToReport[$key])) {
                    $this->receivedPropertiesToReport[$key][1] = $now;
                } else {
                    $this->receivedPropertiesToReport[$key] = [$now, $now];
                }

                if (is_string($value) && !empty($value)) {
                    $constantCaseKey = preg_replace('/_I_D$/', '_ID', $this->constantCase($key));

                    $labelKeyTarget = substr($constantCaseKey, -3) === '_ID'
                        ? preg_replace('/_ID$/', '_NAME', $constantCaseKey)
                        : null;

                    $labelKey = null;
                    if ($labelKeyTarget) {
                        foreach ($context as $originalKey => $originalValue) {
                            if ($this->constantCase($originalKey) === $labelKeyTarget) {
                                $labelKey = $originalKey;
                                break;
                            }
                        }
                    }

                    if (!isset($this->receivedValuesToReport[$key])) {
                        $this->receivedValuesToReport[$key] = [];
                    }

                    $this->receivedValuesToReport[$key][$value] = $labelKey !== null && is_string($context->{$labelKey}) && !empty($context->{$labelKey})
                        ? $context->{$labelKey}
                        : null;
                }
            }
        } catch (Exception $e) {
            // Do nothing
        }

        if ((time() - $this->lastReportTime) >= 5) {
            $this->sendReport();
        }
    }
}
