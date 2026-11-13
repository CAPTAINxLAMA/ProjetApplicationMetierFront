<?php
require_once __DIR__ . '/includes/api.php';

$titre  = 'Utilisateurs';
$erreur = null;
$users  = [];

try {
    $users = api_get('/users');
    $roles = array_column(api_get('/role'), 'Nom', 'id');
    $sexes = array_column(api_get('/sexe'), 'Genre', 'id');
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($erreur): ?>
  <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?></div>
<?php else: ?>
  <div class="card">
    <div class="card-header bg-white border-0 pt-4 px-4">
      <div class="row g-3 align-items-center">
        <div class="col-md">
          <h1 class="h4 mb-0"><i class="bi bi-people me-2"></i>Utilisateurs
            <span class="badge text-bg-primary ms-2"><?= count($users) ?></span></h1>
        </div>
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" id="recherche" class="form-control" placeholder="Rechercher un utilisateur…">
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive px-2 pb-2">
      <table class="table table-hover align-middle mb-0" id="tableUsers">
        <thead class="table-light">
          <tr>
            <th>Utilisateur</th><th>Nom</th><th>Âge</th>
            <th>Rôle</th><th>Genre</th><th class="text-end">Portefeuille</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-3">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($u['Pseudonyme'], 0, 1))) ?></div>
                <div>
                  <div class="fw-semibold"><?= e($u['Pseudonyme']) ?></div>
                  <div class="text-secondary small"><?= e($u['Email']) ?></div>
                </div>
              </div>
            </td>
            <td><?= e($u['Prenom'] . ' ' . $u['Nom']) ?></td>
            <td><?= (int) $u['Age'] ?> ans</td>
            <td><span class="badge rounded-pill text-bg-primary"><?= e($roles[$u['id_role']] ?? '?') ?></span></td>
            <td><?= e($sexes[$u['id_sexe']] ?? '-') ?></td>
            <td class="text-end fw-semibold"><?= number_format((float) $u['Porte_feuille'], 2, ',', ' ') ?> €</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    document.getElementById('recherche').addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('#tableUsers tbody tr').forEach(function (tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  </script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>