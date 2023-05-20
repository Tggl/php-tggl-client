<?php

namespace Tggl\Client;

use Exception;

class TgglClient
{
    protected string $apiKey;
    protected string $url;

    public function __construct(string $apiKey, array $options = [])
    {
        $this->apiKey = $apiKey;
        $this->url = $options['url'] ?? 'https://api.tggl.io/flags';
    }

    public function evalContext($context)
    {
        return $this->evalContexts([$context])[0];
    }

    /**
     * @return TgglResponse[]
     * @throws Exception
     */
    public function evalContexts(array $contexts): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Tggl-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($contexts));


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

        return array_map(function ($flags) {
            return new TgglResponse($flags);
        }, $decoded);
    }
}
