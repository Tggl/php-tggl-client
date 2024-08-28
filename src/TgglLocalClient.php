<?php

namespace Tggl\Client;

use Exception;

class TgglLocalClient
{
    protected $apiKey;
    /** @var Flag[] */
    protected array $config;
    protected string $url;
    protected $reporter;

    public function __construct($apiKey = null, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->config = $options['config'] ?? [];
        $this->url = $options['url'] ?? 'https://api.tggl.io/config';
        $this->reporter = (array_key_exists('reporting', $options) && $options['reporting'] === false) || $apiKey === null
            ? null
            : new TgglReporting($apiKey, [
                'app' =>
                  array_key_exists('reporting', $options) && is_array($options['reporting']) && isset($options['reporting']['app'])
                    ? $options['reporting']['app']
                    : null,
                'appPrefix' => 'php-client:1.4.1/TgglLocalClient',
                'url' =>
                  array_key_exists('reporting', $options) && is_array($options['reporting']) && isset($options['reporting']['url'])
                    ? $options['reporting']['url']
                    : null,
              ]);
    }

    /**
     * @throws Exception
     */
    public function fetchConfig()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Tggl-Api-Key: ' . $this->apiKey]);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $decoded = json_decode($result);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode > 200) {
            if (gettype($decoded) === 'NULL') {
                throw new Exception('Invalid response from Tggl: ' . $httpCode);
            }
            throw new Exception($decoded->error);
        }

        $this->config = [];

        foreach ($decoded as $config) {
            $this->config[$config->slug] = Flag::fromConfig($config);
        }

        return $this->config;
    }

    public function isActive($context, string $slug)
    {
        $inactiveVariation = new Variation();
        $inactiveVariation->active = false;
        $inactiveVariation->value = null;

        $result = array_key_exists($slug, $this->config) ? $this->config[$slug]->eval($context) : $inactiveVariation;

        if (isset($this->reporter)) {
            $this->reporter->reportFlag($slug, $result->active, $result->value);
            $this->reporter->reportContext($context);
        }

        return $result->active;
    }

    public function get($context, string $slug, $defaultValue = null)
    {
        $inactiveVariation = new Variation();
        $inactiveVariation->active = false;
        $inactiveVariation->value = null;

        $result = array_key_exists($slug, $this->config) ? $this->config[$slug]->eval($context) : $inactiveVariation;
        $value = $result->active ? $result->value : $defaultValue;

        if (isset($this->reporter)) {
            $this->reporter->reportFlag($slug, $result->active, $value, $defaultValue);
            $this->reporter->reportContext($context);
        }

        return $value;
    }

    public function getAllActiveFlags($context)
    {
        if (isset($this->reporter)) {
            $this->reporter->reportContext($context);
        }

        return array_map(
            function ($flag) {
                return $flag->value;
            },
            array_filter(
                array_map(function ($flag) use ($context) {
                    return $flag->eval($context);
                }, $this->config),
                function ($flag) {
                    return $flag->active;
                }
            )
        );
    }
}
