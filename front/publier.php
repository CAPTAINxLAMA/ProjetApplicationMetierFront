<?php
require_once __DIR__ . '/includes/auth.php';   // connexion obligatoire
require_once __DIR__ . '/includes/api.php';

// Rôles autorisés à publier : garde la même liste que dans header.php
$rolesPublication = [1,2]; // <-- id du rôle « Content creator » dans la table Role
if (!in_array((int) ($_SESSION['user']['id_role'] ?? 0), $rolesPublication, true)) {
    http_response_code(403);
    exit('Accès réservé aux créateurs de contenu.');
}

$titre  = 'Publier';
$erreur = null;
$types  = [];
$form   = ['Nom' => '', 'id_type' => '', 'Description' => '', 'Prix' => '', 'Taille' => ''];

// Message de succès laissé par la redirection après une publication
$succes = $_SESSION['flash_succes'] ?? null;
unset($_SESSION['flash_succes']);

// Liste des types pour la liste déroulante
try {
    $types = api_get('/type');
} catch (RuntimeException $ex) {
    $erreur = $ex->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $types) {
    foreach ($form as $champ => $_) {
        $form[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $prix   = str_replace(',', '.', $form['Prix']);     // accepte « 9,99 » comme « 9.99 »
    $taille = str_replace(',', '.', $form['Taille']);
    $idsTypes = array_map('intval', array_column($types, 'id'));

    if ($form['Nom'] === '' || mb_strlen($form['Nom']) > 50) {
        $erreur = 'Le nom est obligatoire (50 caractères maximum).';
    } elseif (!in_array((int) $form['id_type'], $idsTypes, true)) {
        $erreur = 'Choisis un type de contenu.';
    } elseif (mb_strlen($form['Description']) > 255) {
        $erreur = 'La description ne doit pas dépasser 255 caractères.';
    } elseif (!is_numeric($prix) || (float) $prix < 0) {
        $erreur = 'Le prix doit être un nombre positif ou nul.';
    } elseif (!is_numeric($taille) || (float) $taille <= 0) {
        $erreur = 'La taille doit être un nombre supérieur à 0.';
    } else {
        $donnees = [
            'id_Createur' => (int) $_SESSION['user']['id'],  // toujours pris dans la session, jamais dans le formulaire
            'id_type'     => (int) $form['id_type'],
            'Nom'         => $form['Nom'],
            'Prix'        => (float) $prix,
            'Taille'      => (float) $taille,
        ];
        if ($form['Description'] !== '') {
            $donnees['Description'] = $form['Description'];
        }

        try {
            $reponse = api_post('/publier_contenu', $donnees);
            $_SESSION['flash_succes'] = 'Contenu « ' . ($reponse['contenu']['Nom'] ?? $form['Nom']) . ' » publié avec succès.';
            header('Location: publier.php');   // évite le renvoi du formulaire si on actualise la page
            exit;
        } catch (RuntimeException $ex) {
            $erreur = $ex->getMessage();
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 mb-4"><i class="bi bi-cloud-upload me-2"></i>Publier un contenu</h1>

        <?php if ($succes): ?>
          <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i><?= e($succes) ?>
            <a href="contenu.php" class="alert-link ms-1">Voir les contenus</a>
          </div>
        <?php endif; ?>

        <?php if ($erreur): ?>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?>
          </div>
        <?php endif; ?>

        <?php if ($types): ?>
          <form method="post" action="publier.php" novalidate>
            <div class="mb-3">
              <label for="Nom" class="form-label">Nom</label>
              <input type="text" class="form-control" id="Nom" name="Nom" maxlength="50"
                     value="<?= e($form['Nom']) ?>" required autofocus>
            </div>

            <div class="mb-3">
              <label for="id_type" class="form-label">Type</label>
              <select class="form-select" id="id_type" name="id_type" required>
                <option value="">Choisir un type…</option>
                <?php foreach ($types as $t): ?>
                  <option value="<?= e($t['id']) ?>" <?= (string) $t['id'] === $form['id_type'] ? 'selected' : '' ?>>
                    <?= e($t['Nom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label for="Description" class="form-label">Description <span class="text-secondary">(facultative)</span></label>
              <textarea class="form-control" id="Description" name="Description" rows="3"
                        maxlength="255"><?= e($form['Description']) ?></textarea>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-sm-6">
                <label for="Prix" class="form-label">Prix (€)</label>
                <input type="number" class="form-control" id="Prix" name="Prix"
                       min="0" step="0.01" value="<?= e($form['Prix']) ?>" required>
              </div>
              <div class="col-sm-6">
                <label for="Taille" class="form-label">Taille (Mo)</label>
                <input type="number" class="form-control" id="Taille" name="Taille"
                       min="0.01" step="0.01" value="<?= e($form['Taille']) ?>" required>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-cloud-upload me-1"></i>Publier
            </button>
          </form>
        <?php elseif (!$erreur): ?>
          <div class="alert alert-warning mb-0">Aucun type de contenu n'existe pour le moment.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>