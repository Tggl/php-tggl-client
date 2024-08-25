<?php

namespace Tggl\Client;

use Exception;

class ApiClient
{
    public function call(string $url, bool $post, string $apiKey = null, $body = null)
    {
        $ch = curl_init();

        $headers = [];

        if ($apiKey != null) {
            $headers[] = 'X-Tggl-Api-Key: ' . $apiKey;
        }

        if ($body != null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $post ? 1 : 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($body != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $decoded = json_decode($result);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 300) {
            if (gettype($decoded) === 'NULL') {
                throw new Exception('Invalid response from Tggl: ' . $httpCode);
            }
            throw new Exception($decoded->error);
        }

        return $decoded;
    }
}
