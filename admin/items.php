<?php

/**
 * GESTION COMPLÈTE DES ITEMS (PLATS & BOISSONS)
 * CRUD complet : Create, Read, Update, Delete
 */

require_once __DIR__ . '/../config/config.php';
requireAdmin();

$page_title = 'Gestion des Items';
$error = '';
$success = '';

// ========================================
// SUPPRESSION D'UN ITEM
// ========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];

    try {
        // Vérifier si l'item existe
        $stmt = $pdo->prepare("SELECT nom FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if ($item) {
            // Supprimer l'item
            $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$item_id]);

            setMessage("✓ Item '{$item['nom']}' supprimé avec succès.");
        } else {
            setMessage("Item introuvable.", "error");
        }
    } catch (PDOException $e) {
        setMessage("Impossible de supprimer cet item (commandes ou paniers associés).", "error");
    }

    redirect('items.php');
}

// ========================================
// AJOUT/MODIFICATION D'UN ITEM
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nom = sanitize($_POST['nom']);
    $description = sanitize($_POST['description']);
    $prix = (float)$_POST['prix'];
    $categorie_id = (int)$_POST['categorie_id'];
    $type = sanitize($_POST['type']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;

    // Validation
    if (empty($nom)) {
        $error = "Le nom est obligatoire.";
    } elseif ($prix <= 0) {
        $error = "Le prix doit être supérieur à 0.";
    } elseif (!in_array($type, ['plat', 'boisson'])) {
        $error = "Type invalide.";
    } else {
        try {
            if ($id > 0) {
                // MODIFICATION
                $stmt = $pdo->prepare("
                    UPDATE items 
                    SET nom = ?, description = ?, prix = ?, categorie_id = ?, type = ?, disponible = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $description, $prix, $categorie_id, $type, $disponible, $id]);

                setMessage("✓ Item modifié avec succès.");
            } else {
                // AJOUT
                $stmt = $pdo->prepare("
                    INSERT INTO items (nom, description, prix, categorie_id, type, disponible)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nom, $description, $prix, $categorie_id, $type, $disponible]);

                setMessage("✓ Nouvel item ajouté avec succès.");
            }

            redirect('items.php');
        } catch (PDOException $e) {
            $error = "Erreur lors de l'opération : " . $e->getMessage();
        }
    }
}

// ========================================
// RÉCUPÉRATION DES DONNÉES
// ========================================

// Catégories pour le select
$categories = $pdo->query("SELECT * FROM categories ORDER BY ordre, nom")->fetchAll();

// Liste de tous les items
$stmt = $pdo->query("
    SELECT i.*, c.nom as categorie_nom 
    FROM items i 
    LEFT JOIN categories c ON i.categorie_id = c.id 
    ORDER BY i.type, i.disponible DESC, i.nom
");
$items = $stmt->fetchAll();

// Item à modifier (si edit)
$item_edit = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $item_edit = $stmt->fetch();
}

// Statistiques
$stats = [
    'total' => count($items),
    'plats' => count(array_filter($items, fn($i) => $i['type'] === 'plat')),
    'boissons' => count(array_filter($items, fn($i) => $i['type'] === 'boisson')),
    'disponibles' => count(array_filter($items, fn($i) => $i['disponible'] == 1)),
];

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-color);
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-box {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: var(--shadow);
    }

    .stat-box h4 {
        margin: 0 0 0.5rem 0;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .stat-box .value {
        font-size: 2rem;
        font-weight: bold;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-color);
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .close-modal:hover {
        color: var(--danger-color);
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .items-table {
        width: 100%;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .items-table th {
        background: var(--primary-color);
        color: white;
        padding: 1rem;
        text-align: left;
    }

    .items-table td {
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }

    .items-table tr:hover {
        background: #f8f9fa;
    }

    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .badge-plat {
        background: #e3f2fd;
        color: #1976d2;
    }

    .badge-boisson {
        background: #fff3e0;
        color: #f57c00;
    }

    .badge-disponible {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-indisponible {
        background: #ffebee;
        color: #c62828;
    }
</style>

<main class="admin-container">
    <!-- En-tête -->
    <div class="admin-header">
        <div>
            <h1>🍽️ Gestion des Plats & Boissons</h1>
            <p style="color: #666; margin: 0.5rem 0 0 0;">Ajoutez, modifiez ou supprimez vos items</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">← Dashboard</a>
            <button onclick="openModal()" class="btn btn-primary">+ Nouvel Item</button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-box">
            <h4>Total Items</h4>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-box">
            <h4>Plats</h4>
            <div class="value"><?= $stats['plats'] ?></div>
        </div>
        <div class="stat-box">
            <h4>Boissons</h4>
            <div class="value"><?= $stats['boissons'] ?></div>
        </div>
        <div class="stat-box">
            <h4>Disponibles</h4>
            <div class="value"><?= $stats['disponibles'] ?></div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tableau des items -->
    <table class="items-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Statut</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: #999;">
                        Aucun item pour le moment. Cliquez sur "Nouvel Item" pour commencer.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong>#<?= $item['id'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($item['nom']) ?></strong>
                            <?php if ($item['description']): ?>
                                <br><small style="color: #666;"><?= htmlspecialchars(substr($item['description'], 0, 60)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $item['type'] ?>">
                                <?= $item['type'] === 'plat' ? '🍽️ Plat' : '🥤 Boisson' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($item['categorie_nom'] ?? '-') ?></td>
                        <td><strong><?= formatPrice($item['prix']) ?></strong></td>
                        <td>
                            <?php if ($item['disponible']): ?>
                                <span class="badge badge-disponible">✓ Disponible</span>
                            <?php else: ?>
                                <span class="badge badge-indisponible">✗ Indisponible</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <button onclick="editItem(<?= $item['id'] ?>)" class="btn btn-secondary btn-sm">
                                    ✏️ Modifier
                                </button>
                                <button onclick="deleteItem(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nom']) ?>')"
                                    class="btn btn-danger btn-sm">
                                    🗑️ Supprimer
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</main>

<!-- Modal Formulaire -->
<div id="itemModal" class="modal <?= $item_edit ? 'active' : '' ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?= $item_edit ? '✏️ Modifier l\'item' : '➕ Nouvel item' ?></h2>
            <button class="close-modal" onclick="closeModal()">×</button>
        </div>

        <form method="POST" id="itemForm">
            <?php if ($item_edit): ?>
                <input type="hidden" name="id" value="<?= $item_edit['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nom">Nom de l'item *</label>
                <input type="text"
                    id="nom"
                    name="nom"
                    class="form-control"
                    value="<?= $item_edit ? htmlspecialchars($item_edit['nom']) : '' ?>"
                    required
                    placeholder="Ex: Salade Caesar Healthy">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description"
                    name="description"
                    class="form-control"
                    rows="3"
                    placeholder="Décrivez brièvement l'item..."><?= $item_edit ? htmlspecialchars($item_edit['description']) : '' ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="type">Type *</label>
                    <select id="type" name="type" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="plat" <?= $item_edit && $item_edit['type'] === 'plat' ? 'selected' : '' ?>>
                            🍽️ Plat
                        </option>
                        <option value="boisson" <?= $item_edit && $item_edit['type'] === 'boisson' ? 'selected' : '' ?>>
                            🥤 Boisson
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prix">Prix (€) *</label>
                    <input type="number"
                        id="prix"
                        name="prix"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        value="<?= $item_edit ? $item_edit['prix'] : '' ?>"
                        required
                        placeholder="12.50">
                </div>
            </div>

            <div class="form-group">
                <label for="categorie_id">Catégorie</label>
                <select id="categorie_id" name="categorie_id" class="form-control">
                    <option value="0">Sans catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"
                            <?= $item_edit && $item_edit['categorie_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox"
                        name="disponible"
                        style="margin-right: 0.5rem;"
                        <?= !$item_edit || $item_edit['disponible'] ? 'checked' : '' ?>>
                    <span>✓ Disponible à la vente</span>
                </label>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <?= $item_edit ? '💾 Enregistrer' : '➕ Ajouter' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('itemModal').classList.add('active');
        document.getElementById('nom').focus();
    }

    function closeModal() {
        if (!confirm('Fermer sans enregistrer ?')) return;
        window.location.href = 'items.php';
    }

    function editItem(id) {
        window.location.href = 'items.php?action=edit&id=' + id;
    }

    function deleteItem(id, nom) {
        if (confirm(`Voulez-vous vraiment supprimer "${nom}" ?\n\nCette action est irréversible.`)) {
            window.location.href = 'items.php?action=delete&id=' + id;
        }
    }

    // Fermer modal avec ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('itemModal');
            if (modal.classList.contains('active')) {
                closeModal();
            }
        }
    });

    // Fermer modal en cliquant à l'extérieur
    document.getElementById('itemModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>