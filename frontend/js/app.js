// FoodHub Frontend - Complete SPA with Fetch API Integration
const API_BASE = '/backend';

// ============================================
// STATE MANAGEMENT
// ============================================
let app = {
    currentUser: null,
    currentCart: null,
    currentRestaurant: null,
    restaurants: [],
    currentPage: 'home'
};

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', async function() {
    await checkUserStatus();
    setupEventListeners();
    showPage('home');
});

// ============================================
// AUTHENTICATION
// ============================================
async function checkUserStatus() {
    try {
        const res = await fetch(`${API_BASE}/auth/user.php`);
        const data = await res.json();
        
        if (data.logged_in) {
            app.currentUser = data.user;
            updateNavigation();
        }
    } catch (error) {
        console.error('Error checking user status:', error);
    }
}

async function registerUser() {
    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const role = document.getElementById('reg-role').value;
    
    if (!name || !email || !password || password.length < 6) {
        alert('Please fill all fields. Password must be at least 6 characters.');
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/auth/register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password, role })
        });
        
        const data = await res.json();
        
        if (data.status === 'success') {
            alert('Registration successful! Please log in.');
            closeModal('register-modal');
            showPage('login');
        } else {
            alert('Error: ' + (data.message || 'Registration failed'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function loginUser() {
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    
    if (!email || !password) {
        alert('Please fill all fields');
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await res.json();
        
        if (data.status === 'success') {
            await checkUserStatus();
            
            // Route based on role
            if (app.currentUser.role === 'admin') {
                showPage('admin-dashboard');
            } else if (app.currentUser.role === 'owner') {
                showPage('owner-dashboard');
            } else {
                showPage('restaurants');
            }
            
            alert('Logged in successfully!');
        } else {
            alert('Error: ' + (data.message || 'Login failed'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function logoutUser() {
    fetch(`${API_BASE}/auth/logout.php`, { method: 'POST' })
        .then(() => {
            app.currentUser = null;
            app.currentCart = null;
            updateNavigation();
            showPage('home');
            alert('Logged out');
        })
        .catch(error => alert('Error: ' + error.message));
}

// ============================================
// RESTAURANTS & MENU
// ============================================
async function loadRestaurants() {
    try {
        const search = document.getElementById('search-restaurant')?.value.trim() || '';
        const area = document.getElementById('filter-area')?.value || '';
        
        let url = `${API_BASE}/restaurants/list.php`;
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (area) params.append('area', area);
        if (params.toString()) url += '?' + params.toString();
        
        const res = await fetch(url);
        app.restaurants = await res.json();
        displayRestaurants();
    } catch (error) {
        console.error('Error loading restaurants:', error);
    }
}

function displayRestaurants() {
    const container = document.getElementById('restaurants-list');
    if (!container) return;
    
    if (!app.restaurants || app.restaurants.length === 0) {
        container.innerHTML = '<p>No restaurants found</p>';
        return;
    }
    
    container.innerHTML = app.restaurants.map(r => `
        <div class="restaurant-card" onclick="viewRestaurantMenu(${r.id})">
            <div class="restaurant-img" style="background: #ddd; height: 200px; display: flex; align-items: center; justify-content: center;">
                ${r.image_url ? `<img src="${r.image_url}" style="width:100%; height:100%; object-fit:cover;">` : `<span>${r.name}</span>`}
            </div>
            <div class="restaurant-info">
                <h3>${r.name}</h3>
                <p>${r.cuisine} • ${r.area}</p>
                <p>⭐ ${r.rating || 0} • ${r.delivery_time}min</p>
                <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); viewRestaurantMenu(${r.id})">View Menu</button>
            </div>
        </div>
    `).join('');
}

async function viewRestaurantMenu(restaurantId) {
    try {
        const res = await fetch(`${API_BASE}/restaurants/menu.php?restaurant_id=${restaurantId}`);
        const menu = await res.json();
        
        app.currentRestaurant = app.restaurants.find(r => r.id === restaurantId);
        
        const modal = document.getElementById('menu-modal');
        if (!modal) return;
        
        let html = `<div class="modal-header">
            <h2>${app.currentRestaurant.name}</h2>
            <button onclick="closeModal('menu-modal')" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <div class="menu-grid">`;
        
        menu.forEach(item => {
            html += `
                <div class="menu-card">
                    <h4>${item.name}</h4>
                    <p>${item.description || ''}</p>
                    <p style="color: #ff6b35; font-weight: bold; font-size: 18px;">KES ${item.price}</p>
                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <input type="number" id="qty-${item.id}" value="1" min="1" style="width:60px; padding:8px; border:1px solid #ddd; border-radius:5px;">
                        <button class="btn btn-primary btn-sm" onclick="addToCart(${restaurantId}, ${item.id})">Add</button>
                    </div>
                </div>
            `;
        });
        
        html += `</div></div>`;
        modal.innerHTML = html;
        openModal('menu-modal');
    } catch (error) {
        alert('Error loading menu: ' + error.message);
    }
}

// ============================================
// CART MANAGEMENT
// ============================================
async function addToCart(restaurantId, menuItemId) {
    if (!app.currentUser) {
        alert('Please log in first');
        return;
    }
    
    const quantity = parseInt(document.getElementById(`qty-${menuItemId}`)?.value || 1);
    
    try {
        const res = await fetch(`${API_BASE}/cart/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                restaurant_id: restaurantId,
                menu_item_id: menuItemId,
                quantity: quantity
            })
        });
        
        if (res.ok) {
            alert('Added to cart!');
            await loadCart();
        } else {
            const error = await res.json();
            alert('Error: ' + (error.error || 'Failed to add item'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function loadCart() {
    if (!app.currentUser) return;
    
    try {
        const res = await fetch(`${API_BASE}/cart/cart.php`);
        app.currentCart = await res.json();
        displayCart();
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

function displayCart() {
    const container = document.getElementById('cart-items-display');
    if (!container) return;
    
    if (!app.currentCart || !app.currentCart.items || app.currentCart.items.length === 0) {
        container.innerHTML = '<p>Cart is empty</p>';
        return;
    }
    
    container.innerHTML = app.currentCart.items.map(item => `
        <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <p><strong>${item.name}</strong></p>
                <p>KES ${item.price} x ${item.quantity} = KES ${(item.price * item.quantity).toFixed(2)}</p>
            </div>
            <button class="btn btn-danger btn-sm" onclick="removeFromCart(${item.id})">Remove</button>
        </div>
    `).join('');
    
    const total = app.currentCart.subtotal || 0;
    const totalDiv = document.getElementById('cart-total');
    if (totalDiv) {
        totalDiv.innerHTML = `<strong>Total: KES ${total.toFixed(2)}</strong>`;
    }
}

async function removeFromCart(cartItemId) {
    try {
        await fetch(`${API_BASE}/cart/cart.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart_item_id: cartItemId,
                quantity: 0
            })
        });
        
        await loadCart();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// ============================================
// CHECKOUT & ORDERS
// ============================================
async function showCheckout() {
    if (!app.currentCart) {
        alert('Cart is empty');
        return;
    }
    
    showPage('checkout');
}

async function placeOrder() {
    const address = document.getElementById('delivery-address')?.value.trim();
    const phone = document.getElementById('delivery-phone')?.value.trim();
    const payment = document.getElementById('payment-method')?.value || 'cash';
    
    if (!address || !phone) {
        alert('Please fill in delivery address and phone');
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/orders/customer_orders.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                restaurant_id: app.currentCart.restaurant_id,
                delivery_address: address,
                delivery_phone: phone,
                payment_method: payment,
                notes: document.getElementById('order-notes')?.value || ''
            })
        });
        
        const data = await res.json();
        
        if (data.status === 'success') {
            alert(`Order placed! Order ID: ${data.order_id}`);
            app.currentCart = null;
            showPage('orders');
            loadCustomerOrders();
        } else {
            alert('Error: ' + (data.error || 'Failed to place order'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function loadCustomerOrders() {
    if (!app.currentUser) return;
    
    try {
        const res = await fetch(`${API_BASE}/orders/customer_orders.php`);
        const orders = await res.json();
        displayCustomerOrders(orders);
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

function displayCustomerOrders(orders) {
    const container = document.getElementById('customer-orders');
    if (!container) return;
    
    if (!orders || orders.length === 0) {
        container.innerHTML = '<p>No orders yet</p>';
        return;
    }
    
    container.innerHTML = orders.map(o => `
        <div style="padding:15px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px;">
            <p><strong>Order #${o.id}</strong> • ${new Date(o.created_at).toLocaleDateString()}</p>
            <p>Status: <strong>${o.status}</strong> • Payment: ${o.payment_status}</p>
            <p>Total: <strong>KES ${o.total_amount.toFixed(2)}</strong></p>
            <button class="btn btn-primary btn-sm" onclick="viewOrderDetails(${o.id})">View Details</button>
        </div>
    `).join('');
}

async function viewOrderDetails(orderId) {
    try {
        const res = await fetch(`${API_BASE}/orders/customer_orders.php?id=${orderId}`);
        const order = await res.json();
        
        let html = `
            <h3>Order #${order.id}</h3>
            <p>Status: <strong>${order.status}</strong></p>
            <p>Total: <strong>KES ${order.total_amount.toFixed(2)}</strong></p>
            <h4>Items:</h4>
            <ul>`;
        
        order.items.forEach(item => {
            html += `<li>${item.name} x ${item.quantity} = KES ${(item.price * item.quantity).toFixed(2)}</li>`;
        });
        
        html += `</ul>`;
        
        alert(html);
    } catch (error) {
        alert('Error loading order: ' + error.message);
    }
}

// ============================================
// OWNER DASHBOARD
// ============================================
async function loadOwnerOrders() {
    if (!app.currentUser || app.currentUser.role !== 'owner') {
        alert('Owner access required');
        return;
    }
    
    try {
        const status = document.getElementById('owner-order-filter')?.value || '';
        let url = `${API_BASE}/owner/orders.php`;
        if (status) url += '?status=' + status;
        
        const res = await fetch(url);
        const orders = await res.json();
        displayOwnerOrders(orders);
    } catch (error) {
        alert('Error loading orders: ' + error.message);
    }
}

function displayOwnerOrders(orders) {
    const container = document.getElementById('owner-orders');
    if (!container) return;
    
    if (!orders || orders.length === 0) {
        container.innerHTML = '<p>No orders</p>';
        return;
    }
    
    container.innerHTML = orders.map(o => `
        <div style="padding:15px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px;">
            <p><strong>Order #${o.id}</strong> from ${o.customer_name}</p>
            <p>Phone: ${o.phone} • Total: KES ${o.total_amount.toFixed(2)}</p>
            <p>Address: ${o.delivery_address}</p>
            <p>Current Status: <strong>${o.status}</strong></p>
            <select id="status-${o.id}" onchange="updateOrderStatus(${o.id})">
                <option value="pending" ${o.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="preparing" ${o.status === 'preparing' ? 'selected' : ''}>Preparing</option>
                <option value="ready" ${o.status === 'ready' ? 'selected' : ''}>Ready</option>
                <option value="delivered" ${o.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                <option value="cancelled" ${o.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>
        </div>
    `).join('');
}

async function updateOrderStatus(orderId) {
    const status = document.getElementById(`status-${orderId}`)?.value;
    
    try {
        const res = await fetch(`${API_BASE}/owner/orders.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        });
        
        if (res.ok) {
            alert('Order status updated!');
            loadOwnerOrders();
        } else {
            const error = await res.json();
            alert('Error: ' + error.error);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// ============================================
// UI HELPERS
// ============================================
function showPage(pageName) {
    document.querySelectorAll('[id$="-page"]').forEach(el => el.style.display = 'none');
    
    const pageEl = document.getElementById(pageName + '-page');
    if (pageEl) pageEl.style.display = 'block';
    
    app.currentPage = pageName;
    
    // Load data based on page
    if (pageName === 'restaurants') loadRestaurants();
    if (pageName === 'orders') loadCustomerOrders();
    if (pageName === 'owner-dashboard') loadOwnerOrders();
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function updateNavigation() {
    const navUser = document.getElementById('nav-user');
    const navLinks = document.getElementById('nav-links');
    
    if (!navUser || !navLinks) return;
    
    if (app.currentUser) {
        navUser.innerHTML = `
            <span>Welcome, ${app.currentUser.name}</span>
            <button class="btn btn-danger btn-sm" onclick="logoutUser()">Logout</button>
        `;
        
        if (app.currentUser.role === 'customer') {
            navLinks.innerHTML += '<li><a href="#" onclick="showPage(\'cart\')">Cart</a></li>';
        }
    } else {
        navUser.innerHTML = `
            <button class="btn btn-primary btn-sm" onclick="showPage('login')">Login</button>
            <button class="btn btn-secondary btn-sm" onclick="openModal('register-modal')">Register</button>
        `;
    }
}

function setupEventListeners() {
    // Search
    document.getElementById('search-restaurant')?.addEventListener('keyup', loadRestaurants);
    document.getElementById('filter-area')?.addEventListener('change', loadRestaurants);
    
    // Auth
    document.getElementById('register-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        registerUser();
    });
    
    document.getElementById('login-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        loginUser();
    });
    
    // Checkout
    document.getElementById('checkout-form')?.addEventListener('submit', (e) => {
        e.preventDefault();
        placeOrder();
    });
    
    // Owner filter
    document.getElementById('owner-order-filter')?.addEventListener('change', loadOwnerOrders);
}
