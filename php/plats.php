<?php
// =============================================
// PLATS - US#7, 8, 9, 10
// =============================================
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        // US#7: Consulter le menu (visiteur)
        listPlats();
        break;
    case 'get':
        // Obtenir un plat spécifique
        getPlat();
        break;
    case 'add':
        // US#8: Ajouter un plat (admin)
        checkAdminAuth();
        addPlat();
        break;
    case 'update':
        // US#9: Modifier un plat (admin)
        checkAdminAuth();
        updatePlat();
        break;
    case 'delete':
        // US#10: Supprimer un plat (admin)
        checkAdminAuth();
        deletePlat();
        break;
    case 'by_category':
        listPlatsByCategory();
        break;
    default:
        sendResponse(false, [], 'Action invalide');
}

/**
 * US#7: Lister tous les plats (accessible aux visiteurs)
 */
function listPlats()
{
    global $pdo;

    $category = $_GET['category'] ?? '';

    try {
        if ($category && $category !== 'all') {
            $stmt = $pdo->prepare("SELECT * FROM plats WHERE category = ? ORDER BY name");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->query("SELECT * FROM plats ORDER BY category, name");
        }

        $plats = $stmt->fetchAll();

        // Décoder les JSON
        foreach ($plats as &$plat) {
            $plat['supplements'] = json_decode($plat['supplements'], true) ?? [];
            $plat['accompagnements'] = json_decode($plat['accompagnements'], true) ?? [];
            $plat['sans_gluten'] = (bool) $plat['sans_gluten'];
            $plat['sans_lactose'] = (bool) $plat['sans_lactose'];
            $plat['bio'] = (bool) $plat['bio'];
        }

        sendResponse(true, [
            'plats' => $plats,
            'count' => count($plats)
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de la récupération des plats: ' . $e->getMessage());
    }
}

/**
 * Obtenir les détails d'un plat spécifique
 */
function getPlat()
{
    global $pdo;

    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        sendResponse(false, [], 'ID du plat invalide');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM plats WHERE id = ?");
        $stmt->execute([$id]);
        $plat = $stmt->fetch();

        if ($plat) {
            // Décoder les JSON
            $plat['supplements'] = json_decode($plat['supplements'], true) ?? [];
            $plat['accompagnements'] = json_decode($plat['accompagnements'], true) ?? [];
            $plat['sans_gluten'] = (bool) $plat['sans_gluten'];
            $plat['sans_lactose'] = (bool) $plat['sans_lactose'];
            $plat['bio'] = (bool) $plat['bio'];

            sendResponse(true, ['plat' => $plat]);
        } else {
            sendResponse(false, [], 'Plat non trouvé');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}

/**
 * US#8: Ajouter un nouveau plat (admin uniquement)
 */
function addPlat()
{
    global $pdo;

    // Récupérer les données
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $sans_gluten = isset($_POST['sans_gluten']) ? 1 : 0;
    $sans_lactose = isset($_POST['sans_lactose']) ? 1 : 0;
    $bio = isset($_POST['bio']) ? 1 : 0;
    $supplements = $_POST['supplements'] ?? '[]';
    $accompagnements = $_POST['accompagnements'] ?? '[]';

    // Validation
    if (empty($name)) {
        sendResponse(false, [], 'Le nom du plat est requis');
    }

    if (empty($category)) {
        sendResponse(false, [], 'La catégorie est requise');
    }

    if ($price <= 0) {
        sendResponse(false, [], 'Le prix doit être supérieur à 0');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO plats (name, category, description, price, image, 
                             sans_gluten, sans_lactose, bio, supplements, accompagnements)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $category,
            $description,
            $price,
            $image,
            $sans_gluten,
            $sans_lactose,
            $bio,
            $supplements,
            $accompagnements
        ]);

        sendResponse(true, [
            'id' => $pdo->lastInsertId(),
            'message' => 'Plat ajouté avec succès'
        ]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de l\'ajout: ' . $e->getMessage());
    }
}

/**
 * US#9: Modifier un plat existant (admin uniquement)
 */
function updatePlat()
{
    global $pdo;

    // Récupérer les données
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image = trim($_POST['image'] ?? '');
    $sans_gluten = isset($_POST['sans_gluten']) ? 1 : 0;
    $sans_lactose = isset($_POST['sans_lactose']) ? 1 : 0;
    $bio = isset($_POST['bio']) ? 1 : 0;
    $supplements = $_POST['supplements'] ?? '[]';
    $accompagnements = $_POST['accompagnements'] ?? '[]';

    // Validation
    if ($id <= 0) {
        sendResponse(false, [], 'ID du plat invalide');
    }

    if (empty($name)) {
        sendResponse(false, [], 'Le nom du plat est requis');
    }

    if (empty($category)) {
        sendResponse(false, [], 'La catégorie est requise');
    }

    if ($price <= 0) {
        sendResponse(false, [], 'Le prix doit être supérieur à 0');
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE plats 
            SET name = ?, category = ?, description = ?, price = ?, image = ?,
                sans_gluten = ?, sans_lactose = ?, bio = ?, 
                supplements = ?, accompagnements = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $category,
            $description,
            $price,
            $image,
            $sans_gluten,
            $sans_lactose,
            $bio,
            $supplements,
            $accompagnements,
            $id
        ]);

        if ($stmt->rowCount() > 0) {
            sendResponse(true, ['message' => 'Plat modifié avec succès']);
        } else {
            sendResponse(false, [], 'Aucune modification effectuée ou plat non trouvé');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de la modification: ' . $e->getMessage());
    }
}

/**
 * US#10: Supprimer un plat (admin uniquement)
 */
function deletePlat()
{
    global $pdo;

    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

    // Validation
    if ($id <= 0) {
        sendResponse(false, [], 'ID du plat invalide');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM plats WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            sendResponse(true, ['message' => 'Plat supprimé avec succès']);
        } else {
            sendResponse(false, [], 'Plat non trouvé');
        }
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur lors de la suppression: ' . $e->getMessage());
    }
}

/**
 * Lister les catégories disponibles
 */
function listPlatsByCategory()
{
    global $pdo;

    try {
        $stmt = $pdo->query("
            SELECT category, COUNT(*) as count 
            FROM plats 
            GROUP BY category
        ");
        $categories = $stmt->fetchAll();

        sendResponse(true, ['categories' => $categories]);
    } catch (PDOException $e) {
        sendResponse(false, [], 'Erreur: ' . $e->getMessage());
    }
}
