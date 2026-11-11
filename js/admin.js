// =============================================
// GREENBITE ADMIN - US 8-12
// =============================================

let currentOrderId = null;
let currentPlatId = null;
let allOrders = [];
let allPlats = [];

// =============================================
// AUTHENTIFICATION
// =============================================
async function checkAuth() {
    try {
        const response = await fetch('../php/auth.php?action=check');
        const data = await response.json();
        
        if (!data.authenticated) {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Erreur:', error);
        window.location.href = 'login.html';
    }
}

async function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        try {
            await fetch('../php/auth.php?action=logout');
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Erreur:', error);
        }
    }
}

// =============================================
// US#8, 9, 10: GESTION DES PLATS
// =============================================

/**
 * Charger tous les plats
 */
async function loadPlats(category = 'all') {
    try {
        const url = category === 'all' 
            ? '../php/plats.php?action=list' 
            : `../php/plats.php?action=list&category=${category}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allPlats = data.plats;
            displayPlats(allPlats);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des plats');
    }
}

/**
 * Afficher les plats dans la grille admin
 */
function displayPlats(plats) {
    const grid = document.getElementById('plats-grid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    if (plats.length === 0) {
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;padding:40px;">Aucun plat trouvé</p>';
        return;
    }
    
    plats.forEach(plat => {
        const card = createPlatCardAdmin(plat);
        grid.appendChild(card);
    });
}

/**
 * Créer une carte plat pour l'admin
 */
function createPlatCardAdmin(plat) {
    const card = document.createElement('div');
    card.className = 'plat-card';
    
    let badges = '';
    if (plat.sans_gluten) badges += '<span class="badge-small">Sans gluten</span> ';
    if (plat.sans_lactose) badges += '<span class="badge-small">Sans lactose</span> ';
    if (plat.bio) badges += '<span class="badge-small bio">Bio</span>';
    
    card.innerHTML = `
        <img src="../${plat.image}" alt="${plat.name}" onerror="this.src='../images/placeholder.jpg'">
        <div class="plat-card-content">
            <span class="plat-category">${plat.category}</span>
            <h3>${plat.name}</h3>
            ${badges}
            <p class="description">${plat.description.substring(0, 80)}...</p>
            <p class="plat-price">${plat.price} DT</p>
            <div class="plat-actions">
                <button class="btn-edit" onclick="editPlat(${plat.id})">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button class="btn-delete" onclick="deletePlat(${plat.id})">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    `;
    
    return card;
}

/**
 * Filtrer les plats par catégorie
 */
function filterPlats(category) {
    document.querySelectorAll('.filters .filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    if (category === 'all') {
        displayPlats(allPlats);
    } else {
        const filtered = allPlats.filter(plat => plat.category === category);
        displayPlats(filtered);
    }
}

/**
 * US#8: Afficher le modal pour ajouter un plat
 */
function showAddModal() {
    currentPlatId = null;
    document.getElementById('modal-title').textContent = 'Ajouter un plat';
    document.getElementById('plat-form').reset();
    document.getElementById('plat-id').value = '';
    document.getElementById('supplements-container').innerHTML = '';
    document.getElementById('accompagnements-container').innerHTML = '';
    document.getElementById('plat-modal').style.display = 'block';
}

/**
 * US#9: Modifier un plat existant
 */
async function editPlat(platId) {
    try {
        const response = await fetch(`../php/plats.php?action=get&id=${platId}`);
        const data = await response.json();
        
        if (data.success) {
            currentPlatId = platId;
            const plat = data.plat;
            
            document.getElementById('modal-title').textContent = 'Modifier le plat';
            document.getElementById('plat-id').value = plat.id;
            document.getElementById('plat-name').value = plat.name;
            document.getElementById('plat-category').value = plat.category;
            document.getElementById('plat-description').value = plat.description;
            document.getElementById('plat-price').value = plat.price;
            document.getElementById('plat-image').value = plat.image;
            document.getElementById('sans-gluten').checked = plat.sans_gluten;
            document.getElementById('sans-lactose').checked = plat.sans_lactose;
            document.getElementById('bio').checked = plat.bio;
            
            // Charger les suppléments
            const suppContainer = document.getElementById('supplements-container');
            suppContainer.innerHTML = '';
            if (plat.supplements && plat.supplements.length > 0) {
                plat.supplements.forEach(supp => {
                    addSupplementField(supp.name, supp.price);
                });
            }
            
            // Charger les accompagnements
            const accContainer = document.getElementById('accompagnements-container');
            accContainer.innerHTML = '';
            if (plat.accompagnements && plat.accompagnements.length > 0) {
                plat.accompagnements.forEach(acc => {
                    addAccompagnementField(acc.name, acc.price);
                });
            }
            
            document.getElementById('plat-modal').style.display = 'block';
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement du plat');
    }
}

/**
 * US#10: Supprimer un plat
 */
async function deletePlat(platId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce plat ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', platId);
    
    try {
        const response = await fetch('../php/plats.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadPlats();
            showNotification('🗑️ Plat supprimé avec succès !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}

/**
 * Fermer le modal des plats
 */
function closePlatModal() {
    document.getElementById('plat-modal').style.display = 'none';
    currentPlatId = null;
}

/**
 * Ajouter un champ de supplément
 */
function addSupplementField(name = '', price = '') {
    const container = document.getElementById('supplements-container');
    const div = document.createElement('div');
    div.className = 'supplement-field';
    div.innerHTML = `
        <input type="text" placeholder="Nom du supplément" value="${name}" class="supp-name">
        <input type="number" placeholder="Prix" step="0.01" value="${price}" class="supp-price">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

/**
 * Ajouter un champ d'accompagnement
 */
function addAccompagnementField(name = '', price = '') {
    const container = document.getElementById('accompagnements-container');
    const div = document.createElement('div');
    div.className = 'accompagnement-field';
    div.innerHTML = `
        <input type="text" placeholder="Nom de l'accompagnement" value="${name}" class="acc-name">
        <input type="number" placeholder="Prix" step="0.01" value="${price}" class="acc-price">
        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

/**
 * Soumettre le formulaire de plat (ajout ou modification)
 */
document.addEventListener('DOMContentLoaded', function() {
    const platForm = document.getElementById('plat-form');
    if (platForm) {
        platForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            await savePlat();
        });
    }
});

/**
 * US#8 & US#9: Sauvegarder un plat (ajout ou modification)
 */
async function savePlat() {
    const platId = document.getElementById('plat-id').value;
    const name = document.getElementById('plat-name').value;
    const category = document.getElementById('plat-category').value;
    const description = document.getElementById('plat-description').value;
    const price = document.getElementById('plat-price').value;
    const image = document.getElementById('plat-image').value;
    const sansGluten = document.getElementById('sans-gluten').checked;
    const sansLactose = document.getElementById('sans-lactose').checked;
    const bio = document.getElementById('bio').checked;
    
    // Récupérer les suppléments
    const supplements = [];
    document.querySelectorAll('.supplement-field').forEach(field => {
        const name = field.querySelector('.supp-name').value;
        const price = field.querySelector('.supp-price').value;
        if (name && price) {
            supplements.push({ name, price: parseFloat(price) });
        }
    });
    
    // Récupérer les accompagnements
    const accompagnements = [];
    document.querySelectorAll('.accompagnement-field').forEach(field => {
        const name = field.querySelector('.acc-name').value;
        const price = field.querySelector('.acc-price').value;
        if (name && price) {
            accompagnements.push({ name, price: parseFloat(price) });
        }
    });
    
    const formData = new FormData();
    formData.append('action', platId ? 'update' : 'add');
    if (platId) formData.append('id', platId);
    formData.append('name', name);
    formData.append('category', category);
    formData.append('description', description);
    formData.append('price', price);
    formData.append('image', image);
    if (sansGluten) formData.append('sans_gluten', '1');
    if (sansLactose) formData.append('sans_lactose', '1');
    if (bio) formData.append('bio', '1');
    formData.append('supplements', JSON.stringify(supplements));
    formData.append('accompagnements', JSON.stringify(accompagnements));
    
    try {
        const response = await fetch('../php/plats.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closePlatModal();
            loadPlats();
            showNotification(platId ? '✅ Plat modifié !' : '✅ Plat ajouté !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la sauvegarde');
    }
}

// =============================================
// US#11, 12: GESTION DES COMMANDES
// =============================================

/**
 * Charger toutes les commandes
 */
async function loadOrders(status = 'all') {
    try {
        const url = status === 'all' 
            ? '../php/commandes.php?action=list' 
            : `../php/commandes.php?action=list&status=${status}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allOrders = data.orders;
            displayOrders(allOrders);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des commandes');
    }
}

/**
 * Afficher les commandes dans le tableau
 */
function displayOrders(orders) {
    const tbody = document.getElementById('orders-list');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;">Aucune commande trouvée</td></tr>';
        return;
    }
    
    orders.forEach(order => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${order.id}</td>
            <td>${order.client_name}</td>
            <td>
                ${order.client_email ? '<i class="fas fa-envelope"></i> ' + order.client_email + '<br>' : ''}
                ${order.client_phone ? '<i class="fas fa-phone"></i> ' + order.client_phone : ''}
            </td>
            <td>${order.items_count} article(s)</td>
            <td><strong>${order.total} DT</strong></td>
            <td><span class="status-badge ${order.status}">${order.status_label}</span></td>
            <td>${order.date}</td>
            <td>
                <button class="btn-action" onclick="viewOrder(${order.id})">
                    <i class="fas fa-eye"></i> Voir
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Filtrer les commandes par statut
 */
function filterOrders(status) {
    document.querySelectorAll('.filters .filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    if (status === 'all') {
        displayOrders(allOrders);
    } else {
        const filtered = allOrders.filter(order => order.status === status);
        displayOrders(filtered);
    }
}

/**
 * Voir les détails d'une commande
 */
async function viewOrder(orderId) {
    try {
        const response = await fetch(`../php/commandes.php?action=get&id=${orderId}`);
        const data = await response.json();
        
        if (data.success) {
            currentOrderId = orderId;
            showOrderModal(data.order);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement de la commande');
    }
}

/**
 * Afficher le modal de détails de commande
 */
function showOrderModal(order) {
    document.getElementById('order-id').textContent = order.id;
    document.getElementById('order-client').textContent = order.client_name;
    document.getElementById('order-email').textContent = order.client_email || 'Non fourni';
    document.getElementById('order-phone').textContent = order.client_phone || 'Non fourni';
    document.getElementById('order-date').textContent = new Date(order.created_at).toLocaleString('fr-FR');
    document.getElementById('order-status').value = order.status;
    document.getElementById('order-notes').value = order.notes || '';
    document.getElementById('order-total').textContent = order.total;
    
    // Afficher les articles
    const itemsList = document.getElementById('order-items-list');
    itemsList.innerHTML = '';
    
    order.items.forEach(item => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'order-item-detail';
        
        let supplementsText = '';
        if (item.supplements && item.supplements.length > 0) {
            supplementsText = '<br><small><strong>Suppléments:</strong> ' + 
                item.supplements.map(s => s.name).join(', ') + '</small>';
        }
        
        let accompagnementText = '';
        if (item.accompagnement) {
            accompagnementText = '<br><small><strong>Accompagnement:</strong> ' + item.accompagnement + '</small>';
        }
        
        itemDiv.innerHTML = `
            <strong>${item.name}</strong> x ${item.quantity}
            ${supplementsText}
            ${accompagnementText}
            <div style="text-align:right;margin-top:10px;">
                <strong>${item.total} DT</strong>
            </div>
        `;
        itemsList.appendChild(itemDiv);
    });
    
    document.getElementById('order-modal').style.display = 'block';
}

/**
 * Fermer le modal de commande
 */
function closeOrderModal() {
    document.getElementById('order-modal').style.display = 'none';
    currentOrderId = null;
}

/**
 * US#12: Modifier une commande
 */
async function updateOrder() {
    const status = document.getElementById('order-status').value;
    const notes = document.getElementById('order-notes').value;
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', currentOrderId);
    formData.append('status', status);
    formData.append('notes', notes);
    
    try {
        const response = await fetch('../php/commandes.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeOrderModal();
            loadOrders();
            loadStats();
            showNotification('✅ Commande mise à jour !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour');
    }
}

/**
 * Charger les statistiques
 */
async function loadStats() {
    try {
        const response = await fetch('../php/commandes.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-orders').textContent = data.total;
            document.getElementById('pending-orders').textContent = data.pending;
            document.getElementById('total-revenue').textContent = data.revenue + ' DT';
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// =============================================
// US#11: AJOUTER UNE COMMANDE MANUELLEMENT
// =============================================

let availablePlats = [];
let orderItemsCounter = 0;

/**
 * Charger les plats disponibles pour la commande
 */
async function loadPlatsForOrder() {
    try {
        const response = await fetch('../php/plats.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            availablePlats = data.plats;
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

/**
 * Afficher le modal d'ajout de commande
 */
async function showAddOrderModal() {
    // Charger les plats d'abord
    await loadPlatsForOrder();
    
    // Réinitialiser le formulaire
    document.getElementById('add-order-form').reset();
    document.getElementById('order-items-container').innerHTML = '';
    orderItemsCounter = 0;
    
    // Ajouter un premier article
    addOrderItem();
    
    // Afficher le modal
    document.getElementById('add-order-modal').style.display = 'block';
    
    // Gérer la soumission du formulaire
    document.getElementById('add-order-form').onsubmit = async function(e) {
        e.preventDefault();
        await saveNewOrder();
    };
}

/**
 * Fermer le modal d'ajout de commande
 */
function closeAddOrderModal() {
    document.getElementById('add-order-modal').style.display = 'none';
}

/**
 * Ajouter un article à la commande
 */
function addOrderItem() {
    const container = document.getElementById('order-items-container');
    const itemId = orderItemsCounter++;
    
    const itemRow = document.createElement('div');
    itemRow.className = 'order-item-row';
    itemRow.id = `order-item-${itemId}`;
    
    // Créer la liste des plats
    let platsOptions = '<option value="">Sélectionner un plat...</option>';
    availablePlats.forEach(plat => {
        platsOptions += `<option value="${plat.id}" data-price="${plat.price}">${plat.name} - ${plat.price} DT</option>`;
    });
    
    itemRow.innerHTML = `
        <div class="form-group">
            <label>Plat</label>
            <select class="plat-select" data-item-id="${itemId}" onchange="updateOrderTotal()" required>
                ${platsOptions}
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantité</label>
            <input type="number" class="quantity-input" data-item-id="${itemId}" value="1" min="1" onchange="updateOrderTotal()" required>
        </div>
        
        <div class="form-group">
            <label>Prix unitaire</label>
            <input type="number" class="price-input" data-item-id="${itemId}" step="0.01" value="0" readonly>
        </div>
        
        <button type="button" class="btn-remove-item" onclick="removeOrderItem(${itemId})">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(itemRow);
    
    // Ajouter l'événement pour mettre à jour le prix
    const select = itemRow.querySelector('.plat-select');
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.dataset.price || 0;
        const priceInput = itemRow.querySelector('.price-input');
        priceInput.value = price;
        updateOrderTotal();
    });
}

/**
 * Supprimer un article de la commande
 */
function removeOrderItem(itemId) {
    const itemRow = document.getElementById(`order-item-${itemId}`);
    if (itemRow) {
        itemRow.remove();
        updateOrderTotal();
    }
}

/**
 * Mettre à jour le total de la commande
 */
function updateOrderTotal() {
    let total = 0;
    
    document.querySelectorAll('.order-item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        total += quantity * price;
    });
    
    // Ajouter frais de livraison
    total += 3;
    
    document.getElementById('new-order-total').textContent = total.toFixed(2);
}

/**
 * Sauvegarder la nouvelle commande
 */
async function saveNewOrder() {
    const clientName = document.getElementById('new-client-name').value.trim();
    const clientPhone = document.getElementById('new-client-phone').value.trim();
    const clientEmail = document.getElementById('new-client-email').value.trim();
    const notes = document.getElementById('new-order-notes').value.trim();
    
    // Validation
    if (!clientName) {
        alert('Le nom du client est requis');
        return;
    }
    
    if (!clientPhone) {
        alert('Le téléphone du client est requis');
        return;
    }
    
    // Collecter les articles
    const items = [];
    let hasItems = false;
    
    document.querySelectorAll('.order-item-row').forEach(row => {
        const select = row.querySelector('.plat-select');
        const platId = select.value;
        const platName = select.options[select.selectedIndex].text;
        const quantity = parseInt(row.querySelector('.quantity-input').value);
        const price = parseFloat(row.querySelector('.price-input').value);
        
        if (platId && quantity > 0) {
            hasItems = true;
            items.push({
                id: platId,
                name: platName.split(' - ')[0], // Enlever le prix du nom
                price: price,
                quantity: quantity,
                supplements: [],
                accompagnement: '',
                accompagnement_price: 0,
                total: (price * quantity).toFixed(2)
            });
        }
    });
    
    if (!hasItems) {
        alert('Veuillez ajouter au moins un article à la commande');
        return;
    }
    
    // Calculer le total
    let total = items.reduce((sum, item) => sum + parseFloat(item.total), 0);
    total += 3; // Frais de livraison
    
    // Préparer les données
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('client_name', clientName);
    formData.append('client_email', clientEmail);
    formData.append('client_phone', clientPhone);
    formData.append('items', JSON.stringify(items));
    formData.append('total', total.toFixed(2));
    formData.append('notes', notes);
    formData.append('status', 'en_attente');
    
    try {
        const response = await fetch('../php/commandes.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeAddOrderModal();
            loadOrders();
            loadStats();
            showNotification('✅ Commande créée avec succès ! #' + data.order_id);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la création de la commande');
    }
}

// =============================================
// UTILITAIRES
// =============================================
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(to right, #f12711, #f5af19);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}