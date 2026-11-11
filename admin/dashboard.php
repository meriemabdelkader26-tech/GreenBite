<?php

/**
 * DASHBOARD ADMINISTRATEUR COMPLET
 * Vue d'ensemble avec graphiques et actions rapides
 */

require_once __DIR__ . '/../config/config.php';
requireAdmin();

$page_title = 'Dashboard Administrateur';

// ========================================
// STATISTIQUES GLOBALES
// ========================================

// Commandes
$stats_commandes = [
    'total' => $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn(),
    'aujourdhui' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'en_attente' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'")->fetchColumn(),
    'en_preparation' => $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_preparation'")->fetchColumn(),
];

// Items
$stats_items = [
    'total' => $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn(),
    'plats' => $pdo->query("SELECT COUNT(*) FROM items WHERE type = 'plat'")->fetchColumn(),
    'boissons' => $pdo->query("SELECT COUNT(*) FROM items WHERE type = 'boisson'")->fetchColumn(),
    'disponibles' => $pdo->query("SELECT COUNT(*) FROM items WHERE disponible = 1")->fetchColumn(),
];

// Clients
$stats_clients = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn(),
    'nouveaux_mois' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(),
];

// Revenus
$revenus = [
    'total' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee'")->fetchColumn(),
    'mois' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(),
    'jour' => $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut != 'annulee' AND DATE(created_at) = CURDATE()")->fetchColumn(),
];

// Commandes récentes
$commandes_recentes = $pdo->query("
    SELECT c.*, u.nom as client_nom
    FROM commandes c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetchAll();

// Items populaires
$items_populaires = $pdo->query("
    SELECT i.nom, i.type, COUNT(cd.id) as nb_commandes, SUM(cd.quantite) as total_vendus
    FROM items i
    JOIN commande_details cd ON i.id = cd.item_id
    GROUP BY i.id
    ORDER BY total_vendus DESC
    LIMIT 5
")->fetchAll();

// Graphique des commandes par jour (7 derniers jours)
$graphique_commandes = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as nb
    FROM commandes
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .dashboard-welcome {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
    }

    .dashboard-welcome h1 {
        margin: 0 0 0.5rem 0;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border-left: 4px solid var(--primary-color);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    .stat-card.warning {
        border-left-color: #FFA500;
    }

    .stat-card.danger {
        border-left-color: #e74c3c;
    }

    .stat-card.success {
        border-left-color: #27ae60;
    }

    .stat-card.info {
        border-left-color: #3498db;
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .stat-icon {
        font-size: 2rem;
        opacity: 0.7;
    }

    .stat-label {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--dark-color);
        margin: 0.5rem 0;
    }

    .stat-footer {
        color: #999;
        font-size: 0.85rem;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #eee;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quick-action {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        text-decoration: none;
        color: var(--dark-color);
        border: 2px solid transparent;
    }

    .quick-action:hover {
        border-color: var(--primary-color);
        transform: translateY(-3px);
    }

    .quick-action-icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .panel {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: var(--shadow);
    }

    .panel h3 {
        margin: 0 0 1rem 0;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-color);
    }

    .recent-order {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        transition: var(--transition);
    }

    .recent-order:hover {
        background: #f8f9fa;
    }

    .recent-order:last-child {
        border-bottom: none;
    }

    .popular-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .popular-item:last-child {
        border-bottom: none;
    }

    .chart-container {
        height: 200px;
        margin-top: 1rem;
    }

    @media (max-width: 968px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="admin-container">
    <!-- Bienvenue -->
    <div class="dashboard-welcome">
        <h1>👋 Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?></h1>
        <p>Tableau de bord administrateur - <?= date('d/m/Y à H:i') ?></p>
    </div>

    <!-- Statistiques principales -->
    <div class="stats-grid">
        <!-- Commandes du jour -->
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Commandes Aujourd'hui</p>
                    <div class="stat-value"><?= $stats_commandes['aujourdhui'] ?></div>
                </div>
                <div class="stat-icon">📅</div>
            </div>
            <div class="stat-footer">
                Total: <?= $stats_commandes['total'] ?> commandes
            </div>
        </div>

        <!-- En attente -->
        <div class="stat-card danger">
            <div class="stat-header">
                <div>
                    <p class="stat-label">À Traiter</p>
                    <div class="stat-value"><?= $stats_commandes['en_attente'] ?></div>
                </div>
                <div class="stat-icon">⏳</div>
            </div>
            <div class="stat-footer">
                En préparation: <?= $stats_commandes['en_preparation'] ?>
            </div>
        </div>

        <!-- Items disponibles -->
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Items Disponibles</p>
                    <div class="stat-value"><?= $stats_items['disponibles'] ?></div>
                </div>
                <div class="stat-icon">🍽️</div>
            </div>
            <div class="stat-footer">
                <?= $stats_items['plats'] ?> plats · <?= $stats_items['boissons'] ?> boissons
            </div>
        </div>

        <!-- Clients -->
        <div class="stat-card info">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Clients Inscrits</p>
                    <div class="stat-value"><?= $stats_clients['total'] ?></div>
                </div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-footer">
                +<?= $stats_clients['nouveaux_mois'] ?> ce mois
            </div>
        </div>

        <!-- Revenu du jour -->
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Revenu Aujourd'hui</p>
                    <div class="stat-value"><?= number_format($revenus['jour'], 0) ?> €</div>
                </div>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-footer">
                Ce mois: <?= formatPrice($revenus['mois']) ?>
            </div>
        </div>

        <!-- Revenu total -->
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Revenu Total</p>
                    <div class="stat-value"><?= number_format($revenus['total'], 0) ?> €</div>
                </div>
                <div class="stat-icon">📈</div>
            </div>
            <div class="stat-footer">
                Chiffre d'affaires global
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <h3 style="margin-bottom: 1rem;">⚡ Actions Rapides</h3>
    <div class="quick-actions">
        <a href="items.php" class="quick-action">
            <div class="quick-action-icon">🍽️</div>
            <strong>Gérer les Items</strong>
        </a>
        <a href="commandes.php" class="quick-action">
            <div class="quick-action-icon">📦</div>
            <strong>Gérer les Commandes</strong>
        </a>
        <a href="commandes.php?statut=en_attente" class="quick-action">
            <div class="quick-action-icon">⏳</div>
            <strong>Commandes en Attente</strong>
        </a>
        <a href="../public/index.php" class="quick-action">
            <div class="quick-action-icon">🌐</div>
            <strong>Voir le Site</strong>
        </a>
    </div>

    <!-- Contenu principal -->
    <div class="content-grid">
        <!-- Commandes récentes -->
        <div class="panel">
            <h3>📦 Dernières Commandes</h3>
            <?php if (empty($commandes_recentes)): ?>
                <p style="text-align: center; color: #999; padding: 2rem;">Aucune commande</p>
            <?php else: ?>
                <?php foreach ($commandes_recentes as $cmd): ?>
                    <?php $statut = getStatutLabel($cmd['statut']); ?>
                    <div class="recent-order">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>#<?= $cmd['id'] ?> - <?= htmlspecialchars($cmd['client_nom']) ?></strong><br>
                                <small style="color: #666;">
                                    <?= date('d/m/Y H:i', strtotime($cmd['created_at'])) ?>
                                </small>
                            </div>
                            <div style="text-align: right;">
                                <div style="margin-bottom: 0.25rem;">
                                    <span style="background: <?= $statut['color'] ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;">
                                        <?= $statut['label'] ?>
                                    </span>
                                </div>
                                <strong><?= formatPrice($cmd['total']) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="commandes.php" class="btn btn-primary btn-sm">Voir toutes les commandes →</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items populaires -->
        <div class="panel">
            <h3>🏆 Items les Plus Vendus</h3>
            <?php if (empty($items_populaires)): ?>
                <p style="text-align: center; color: #999; padding: 2rem;">Aucune vente</p>
            <?php else: ?>
                <?php foreach ($items_populaires as $item): ?>
                    <div class="popular-item">
                        <div>
                            <strong><?= htmlspecialchars($item['nom']) ?></strong><br>
                            <small style="color: #666;">
                                <?= $item['type'] === 'plat' ? '🍽️ Plat' : '🥤 Boisson' ?>
                            </small>
                        </div>
                        <div style="text-align: right;">
                            <strong><?= $item['total_vendus'] ?></strong> vendus<br>
                            <small style="color: #666;"><?= $item['nb_commandes'] ?> commandes</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Graphique des commandes -->
    <div class="panel" style="margin-top: 2rem;">
        <h3>📊 Commandes des 7 derniers jours</h3>
        <div class="chart-container">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Graphique des commandes
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                <?php foreach ($graphique_commandes as $data): ?> '<?= date('d/m', strtotime($data['date'])) ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Commandes',
                data: [
                    <?php foreach ($graphique_commandes as $data): ?>
                        <?= $data['nb'] ?>,
                    <?php endforeach; ?>
                ],
                borderColor: 'rgb(76, 175, 80)',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>