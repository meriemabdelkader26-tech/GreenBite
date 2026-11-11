<?php
// =============================================
// COMMANDES - US#2, 11, 12
// =============================================
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        // US#2: Créer une commande (client)
        createOrder();
        break;
    case 'list':
        // US#11: Lister les commandes (admin)
        checkAdminAuth();
        listOrders();
        break;
    case 'get':
        // Obtenir une commande
        checkAdminAuth();
        getOrder();
        break;
    case 'add':
        // US#11: Ajouter une commande (admin)
        checkAdminAuth();
        addOrder();
        break;
    case 'update':
        // US#12: Modifier une commande (admin)
        checkAdminAuth();
        updateOrder();
        break;
    case 'delete':
        checkAdminAuth();
        deleteOrder();
        break;
    case 'stats':
        checkAdminAuth();
        getStats();
        break;
    default:
        sendResponse(false, [], 'Action invalide');
}

/**
 * US#2: Créer une commande (client finalise son achat)
 */
function createOrder()
{
    global $pdo;

    $clientName = trim($_POST['client_name'] ?? '');
    $clientEmail = trim($_POST['client_email'] ?? '');
    $clientPhone = trim($_POST['client_phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($clientName)) {
        sendResponse(false, [], 'Le nom du client est requis');
    }

    // Récupérer le panier
    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        sendResponse(false, [], 'Le panier est vide');
    }

    // Calculer le total
    $total = 0;
    foreach ($cart as $item) {
        $total += floatval($item['total']);
    }
    $total += 3; // Frais de livraison

    try {
        $stmt = $pdo->prepare("
            INSERT INTO commandes (client_name, client_email, client_phone, items, total, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, 'en_attente')
        ");

        $stmt->execute([
            $clientName,
            $clientEmail,
            $clientPhone,
            json_encode($cart, JSON_UNESCAPED_UNICODE),
            $total,
            $notes
        ]);

        $orderId = $pdo->lastInsertId();

        // Vider le panier après commande réussie
        $_SESSION['cart'] = [];

        sendResponse(true, [
            'order_id' => $orderId,
            'total' => $total,
            'message' => 'Commande créée avec succès'
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de la création: ' . $e->getMessage());
    }
}

/**
 * US#11: Lister toutes les commandes (admin)
 */
function listOrders()
{
    global $pdo;

    $status = $_GET['status'] ?? '';
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);

    try {
        if ($status && $status !== 'all') {
            $stmt = $pdo->prepare("
                SELECT id, client_name, client_email, client_phone, total, status, 
                       notes, created_at, updated_at,
                       JSON_LENGTH(items) as items_count
                FROM commandes 
                WHERE status = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$status, $limit, $offset]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, client_name, client_email, client_phone, total, status, 
                       notes, created_at, updated_at,
                       JSON_LENGTH(items) as items_count
                FROM commandes 
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
        }

        $orders = $stmt->fetchAll();

        // Formater les données
        $formattedOrders = [];
        $statusLabels = [
            'en_attente' => 'En attente',
            'en_preparation' => 'En préparation',
            'en_livraison' => 'En livraison',
            'livree' => 'Livrée',
            'annulee' => 'Annulée'
        ];

        foreach ($orders as $order) {
            $formattedOrders[] = [
                'id' => $order['id'],
                'client_name' => $order['client_name'],
                'client_email' => $order['client_email'],
                'client_phone' => $order['client_phone'],
                'items_count' => $order['items_count'],
                'total' => number_format($order['total'], 2),
                'status' => $order['status'],
                'status_label' => $statusLabels[$order['status']] ?? $order['status'],
                'notes' => $order['notes'],
                'created_at' => $order['created_at'],
                'updated_at' => $order['updated_at'],
                'date' => date('d/m/Y H:i', strtotime($order['created_at']))
            ];
        }

        sendResponse(true, [
            'orders' => $formattedOrders,
            'count' => count($formattedOrders)
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * Obtenir une commande spécifique
 */
function getOrder()
{
    global $pdo;

    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        sendResponse(false, [], 'ID de commande invalide');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = json_decode($order['items'], true);
            sendResponse(true, ['order' => $order]);
        } else {
            sendResponse(false, [], 'Commande non trouvée');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * US#11: Ajouter une commande manuellement (admin)
 */
function addOrder()
{
    global $pdo;

    $clientName = trim($_POST['client_name'] ?? '');
    $clientEmail = trim($_POST['client_email'] ?? '');
    $clientPhone = trim($_POST['client_phone'] ?? '');
    $items = $_POST['items'] ?? '[]';
    $total = floatval($_POST['total'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'en_attente';

    // Validation
    if (empty($clientName)) {
        sendResponse(false, [], 'Le nom du client est requis');
    }

    if ($total <= 0) {
        sendResponse(false, [], 'Le total doit être supérieur à 0');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO commandes (client_name, client_email, client_phone, items, total, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $clientName,
            $clientEmail,
            $clientPhone,
            $items,
            $total,
            $notes,
            $status
        ]);

        sendResponse(true, [
            'order_id' => $pdo->lastInsertId(),
            'message' => 'Commande ajoutée avec succès'
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * US#12: Modifier une commande (admin)
 */
function updateOrder()
{
    global $pdo;

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Validation
    if ($id <= 0) {
        sendResponse(false, [], 'ID de commande invalide');
    }

    $validStatuses = ['en_attente', 'en_preparation', 'en_livraison', 'livree', 'annulee'];

    if ($status && !in_array($status, $validStatuses)) {
        sendResponse(false, [], 'Statut invalide');
    }

    try {
        // Construire la requête dynamiquement
        $updates = [];
        $params = [];

        if ($status !== null) {
            $updates[] = "status = ?";
            $params[] = $status;
        }

        if ($notes !== null) {
            $updates[] = "notes = ?";
            $params[] = $notes;
        }

        if (empty($updates)) {
            sendResponse(false, [], 'Aucune modification spécifiée');
        }

        $params[] = $id;

        $sql = "UPDATE commandes SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            sendResponse(true, ['message' => 'Commande mise à jour avec succès']);
        } else {
            sendResponse(false, [], 'Aucune modification effectuée ou commande non trouvée');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * Supprimer une commande (admin)
 */
function deleteOrder()
{
    global $pdo;

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        sendResponse(false, [], 'ID de commande invalide');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendResponse(true, ['message' => 'Commande supprimée avec succès']);
        } else {
            sendResponse(false, [], 'Commande non trouvée');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * Obtenir les statistiques (admin)
 */
function getStats()
{
    global $pdo;

    try {
        // Total des commandes
        $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM commandes");
        $total = $totalStmt->fetch()['total'];

        // Commandes en attente
        $pendingStmt = $pdo->query("SELECT COUNT(*) as pending FROM commandes WHERE status = 'en_attente'");
        $pending = $pendingStmt->fetch()['pending'];

        // Revenu total (hors annulées)
        $revenueStmt = $pdo->query("SELECT SUM(total) as revenue FROM commandes WHERE status != 'annulee'");
        $revenue = $revenueStmt->fetch()['revenue'] ?? 0;

        // Par statut
        $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM commandes GROUP BY status");
        $byStatus = $statusStmt->fetchAll();

        sendResponse(true, [
            'total' => $total,
            'pending' => $pending,
            'revenue' => number_format($revenue, 2),
            'by_status' => $byStatus
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}
