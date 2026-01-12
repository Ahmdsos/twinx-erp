/**
 * TWINX POS - نقطة البيع
 * PWA JavaScript Application with API Integration
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
let customers = [];
let token = localStorage.getItem('pos_token') || '';
let currentCustomer = null;

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Try to load from API first, fallback to sample data
    await loadProducts();
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

// Load Products from API
async function loadProducts() {
    try {
        const response = await fetch(`${CONFIG.API_URL}/pos/products`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                products = data.data.map(p => ({
                    id: p.id,
                    name: p.name,
                    sku: p.sku,
                    price: parseFloat(p.selling_price),
                    category: p.category?.name || 'عام',
                    emoji: getCategoryEmoji(p.category?.name),
                    barcode: p.barcode
                }));
                console.log('✅ Products loaded from API:', products.length);
                return;
            }
        }
    } catch (error) {
        console.log('⚠️ API not available, using sample data');
    }
    
    // Fallback to sample data
    products = getSampleProducts();
}

// Get Category Emoji
function getCategoryEmoji(category) {
    const emojis = {
        'إلكترونيات': '📱',
        'ملابس': '👕',
        'طعام': '🍔',
        'مشروبات': '🥤',
        'Electronics': '📱',
        'Clothes': '👕',
        'Food': '🍔',
        'Drinks': '🥤',
    };
    return emojis[category] || '📦';
}

// Sample Products
function getSampleProducts() {
    return [
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
}

// Render Products
function renderProducts(query = '', category = 'all') {
    const container = document.getElementById('products');
    let filtered = products;
    
    // Category mapping for Arabic
    const categoryMap = {
        'food': ['food', 'طعام'],
        'drinks': ['drinks', 'مشروبات'],
        'electronics': ['electronics', 'إلكترونيات'],
        'clothes': ['clothes', 'ملابس'],
    };
    
    if (category !== 'all') {
        const validCats = categoryMap[category] || [category];
        filtered = filtered.filter(p => validCats.includes(p.category?.toLowerCase()));
    }
    
    if (query) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(query) ||
            (p.sku && p.sku.toLowerCase().includes(query)) ||
            (p.barcode && p.barcode.includes(query))
        );
    }
    
    container.innerHTML = filtered.map(product => `
        <div class="product-card" onclick="addToCart('${product.id}')">
            <div class="emoji">${product.emoji}</div>
            <div class="name">${product.name}</div>
            <div class="price">${product.price.toFixed(2)} ${CONFIG.CURRENCY}</div>
        </div>
    `).join('');
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="empty-products">لا توجد منتجات</div>';
    }
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
    
    // Play sound or haptic feedback
    if (navigator.vibrate) navigator.vibrate(50);
    
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
async function processPayment() {
    if (cart.length === 0) return;
    
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const total = subtotal * (1 + CONFIG.TAX_RATE);
    
    // Prepare sale data
    const saleData = {
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.qty,
            unit_price: item.price
        })),
        payment_method: 'cash',
        customer_id: currentCustomer?.id || null
    };
    
    // Show confirmation
    if (confirm(`إتمام الدفع بمبلغ ${total.toFixed(2)} ${CONFIG.CURRENCY}؟`)) {
        try {
            // Try to send to API
            const response = await fetch(`${CONFIG.API_URL}/pos/sale`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(saleData)
            });
            
            if (response.ok) {
                console.log('✅ Sale sent to API');
            }
        } catch (error) {
            console.log('⚠️ Sale stored locally (offline mode)');
            // Store locally for sync later
            const pendingSales = JSON.parse(localStorage.getItem('pending_sales') || '[]');
            pendingSales.push({ ...saleData, timestamp: Date.now() });
            localStorage.setItem('pending_sales', JSON.stringify(pendingSales));
        }
        
        // Clear cart
        cart = [];
        currentCustomer = null;
        renderCart();
        updateTotals();
        
        // Show success
        showNotification('✅ تمت عملية البيع بنجاح!', 'success');
    }
}

// Show Notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 16px 24px;
        background: ${type === 'success' ? '#16a34a' : '#1e40af'};
        color: white;
        border-radius: 8px;
        font-weight: 600;
        z-index: 1000;
        animation: slideDown 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Search Customers
async function searchCustomers(query) {
    try {
        const response = await fetch(`${CONFIG.API_URL}/pos/customers/search?search=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            return data.data || [];
        }
    } catch (error) {
        console.log('Customer search failed');
    }
    return [];
}

// Format Currency
function formatCurrency(amount) {
    return `${amount.toFixed(2)} ${CONFIG.CURRENCY}`;
}
