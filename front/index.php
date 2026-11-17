<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';

$titre  = 'Accueil';
$erreur = null;
$stats  = [];

try {
    $stats = [
        ['Utilisateurs', count(api_get('/users')),   'bi-people',          'primary'],
        ['Contenus',     count(api_get('/contenu')), 'bi-collection-play', 'success'],
        ['Types',        count(api_get('/type')),    'bi-tags',            'warning'],
        ['Rôles',        count(api_get('/role')),    'bi-shield-check',    'danger'],
    ];
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<section class="hero p-5 mb-4">
  <h1 class="display-5 fw-bold">Bienvenue sur Steam_2</h1>
  <p class="lead mb-4">Explore les utilisateurs et le catalogue de contenus de la plateforme.</p>
  <a href="users.php" class="btn btn-light me-2"><i class="bi bi-people me-1"></i>Utilisateurs</a>
  <a href="contenu.php" class="btn btn-primary"><i class="bi bi-collection-play me-1"></i>Contenus</a>
</section>

<?php if ($erreur): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($stats as [$label, $nb, $icone, $couleur]): ?>
      <div class="col-6 col-lg-3">
        <div class="card card-hover h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon bg-<?= $couleur ?>-subtle text-<?= $couleur ?>">
              <i class="bi <?= $icone ?>"></i>
            </div>
            <div>
              <div class="fs-3 fw-bold lh-1"><?= $nb ?></div>
              <div class="text-secondary small"><?= e($label) ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>