<?php
require_once 'config.php';

// Récupérer l'action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'check':
        checkAuth();
        break;
    default:
        sendResponse(false, [], 'Action invalide');
}

/**
 * Connexion administrateur
 */
function login()
{
    global $pdo;

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendResponse(false, [], 'Nom d\'utilisateur et mot de passe requis');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // Vérifier si le mot de passe est hashé ou en clair
            $isPasswordCorrect = false;

            // Essayer avec password_verify (pour les mots de passe hashés)
            if (password_verify($password, $admin['password'])) {
                $isPasswordCorrect = true;
            }
            // Sinon, vérifier en clair (pour compatibilité temporaire)
            elseif ($password === $admin['password']) {
                $isPasswordCorrect = true;
            }

            if ($isPasswordCorrect) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                sendResponse(true, [
                    'username' => $admin['username'],
                    'email' => $admin['email']
                ], 'Connexion réussie');
            } else {
                sendResponse(false, [], 'Nom d\'utilisateur ou mot de passe incorrect');
            }
        } else {
            sendResponse(false, [], 'Nom d\'utilisateur ou mot de passe incorrect');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de la connexion: ' . $e->getMessage());
    }
}

/**
 * Déconnexion
 */
function logout()
{
    session_destroy();
    sendResponse(true, [], 'Déconnexion réussie');
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function checkAuth()
{
    $isLoggedIn = isAdminLoggedIn();

    $response = ['authenticated' => $isLoggedIn];

    if ($isLoggedIn) {
        $response['username'] = $_SESSION['admin_username'] ?? '';
        $response['admin_id'] = $_SESSION['admin_id'] ?? '';
    }

    sendResponse(true, $response);
}
