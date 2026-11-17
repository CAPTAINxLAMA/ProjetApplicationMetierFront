<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';

// Non connecté -> page de connexion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

$titre  = 'Bibliothèque';
$erreur = null;
$jeux   = [];

try {
    $jeux = api_get('/users/' . (int) $user['id'] . '/achats?');
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4"><i class="bi bi-journal-bookmark me-2"></i>Ma bibliothèque
  <span class="badge text-bg-primary ms-2"><?= count($jeux) ?></span></h1>

<?php if ($erreur): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?></div>
<?php elseif (!$jeux): ?>
  <div class="alert alert-info">
    Vous n'avez encore acheté aucun jeu.
    <a href="contenu.php" class="btn btn-primary btn-sm ms-2">Découvrir les contenus</a>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($jeux as $j): ?>
      <div class="col">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge text-bg-secondary"><?= e($j['Type']) ?></span>
              <?php if ((float) $j['Prix'] == 0): ?>
                <span class="badge text-bg-success">Gratuit</span>
              <?php else: ?>
                <span class="badge text-bg-primary"><?= number_format((float) $j['Prix'], 2, ',', ' ') ?> €</span>
              <?php endif; ?>
            </div>
            <h2 class="h5 card-title"><?= e($j['Nom']) ?></h2>
            <p class="card-text text-secondary flex-grow-1"><?= e($j['Description'] ?? 'Pas de description.') ?></p>
            <div class="text-secondary small">
              <i class="bi bi-hdd me-1"></i><?= e($j['Taille']) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>