// =============================================
// GREENBITE - JAVASCRIPT CLIENT (US 3-7)
// =============================================

let cart = [];
let currentPlat = null;
let editingCartItemId = null;

// =============================================
// INITIALISATION
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    // US#7: Charger le menu au démarrage
    if (document.getElementById('recipies')) {
        loadPlats();
    }
    
    // US#4: Charger le panier
    loadCartFromServer();
    
    // Page panier
    if (document.getElementById('cart-items')) {
        displayCart();
    }
    
    setupEventListeners();
});

// =============================================
// US#7: CONSULTER LE MENU (Visiteur)
// =============================================
async function loadPlats(category = 'all') {
    try {
        const url = category === 'all' 
            ? 'php/plats.php?action=list' 
            : `php/plats.php?action=list&category=${category}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayPlats(data.plats);
        } else {
            console.error('Erreur:', data.message);
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
}

function displayPlats(plats) {
    const container = document.querySelector('#recipies .cards');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (plats.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:#888;">Aucun plat disponible</p>';
        return;
    }
    
    plats.forEach(plat => {
        const card = createPlatCard(plat);
        container.appendChild(card);
    });
}

function createPlatCard(plat) {
    const card = document.createElement('div');
    card.className = 'card';
    
    // Badges
    let badges = '<div class="badges">';
    if (plat.sans_gluten) badges += '<span class="badge">Sans gluten ✓</span>';
    if (plat.sans_lactose) badges += '<span class="badge">Sans lactose ✓</span>';
    if (plat.bio) badges += '<span class="badge bio">Bio 🌱</span>';
    badges += '</div>';
    
    card.innerHTML = `
        <img src="${plat.image}" alt="${plat.name}" onerror="this.src='images/placeholder.jpg'">
        ${badges}
        <h5>${plat.name}</h5>
        <div class="rating-stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
        </div>
        <p class="prix">${plat.price} DT</p>
        <button class="btn-commander" onclick="openCustomizationModal(${plat.id})">
            Personnaliser & Commander
        </button>
    `;
    
    return card;
}

// =============================================
// US#1: PERSONNALISER UN REPAS
// =============================================
async function openCustomizationModal(platId) {
    try {
        const response = await fetch(`php/plats.php?action=get&id=${platId}`);
        const data = await response.json();
        
        if (data.success) {
            currentPlat = data.plat;
            showCustomizationModal(currentPlat);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Impossible de charger le plat');
    }
}

function showCustomizationModal(plat) {
    let modal = document.getElementById('customization-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customization-modal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    let badges = '';
    if (plat.sans_gluten) badges += '<span class="badge-modal">Sans gluten ✓</span>';
    if (plat.sans_lactose) badges += '<span class="badge-modal">Sans lactose ✓</span>';
    if (plat.bio) badges += '<span class="badge-modal bio">Bio 🌱</span>';
    
    let supplementsHtml = '';
    if (plat.supplements && plat.supplements.length > 0) {
        supplementsHtml = '<h3>Choisissez vos suppléments :</h3><div class="supplements-list">';
        plat.supplements.forEach((supp, index) => {
            supplementsHtml += `
                <label class="supplement-item">
                    <input type="checkbox" value="${index}" 
                           data-name="${supp.name}" 
                           data-price="${supp.price}"
                           onchange="calculateTotal()">
                    <span>${supp.name} (+${supp.price} DT)</span>
                </label>
            `;
        });
        supplementsHtml += '</div>';
    }
    
    let accompagnementsHtml = '';
    if (plat.accompagnements && plat.accompagnements.length > 0) {
        accompagnementsHtml = '<h3>Choisissez un accompagnement :</h3><div class="accompagnements-list">';
        plat.accompagnements.forEach((acc, index) => {
            const checked = index === 0 ? 'checked' : '';
            accompagnementsHtml += `
                <label class="accompagnement-item">
                    <input type="radio" name="accompagnement" value="${index}"
                           data-name="${acc.name}" 
                           data-price="${acc.price}"
                           ${checked}
                           onchange="calculateTotal()">
                    <span>${acc.name}${acc.price > 0 ? ' (+' + acc.price + ' DT)' : ''}</span>
                </label>
            `;
        });
        accompagnementsHtml += '</div>';
    }
    
    modal.innerHTML = `
        <div class="modal-content customization-modal-content">
            <span class="close" onclick="closeCustomizationModal()">&times;</span>
            
            <div class="modal-header">
                <img src="${plat.image}" alt="${plat.name}">
                <div class="modal-info">
                    <h2>${plat.name}</h2>
                    <div class="badges-container">${badges}</div>
                    <p class="description">${plat.description}</p>
                    <p class="prix-base">Prix de base : <strong>${plat.price} DT</strong></p>
                </div>
            </div>
            
            <div class="modal-body">
                ${supplementsHtml}
                ${accompagnementsHtml}
                
                <div class="quantity-section">
                    <h3>Quantité :</h3>
                    <div class="quantity-control">
                        <button onclick="changeQuantity(-1)">-</button>
                        <input type="number" id="quantity" value="1" min="1" readonly>
                        <button onclick="changeQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="total-section">
                    <h3>Prix total : <span id="total-price">${plat.price}</span> DT</h3>
                </div>
                
                <button class="btn-add-to-cart" onclick="addToCart()">
                    Ajouter au panier 🛒
                </button>
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
    
    modal.onclick = function(event) {
        if (event.target === modal) {
            closeCustomizationModal();
        }
    };
    
    calculateTotal();
}

function closeCustomizationModal() {
    const modal = document.getElementById('customization-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentPlat = null;
}

function changeQuantity(delta) {
    const input = document.getElementById('quantity');
    let quantity = parseInt(input.value) + delta;
    if (quantity < 1) quantity = 1;
    input.value = quantity;
    calculateTotal();
}

function calculateTotal() {
    if (!currentPlat) return;
    
    const quantity = parseInt(document.getElementById('quantity').value);
    let total = parseFloat(currentPlat.price);
    
    const supplements = document.querySelectorAll('.supplements-list input[type="checkbox"]:checked');
    supplements.forEach(supp => {
        total += parseFloat(supp.dataset.price);
    });
    
    const accompagnement = document.querySelector('.accompagnements-list input[type="radio"]:checked');
    if (accompagnement) {
        total += parseFloat(accompagnement.dataset.price);
    }
    
    total *= quantity;
    
    document.getElementById('total-price').textContent = total.toFixed(2);
}

// =============================================
// US#3: AJOUTER AU PANIER
// =============================================
async function addToCart() {
    if (!currentPlat) return;
    
    const quantity = parseInt(document.getElementById('quantity').value);
    
    const supplementsInputs = document.querySelectorAll('.supplements-list input[type="checkbox"]:checked');
    const supplements = Array.from(supplementsInputs).map(input => ({
        name: input.dataset.name,
        price: parseFloat(input.dataset.price)
    }));
    
    const accompagnementInput = document.querySelector('.accompagnements-list input[type="radio"]:checked');
    const accompagnement = accompagnementInput ? accompagnementInput.dataset.name : '';
    const accompagnementPrice = accompagnementInput ? parseFloat(accompagnementInput.dataset.price) : 0;
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('plat_id', currentPlat.id);
    formData.append('quantity', quantity);
    formData.append('supplements', JSON.stringify(supplements));
    formData.append('accompagnement', accompagnement);
    formData.append('accompagnement_price', accompagnementPrice);
    
    try {
        const response = await fetch('php/panier.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeCustomizationModal();
            updateCartCount(data.cart_count);
            showNotification('✅ Article ajouté au panier !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'ajout au panier');
    }
}

// =============================================
// US#4: CONSULTER LE PANIER
// =============================================
async function loadCartFromServer() {
    try {
        const response = await fetch('php/panier.php?action=get');
        const data = await response.json();
        
        if (data.success) {
            cart = data.cart;
            updateCartCount(data.count);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

function displayCart() {
    const cartList = document.getElementById('cart-items');
    const emptyCart = document.getElementById('empty-cart');
    
    if (!cartList) return;
    
    loadCartFromServer().then(() => {
        if (cart.length === 0) {
            cartList.style.display = 'none';
            if (emptyCart) emptyCart.style.display = 'block';
            return;
        }
        
        cartList.style.display = 'block';
        if (emptyCart) emptyCart.style.display = 'none';
        cartList.innerHTML = '';
        
        cart.forEach(item => {
            const cartItem = createCartItemElement(item);
            cartList.appendChild(cartItem);
        });
        
        updateCartSummary();
    });
}

function createCartItemElement(item) {
    const div = document.createElement('div');
    div.className = 'cart-item';
    
    let badges = '';
    if (item.sans_gluten) badges += '<span class="badge-small">Sans gluten ✓</span>';
    if (item.sans_lactose) badges += '<span class="badge-small">Sans lactose ✓</span>';
    if (item.bio) badges += '<span class="badge-small bio">Bio 🌱</span>';
    
    let supplementsList = '';
    if (item.supplements && item.supplements.length > 0) {
        supplementsList = '<strong>Suppléments:</strong> ' + item.supplements.map(s => s.name).join(', ');
    }
    
    let accompagnementText = '';
    if (item.accompagnement) {
        accompagnementText = '<strong>Accompagnement:</strong> ' + item.accompagnement;
    }
    
    div.innerHTML = `
        <img src="${item.image}" alt="${item.name}">
        <div class="cart-item-info">
            <h3>${item.name}</h3>
            <div class="badges-container">${badges}</div>
            <p class="cart-item-details">${supplementsList}</p>
            <p class="cart-item-details">${accompagnementText}</p>
            <p><strong>Quantité:</strong> ${item.quantity}</p>
            <p class="prix-item">${item.total} DT</p>
        </div>
        <div class="cart-item-actions">
            <button class="btn-small" onclick="editCartItem('${item.id}')">
                <i class="fas fa-edit"></i> Modifier
            </button>
            <button class="btn-small btn-danger" onclick="deleteCartItem('${item.id}')">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    `;
    
    return div;
}

function updateCartSummary() {
    const subtotal = cart.reduce((sum, item) => sum + parseFloat(item.total), 0);
    const delivery = 3;
    const total = subtotal + delivery;
    
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total-cart');
    
    if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2) + ' DT';
    if (totalEl) totalEl.textContent = total.toFixed(2) + ' DT';
}

function updateCartCount(count) {
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = count;
    });
}

// =============================================
// US#5: MODIFIER UN ARTICLE
// =============================================
async function editCartItem(cartItemId) {
    const item = cart.find(i => i.id === cartItemId);
    if (!item) return;
    
    editingCartItemId = cartItemId;
    
    try {
        const response = await fetch(`php/plats.php?action=get&id=${item.plat_id}`);
        const data = await response.json();
        
        if (data.success) {
            currentPlat = data.plat;
            showEditModal(item);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

function showEditModal(item) {
    let modal = document.getElementById('edit-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'edit-modal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    let supplementsHtml = '';
    if (currentPlat.supplements && currentPlat.supplements.length > 0) {
        supplementsHtml = '<h3>Suppléments :</h3><div class="supplements-list">';
        currentPlat.supplements.forEach((supp, index) => {
            const isChecked = item.supplements.some(s => s.name === supp.name);
            supplementsHtml += `
                <label class="supplement-item">
                    <input type="checkbox" value="${index}" 
                           data-name="${supp.name}" 
                           data-price="${supp.price}"
                           ${isChecked ? 'checked' : ''}
                           onchange="calculateEditTotal()">
                    <span>${supp.name} (+${supp.price} DT)</span>
                </label>
            `;
        });
        supplementsHtml += '</div>';
    }
    
    let accompagnementsHtml = '';
    if (currentPlat.accompagnements && currentPlat.accompagnements.length > 0) {
        accompagnementsHtml = '<h3>Accompagnement :</h3><div class="accompagnements-list">';
        currentPlat.accompagnements.forEach((acc, index) => {
            const isChecked = item.accompagnement === acc.name;
            accompagnementsHtml += `
                <label class="accompagnement-item">
                    <input type="radio" name="edit-accompagnement" value="${index}"
                           data-name="${acc.name}" 
                           data-price="${acc.price}"
                           ${isChecked ? 'checked' : ''}
                           onchange="calculateEditTotal()">
                    <span>${acc.name}${acc.price > 0 ? ' (+' + acc.price + ' DT)' : ''}</span>
                </label>
            `;
        });
        accompagnementsHtml += '</div>';
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Modifier l'article</h2>
            <h3>${item.name}</h3>
            
            ${supplementsHtml}
            ${accompagnementsHtml}
            
            <div class="quantity-section">
                <h3>Quantité :</h3>
                <div class="quantity-control">
                    <button onclick="changeEditQuantity(-1)">-</button>
                    <input type="number" id="edit-quantity" value="${item.quantity}" min="1" readonly>
                    <button onclick="changeEditQuantity(1)">+</button>
                </div>
            </div>
            
            <div class="total-section">
                <h3>Prix total : <span id="edit-total-price">${item.total}</span> DT</h3>
            </div>
            
            <button class="btn-primary" onclick="saveCartEdit()">
                Enregistrer les modifications
            </button>
        </div>
    `;
    
    modal.style.display = 'block';
    calculateEditTotal();
}

function changeEditQuantity(delta) {
    const input = document.getElementById('edit-quantity');
    let quantity = parseInt(input.value) + delta;
    if (quantity < 1) quantity = 1;
    input.value = quantity;
    calculateEditTotal();
}

function calculateEditTotal() {
    if (!currentPlat) return;
    
    const quantity = parseInt(document.getElementById('edit-quantity').value);
    let total = parseFloat(currentPlat.price);
    
    const supplements = document.querySelectorAll('#edit-modal .supplements-list input:checked');
    supplements.forEach(supp => {
        total += parseFloat(supp.dataset.price);
    });
    
    const accompagnement = document.querySelector('#edit-modal .accompagnements-list input:checked');
    if (accompagnement) {
        total += parseFloat(accompagnement.dataset.price);
    }
    
    total *= quantity;
    
    document.getElementById('edit-total-price').textContent = total.toFixed(2);
}

async function saveCartEdit() {
    const quantity = parseInt(document.getElementById('edit-quantity').value);
    
    const supplementsInputs = document.querySelectorAll('#edit-modal .supplements-list input:checked');
    const supplements = Array.from(supplementsInputs).map(input => ({
        name: input.dataset.name,
        price: parseFloat(input.dataset.price)
    }));
    
    const accompagnementInput = document.querySelector('#edit-modal .accompagnements-list input:checked');
    const accompagnement = accompagnementInput ? accompagnementInput.dataset.name : '';
    const accompagnementPrice = accompagnementInput ? parseFloat(accompagnementInput.dataset.price) : 0;
    
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('cart_item_id', editingCartItemId);
    formData.append('quantity', quantity);
    formData.append('supplements', JSON.stringify(supplements));
    formData.append('accompagnement', accompagnement);
    formData.append('accompagnement_price', accompagnementPrice);
    
    try {
        const response = await fetch('php/panier.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeEditModal();
            loadCartFromServer().then(() => displayCart());
            showNotification('✅ Article modifié !');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

function closeEditModal() {
    const modal = document.getElementById('edit-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    editingCartItemId = null;
}

// =============================================
// US#6: SUPPRIMER UN ARTICLE
// =============================================
async function deleteCartItem(cartItemId) {
    if (!confirm('Supprimer cet article du panier ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('cart_item_id', cartItemId);
    
    try {
        const response = await fetch('php/panier.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCartFromServer().then(() => displayCart());
            showNotification('🗑️ Article supprimé');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// =============================================
// US#2: COMMANDER
// =============================================
async function checkout() {
    if (cart.length === 0) {
        alert('Votre panier est vide');
        return;
    }
    
    showCheckoutModal();
}

function showCheckoutModal() {
    let modal = document.getElementById('checkout-modal');
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'checkout-modal';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    const subtotal = cart.reduce((sum, item) => sum + parseFloat(item.total), 0);
    const delivery = 3;
    const total = subtotal + delivery;
    
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="closeCheckoutModal()">&times;</span>
            <h2>Finaliser votre commande</h2>
            
            <form id="checkout-form">
                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" id="client-name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="client-email">
                </div>
                
                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" id="client-phone" required>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="notes" rows="3"></textarea>
                </div>
                
                <div class="checkout-summary">
                    <p>Sous-total: <strong>${subtotal.toFixed(2)} DT</strong></p>
                    <p>Livraison: <strong>${delivery.toFixed(2)} DT</strong></p>
                    <hr>
                    <p class="total">Total: <strong>${total.toFixed(2)} DT</strong></p>
                </div>
                
                <button type="submit" class="btn-primary btn-block">
                    Confirmer la commande 🚀
                </button>
            </form>
        </div>
    `;
    
    modal.style.display = 'block';
    
    document.getElementById('checkout-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        await processCheckout();
    });
}

async function processCheckout() {
    const clientName = document.getElementById('client-name').value;
    const clientEmail = document.getElementById('client-email').value;
    const clientPhone = document.getElementById('client-phone').value;
    const notes = document.getElementById('notes').value;
    
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('client_name', clientName);
    formData.append('client_email', clientEmail);
    formData.append('client_phone', clientPhone);
    formData.append('notes', notes);
    
    try {
        const response = await fetch('php/commandes.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeCheckoutModal();
            showSuccessMessage(data.order_id, data.total);
            cart = [];
            updateCartCount(0);
            displayCart();
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la commande');
    }
}

function closeCheckoutModal() {
    const modal = document.getElementById('checkout-modal');
    if (modal) modal.style.display = 'none';
}

function showSuccessMessage(orderId, total) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content success-modal">
            <div class="success-icon">✅</div>
            <h2>Commande confirmée !</h2>
            <p>Numéro: <strong>#${orderId}</strong></p>
            <p>Total: <strong>${total} DT</strong></p>
            <button class="btn-primary" onclick="location.href='index.html'">
                Retour à l'accueil
            </button>
        </div>
    `;
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// =============================================
// UTILITAIRES
// =============================================
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function setupEventListeners() {
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', checkout);
    }
}

function mobile() {
    var x = document.getElementById("nav");
    if (x.className === "nav") {
        x.className += " responsive";
    } else {
        x.className = "nav";
    }
}