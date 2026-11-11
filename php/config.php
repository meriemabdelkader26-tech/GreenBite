<?php
// Configuration de la base de données GreenBite
define('DB_HOST', 'localhost');
define('DB_NAME', 'greenbite_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données avec PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
    ]));
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers pour les réponses JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Fonction utilitaire pour envoyer une réponse JSON
function sendResponse($success, $data = [], $message = '')
{
    $response = ['success' => $success];

    if ($message) {
        $response['message'] = $message;
    }

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Fonction pour vérifier si l'admin est connecté
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Fonction pour vérifier l'authentification admin
function checkAdminAuth()
{
    if (!isAdminLoggedIn()) {
        sendResponse(false, [], 'Non autorisé. Veuillez vous connecter.');
    }
}
