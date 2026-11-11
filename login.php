<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['role'] = $user['role'];

        setMessage("Bienvenue " . $user['nom'] . " !");
        redirect('index.php');
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Healthy Resto</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">🥗 Healthy Resto</div>
            <div class="nav-links">
                <a href="index.php">Menu</a>
                <a href="login.php" class="active">Connexion</a>
                <a href="register.php">Inscription</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2>Connexion</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <p style="text-align: center; margin-top: 1rem;">
            Pas encore de compte ? <a href="register.php" style="color: var(--primary-color); font-weight: bold;">Inscrivez-vous</a>
        </p>

        <div style="margin-top: 2rem; padding: 1rem; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
            <p style="margin: 0; font-size: 0.9rem; color: #856404;"><strong>💡 Comptes de test :</strong></p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #856404;">
                <strong>Admin :</strong> admin@healthy.com / password<br>
                <strong>Client :</strong> client@test.com / password
            </p>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Healthy Resto. Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>