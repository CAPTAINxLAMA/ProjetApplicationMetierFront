<?php
require_once __DIR__ . '/../config.php';

define('JWT_SECRET', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6NCwiaWRfcm9sZSI6MywiaWF0IjoxNzk0OTI0MDg3LCJleHAiOjE3OTQ5MjQ2ODd9.7yr6k10Cghy7hrghVYocfd2Hk_PPvaUm7L6nwKJDW-Y');

// Fabrique un JWT (HS256) valable 5 min à partir de l'utilisateur en session
function jwt_session(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user = $_SESSION['user'] ?? null;
    if (!$user) return '';

    $b64 = fn($d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

    $header  = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = $b64(json_encode([
        'id'      => (int) $user['id'],
        'id_role' => (int) $user['id_role'],
        'exp'     => time() + 300,
    ]));
    $signature = $b64(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    return "$header.$payload.$signature";
}

// En-têtes communs : le token n'est ajouté que si l'utilisateur est connecté
function api_headers(array $extra = []): array
{
    $headers = array_merge(['Accept: application/json'], $extra);
    $token = jwt_session();
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    return $headers;
}

function api_get(string $path): array
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => api_headers(),
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

// Envoie un POST en JSON vers l'API (même gestion d'erreurs que api_get)
function api_post(string $path, array $donnees): array
{
    $ch = curl_init(API_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => api_headers(['Content-Type: application/json']),
        CURLOPT_POSTFIELDS     => json_encode($donnees),
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