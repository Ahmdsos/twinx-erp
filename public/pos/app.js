/**
 * TWINX POS - نقطة البيع
 * PWA JavaScript Application
 */

// Configuration
const CONFIG = {
    API_URL: '/api/v1',
    TAX_RATE: 0.15,
    CURRENCY: 'ر.س'
};

// State
let cart = [];
let products = [];
let token = localStorage.getItem('pos_token') || '';

// Sample Products (will be replaced by API)
const sampleProducts = [
    { id: '1', name: 'برجر لحم', price: 25, category: 'food', emoji: '🍔' },
    { id: '2', name: 'بيتزا كبيرة', price: 45, category: 'food', emoji: '🍕' },
    { id: '3', name: 'شاورما', price: 18, category: 'food', emoji: '🌯' },
    { id: '4', name: 'كولا', price: 5, category: 'drinks', emoji: '🥤' },
    { id: '5', name: 'عصير برتقال', price: 8, category: 'drinks', emoji: '🍊' },
    { id: '6', name: 'ماء', price: 2, category: 'drinks', emoji: '💧' },
    { id: '7', name: 'آيفون كيس', price: 35, category: 'electronics', emoji: '📱' },
    { id: '8', name: 'شاحن', price: 25, category: 'electronics', emoji: '🔌' },
    { id: '9', name: 'تيشيرت', price: 55, category: 'clothes', emoji: '👕' },
    { id: '10', name: 'جينز', price: 120, category: 'clothes', emoji: '👖' },
];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    products = sampleProducts;
    renderProducts();
    setupEventListeners();
    
    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(err => console.log('SW Error:', err));
    }
});

// Setup Event Listeners
function setupEventListeners() {
    // Search
    document.getElementById('search').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        renderProducts(query);
    });
    
    // Categories
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderProducts('', btn.dataset.category);
        });
    });
    
    // Pay Button
    document.getElementById('pay-btn').addEventListener('click', processPayment);
}

// Render Products
function renderProducts(query = '', category = 'all') {
    const container = document.getElementById('products');
    let filtered = products;
    
    if (category !== 'all') {
        filtered = filtered.filter(p => p.category === category);
    }
    
    if (query) {
        filtered = filtered.filter(p => p.name.toLowerCase().includes(query));
    }
    
    container.innerHTML = filtered.map(product => `
        <div class="product-card" onclick="addToCart('${product.id}')">
            <div class="emoji">${product.emoji}</div>
            <div class="name">${product.name}</div>
            <div class="price">${product.price.toFixed(2)} ${CONFIG.CURRENCY}</div>
        </div>
    `).join('');
}

// Add to Cart
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    const existing = cart.find(item => item.id === productId);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ ...product, qty: 1 });
    }
    
    renderCart();
    updateTotals();
}

// Remove from Cart
function removeFromCart(productId) {
    const index = cart.findIndex(item => item.id === productId);
    if (index > -1) {
        if (cart[index].qty > 1) {
            cart[index].qty--;
        } else {
            cart.splice(index, 1);
        }
    }
    renderCart();
    updateTotals();
}

// Render Cart
function renderCart() {
    const container = document.getElementById('cart-items');
    const emptyCart = document.getElementById('empty-cart');
    
    if (cart.length === 0) {
        emptyCart.style.display = 'flex';
        container.innerHTML = '';
        container.appendChild(emptyCart);
        document.getElementById('pay-btn').disabled = true;
        return;
    }
    
    emptyCart.style.display = 'none';
    document.getElementById('pay-btn').disabled = false;
    
    container.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="info">
                <div class="name">${item.emoji} ${item.name}</div>
                <div class="price">${item.price.toFixed(2)} ${CONFIG.CURRENCY}</div>
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="removeFromCart('${item.id}')">-</button>
                <span class="qty">${item.qty}</span>
                <button class="qty-btn" onclick="addToCart('${item.id}')">+</button>
            </div>
            <div class="total">${(item.price * item.qty).toFixed(2)}</div>
        </div>
    `).join('');
}

// Update Totals
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const tax = subtotal * CONFIG.TAX_RATE;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)} ${CONFIG.CURRENCY}`;
    document.getElementById('tax').textContent = `${tax.toFixed(2)} ${CONFIG.CURRENCY}`;
    document.getElementById('total').textContent = `${total.toFixed(2)} ${CONFIG.CURRENCY}`;
}

// Process Payment
function processPayment() {
    if (cart.length === 0) return;
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const total = subtotal * (1 + CONFIG.TAX_RATE);
    
    // Show confirmation
    if (confirm(`إتمام الدفع بمبلغ ${total.toFixed(2)} ${CONFIG.CURRENCY}؟`)) {
        // Clear cart
        cart = [];
        renderCart();
        updateTotals();
        
        // Show success
        alert('✅ تمت عملية البيع بنجاح!');
    }
}

// Format Currency
function formatCurrency(amount) {
    return `${amount.toFixed(2)} ${CONFIG.CURRENCY}`;
}
