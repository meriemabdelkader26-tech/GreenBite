<?php
// =============================================
// PANIER - US#3, 4, 5, 6
// =============================================
require_once 'config.php';

// Initialiser le panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // US#3: Ajouter au panier
        addToCart();
        break;
    case 'get':
        // US#4: Consulter le panier
        getCart();
        break;
    case 'update':
        // US#5: Modifier un article
        updateCartItem();
        break;
    case 'delete':
        // US#6: Supprimer un article
        deleteCartItem();
        break;
    case 'clear':
        clearCart();
        break;
    case 'count':
        getCartCount();
        break;
    default:
        sendResponse(false, [], 'Action invalide');
}

/**
 * US#3: Ajouter un article au panier
 */
function addToCart()
{
    global $pdo;

    // Récupérer les données
    $platId = intval($_POST['plat_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $supplements = json_decode($_POST['supplements'] ?? '[]', true) ?: [];
    $accompagnement = $_POST['accompagnement'] ?? '';
    $accompagnement_price = floatval($_POST['accompagnement_price'] ?? 0);

    // Validation
    if ($platId <= 0) {
        sendResponse(false, [], 'ID du plat invalide');
    }

    if ($quantity <= 0) {
        sendResponse(false, [], 'Quantité invalide');
    }

    try {
        // Récupérer le plat depuis la BDD
        $stmt = $pdo->prepare("SELECT * FROM plats WHERE id = ?");
        $stmt->execute([$platId]);
        $plat = $stmt->fetch();

        if (!$plat) {
            sendResponse(false, [], 'Plat non trouvé');
        }

        // Calculer le prix total
        $basePrice = floatval($plat['price']);
        $supplementsPrice = 0;

        foreach ($supplements as $supplement) {
            $supplementsPrice += floatval($supplement['price'] ?? 0);
        }

        $itemTotal = ($basePrice + $supplementsPrice + $accompagnement_price) * $quantity;

        // Créer l'item du panier
        $cartItem = [
            'id' => uniqid('cart_', true),
            'plat_id' => $platId,
            'name' => $plat['name'],
            'price' => $basePrice,
            'image' => $plat['image'],
            'quantity' => $quantity,
            'supplements' => $supplements,
            'accompagnement' => $accompagnement,
            'accompagnement_price' => $accompagnement_price,
            'sans_gluten' => (bool) $plat['sans_gluten'],
            'sans_lactose' => (bool) $plat['sans_lactose'],
            'bio' => (bool) $plat['bio'],
            'total' => round($itemTotal, 2)
        ];

        // Ajouter au panier dans la session
        $_SESSION['cart'][] = $cartItem;

        // Réponse
        sendResponse(true, [
            'cart_item' => $cartItem,
            'cart_count' => count($_SESSION['cart']),
            'message' => 'Article ajouté au panier avec succès'
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur base de données: ' . $e->getMessage());
    }
}

/**
 * US#4: Consulter le panier
 */
function getCart()
{
    $cart = $_SESSION['cart'] ?? [];

    // Calculer le total
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += floatval($item['total']);
    }

    $delivery = 3.00; // Frais de livraison
    $total = $subtotal + $delivery;

    sendResponse(true, [
        'cart' => $cart,
        'count' => count($cart),
        'subtotal' => round($subtotal, 2),
        'delivery' => $delivery,
        'total' => round($total, 2)
    ]);
}

/**
 * US#5: Modifier un article du panier
 */
function updateCartItem()
{
    $cartItemId = $_POST['cart_item_id'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    $supplements = json_decode($_POST['supplements'] ?? '[]', true) ?: [];
    $accompagnement = $_POST['accompagnement'] ?? '';
    $accompagnement_price = floatval($_POST['accompagnement_price'] ?? 0);

    // Validation
    if (empty($cartItemId)) {
        sendResponse(false, [], 'ID de l\'article requis');
    }

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        sendResponse(false, [], 'Panier vide');
    }

    if ($quantity <= 0) {
        sendResponse(false, [], 'Quantité invalide');
    }

    // Chercher et modifier l'article
    $found = false;

    foreach ($_SESSION['cart'] as $key => &$item) {
        if ($item['id'] === $cartItemId) {
            // Recalculer le prix
            $basePrice = floatval($item['price']);
            $supplementsPrice = 0;

            foreach ($supplements as $supplement) {
                $supplementsPrice += floatval($supplement['price'] ?? 0);
            }

            $itemTotal = ($basePrice + $supplementsPrice + $accompagnement_price) * $quantity;

            // Mettre à jour l'article
            $item['quantity'] = $quantity;
            $item['supplements'] = $supplements;
            $item['accompagnement'] = $accompagnement;
            $item['accompagnement_price'] = $accompagnement_price;
            $item['total'] = round($itemTotal, 2);

            $found = true;
            break;
        }
    }

    if ($found) {
        sendResponse(true, [
            'cart' => $_SESSION['cart'],
            'count' => count($_SESSION['cart']),
            'message' => 'Article modifié avec succès'
        ]);
    } else {
        sendResponse(false, [], 'Article non trouvé dans le panier');
    }
}

/**
 * US#6: Supprimer un article du panier
 */
function deleteCartItem()
{
    $cartItemId = $_POST['cart_item_id'] ?? $_GET['cart_item_id'] ?? '';

    // Validation
    if (empty($cartItemId)) {
        sendResponse(false, [], 'ID de l\'article requis');
    }

    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        sendResponse(false, [], 'Panier déjà vide');
    }

    // Compter avant suppression
    $initialCount = count($_SESSION['cart']);

    // Filtrer pour supprimer l'article
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($cartItemId) {
        return $item['id'] !== $cartItemId;
    });

    // Réindexer le tableau
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    // Vérifier si suppression réussie
    if (count($_SESSION['cart']) < $initialCount) {
        sendResponse(true, [
            'cart' => $_SESSION['cart'],
            'count' => count($_SESSION['cart']),
            'message' => 'Article supprimé du panier'
        ]);
    } else {
        sendResponse(false, [], 'Article non trouvé dans le panier');
    }
}

/**
 * Vider complètement le panier
 */
function clearCart()
{
    $_SESSION['cart'] = [];
    sendResponse(true, [
        'message' => 'Panier vidé',
        'count' => 0
    ]);
}

/**
 * Obtenir uniquement le nombre d'articles
 */
function getCartCount()
{
    $count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    sendResponse(true, ['count' => $count]);
}
