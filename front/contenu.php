<?php
require_once __DIR__ . '/includes/api.php';

$titre    = 'Contenus';
$erreur   = null;
$contenus = [];

try {
    $contenus = api_get('/contenu');
    $types    = array_column(api_get('/type'), 'Nom', 'id');
    $createurs = array_column(api_get('/users'), 'Pseudonyme', 'id');
    $likes    = array_count_values(array_column(api_get('/likeur'), 'id_contenu'));
    $achats   = array_count_values(array_column(api_get('/consommateur'), 'id_contenu'));
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4"><i class="bi bi-collection-play me-2"></i>Contenus
  <span class="badge text-bg-primary ms-2"><?= count($contenus) ?></span></h1>

<?php if ($erreur): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?></div>
<?php elseif (!$contenus): ?>
  <div class="alert alert-info">Aucun contenu pour le moment.</div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($contenus as $c): ?>
      <div class="col">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge text-bg-secondary"><?= e($types[$c['id_type']] ?? '?') ?></span>
              <?php if ((float) $c['Prix'] == 0): ?>
                <span class="badge text-bg-success">Gratuit</span>
              <?php else: ?>
                <span class="badge text-bg-primary"><?= number_format((float) $c['Prix'], 2, ',', ' ') ?> €</span>
              <?php endif; ?>
            </div>
            <h2 class="h5 card-title"><?= e($c['Nom']) ?></h2>
            <p class="card-text text-secondary flex-grow-1"><?= e($c['Description'] ?? 'Pas de description.') ?></p>
            <div class="text-secondary small mb-3">
              <i class="bi bi-person me-1"></i><?= e($createurs[$c['id_Createur']] ?? 'Inconnu') ?>
              · <i class="bi bi-hdd me-1"></i><?= e($c['Taille']) ?>
            </div>
          </div>
          <div class="card-footer bg-white border-0 pb-3 d-flex gap-3">
            <span class="text-danger"><i class="bi bi-heart-fill me-1"></i><?= $likes[$c['id']] ?? 0 ?></span>
            <span class="text-success"><i class="bi bi-cart-check me-1"></i><?= $achats[$c['id']] ?? 0 ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>