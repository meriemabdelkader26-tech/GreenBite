<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $telephone = sanitize($_POST['telephone']);
    $adresse = sanitize($_POST['adresse']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Créer le compte
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (nom, email, telephone, adresse, password, role) 
                VALUES (?, ?, ?, ?, ?, 'client')
            ");

            if ($stmt->execute([$nom, $email, $telephone, $adresse, $hashed_password])) {
                setMessage("Compte créé avec succès ! Vous pouvez maintenant vous connecter.");
                redirect('login.php');
            } else {
                $error = "Erreur lors de la création du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Healthy Resto</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">🥗 Healthy Resto</div>
            <div class="nav-links">
                <a href="index.php">Menu</a>
                <a href="login.php">Connexion</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h2>Inscription</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input type="text" id="nom" name="nom" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" class="form-control">
            </div>

            <div class="form-group">
                <label for="adresse">Adresse</label>
                <textarea id="adresse" name="adresse" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
        </form>

        <p style="text-align: center; margin-top: 1rem;">
            Déjà un compte ? <a href="login.php">Connectez-vous</a>
        </p>
    </div>
</body>

</html>