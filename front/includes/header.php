<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$titre = $titre ?? 'Steam_2';
$page  = basename($_SERVER['SCRIPT_NAME']);
$user  = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titre) ?> · Steam_2</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-controller me-2"></i>Steam_2</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?= $page === 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house me-1"></i>Accueil</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $page === 'users.php' ? 'active' : '' ?>" href="users.php">
            <i class="bi bi-people me-1"></i>Utilisateurs</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $page === 'contenu.php' ? 'active' : '' ?>" href="contenu.php">
            <i class="bi bi-collection-play me-1"></i>Contenus</a>
        </li>
        <?php if ($user): ?>
          <li class="nav-item">
            <span class="nav-link text-white ms-lg-1">
              <i class="bi bi-person-circle me-1"></i><?= e($user['Pseudonyme']) ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= $page === 'login.php' ? 'active' : '' ?>" href="login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Connexion</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container pb-5">