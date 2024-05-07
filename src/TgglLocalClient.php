<?php

namespace Tggl\Client;

use Exception;

class TgglLocalClient
{
    protected string $apiKey;
    /** @var Flag[] */
    protected array $config;
    protected string $url;

    public function __construct(string $apiKey, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->config = $options['config'] ?? [];
        $this->url = $options['url'] ?? 'https://api.tggl.io/config';
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
        return array_key_exists($slug, $this->config) ? $this->config[$slug]->eval($context)->active : false;
    }

    public function get($context, string $slug, $defaultValue = null)
    {
        $inactiveVariation = new Variation();
        $inactiveVariation->active = false;
        $inactiveVariation->value = null;

        $result = array_key_exists($slug, $this->config) ? $this->config[$slug]->eval($context) : $inactiveVariation;
        return $result->active ? $result->value : $defaultValue;
    }

    public function getAllActiveFlags($context)
    {
        return array_map(
            function ($flag) {
                return $flag->value;
            },
            array_filter(
                array_map(function ($flag) {
                    return $flag->eval($context);
                }, $this->config),
                function ($flag) {
                    return $flag->active;
                }
            )
        );
    }
}
