<?php
require_once __DIR__ . '/../config.php';

function api_get(string $path): array
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException("API injoignable : $err");
    }
    $data = json_decode($body, true);
    if ($code >= 400) {
        throw new RuntimeException($data['error'] ?? "Erreur HTTP $code");
    }
    return $data ?? [];
}

function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}