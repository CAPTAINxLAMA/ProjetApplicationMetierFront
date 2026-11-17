<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['user'] ?? null;
if (!$user) {
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

// L'id_user vient de la session, pas du navigateur
$payload = json_encode(['id_user' => (int) $user['id'], 'id_contenu' => $idContenu]);

$ch = curl_init('http://localhost:3000/acheter'); // adapte si tu as une constante dans includes/api.php
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
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

// On renvoie tel quel le statut et le JSON de l'API (201, 402, 409...)
http_response_code($status);
echo $reponse;