<?php
require_once __DIR__ . '/includes/api.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déjà connecté : retour à l'accueil
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$titre  = 'Connexion';
$erreur = null;
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $erreur = 'Merci de remplir tous les champs.';
    } else {
        try {
            $data = api_post('/login', ['email' => $email, 'password' => $password]);
            if (!isset($data['user'])) {
                throw new RuntimeException('Réponse inattendue de l\'API.');
            }
            session_regenerate_id(true);        // protège contre le vol de session
            $_SESSION['user'] = $data['user'];  // id, id_role, Pseudonyme, Nom, Prenom, Email
            header('Location: index.php');
            exit;
        } catch (RuntimeException $ex) {
            $erreur = $ex->getMessage();        // ex. « Email ou mot de passe incorrect »
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 mb-4"><i class="bi bi-box-arrow-in-right me-2"></i>Connexion</h1>

        <?php if ($erreur): ?>
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($erreur) ?>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($email) ?>" required autofocus>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>