<?php
require_once __DIR__ . '/includes/api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vous devez être connecté']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$donnees   = json_decode(file_get_contents('php://input'), true);
$idContenu = $donnees['id_contenu'] ?? null;
if (!is_int($idContenu)) {
    http_response_code(400);
    echo json_encode(['error' => 'id_contenu invalide']);
    exit;
}

// L'id_user n'est plus envoyé : l'API le lit dans le token
$ch = curl_init(API_URL . '/acheter');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['id_contenu' => $idContenu]),
    CURLOPT_HTTPHEADER     => api_headers(['Content-Type: application/json']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$reponse = curl_exec($ch);
$status  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($reponse === false) {
    http_response_code(502);
    echo json_encode(['error' => "Impossible de contacter l'API"]);
    exit;
}

http_response_code($status);

// Achat réussi : on met à jour le solde stocké en session
if ($status === 201) {
    $data = json_decode($reponse, true);
    if (isset($data['nouveau_solde'])) {
        $_SESSION['user']['Porte_feuille'] = $data['nouveau_solde'];
    }
}

echo $reponse;