<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$connecte = !empty($_SESSION['user']);

$titre    = 'Contenus';
$erreur   = null;
$contenus = [];

try {
    $contenus = api_get('/contenu');
    $types    = array_column(api_get('/type'), 'Nom', 'id');
    $createurs = array_column(api_get('/createurs'), 'Pseudonyme', 'id');
    $likes    = array_count_values(array_column(api_get('/likeur'), 'id_contenu'));
    $achats   = array_count_values(array_column(api_get('/consommateur'), 'id_contenu'));
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<h1 class="h4 mb-4"><i class="bi bi-collection-play me-2"></i>Contenus
  <span class="badge text-bg-primary ms-2"><?= count($contenus) ?></span></h1>

<div id="messageAchat"></div>

<?php if ($erreur): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?></div>
<?php elseif (!$contenus): ?>
  <div class="alert alert-info">Aucun contenu pour le moment.</div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($contenus as $c): ?>
      <div class="col">
        <div class="card card-hover h-100"
             role="button"
             data-bs-toggle="modal"
             data-bs-target="#modalAchat"
             data-id="<?= (int) $c['id'] ?>"
             data-nom="<?= e($c['Nom']) ?>"
             data-prix="<?= e($c['Prix']) ?>">
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

  <!-- Popup de confirmation d'achat -->
  <div class="modal fade" id="modalAchat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-cart-check me-2"></i>Confirmer l'achat</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <?php if ($connecte): ?>
            <p class="mb-0">Voulez-vous acheter <strong id="achatNom"></strong> pour <strong id="achatPrix"></strong> ?</p>
          <?php else: ?>
            <p class="mb-0">Vous devez être connecté pour acheter un contenu.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <?php if ($connecte): ?>
            <button type="button" class="btn btn-primary" id="btnConfirmerAchat">Confirmer l'achat</button>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary">Se connecter</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modalEl = document.getElementById('modalAchat');
      const btn = document.getElementById('btnConfirmerAchat');
      const zoneMessage = document.getElementById('messageAchat');
      let idContenu = null;

      // Remplir la popup avec le contenu cliqué
      modalEl.addEventListener('show.bs.modal', function (event) {
        const card = event.relatedTarget;
        idContenu = parseInt(card.dataset.id, 10);
        const nomEl = document.getElementById('achatNom');
        const prixEl = document.getElementById('achatPrix');
        if (nomEl) nomEl.textContent = card.dataset.nom;
        if (prixEl) {
          const prix = parseFloat(card.dataset.prix);
          prixEl.textContent = prix === 0
            ? 'gratuit'
            : prix.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €';
        }
      });

      if (!btn) return;

      // Confirmer : appel à acheter.php, qui appelle l'API
      btn.addEventListener('click', async function () {
        btn.disabled = true;
        let classe = 'danger';
        let texte = '';
        try {
          const rep = await fetch('acheter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_contenu: idContenu })
          });
          const data = await rep.json();
          if (rep.ok) {
            classe = 'success';
            texte = data.message + ' — nouveau solde : ' +
              Number(data.nouveau_solde).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €';
          } else if (rep.status === 402) {
            texte = 'Solde insuffisant : il vous manque ' +
              Number(data.manquant).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' €.';
          } else {
            texte = data.error || 'Une erreur est survenue.';
          }
        } catch (err) {
          texte = "Impossible de contacter le serveur.";
        }

        bootstrap.Modal.getInstance(modalEl).hide();
        zoneMessage.innerHTML =
          '<div class="alert alert-' + classe + ' alert-dismissible fade show">' +
          texte +
          '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        btn.disabled = false;

        // Rafraîchir les compteurs d'achats après un succès
        if (classe === 'success') setTimeout(() => location.reload(), 2000);
      });
    });
  </script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>