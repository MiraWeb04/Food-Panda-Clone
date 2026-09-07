let products = [];
function renderPromos() {
  const promoSection = document.getElementById('promo-section');
  const promoCards = document.getElementById('promo-cards');
  if (!promoSection || !promoCards) return;
  if (!Array.isArray(activePromos) || activePromos.length === 0) {
    promoSection.classList.add('hidden');
    promoCards.innerHTML = '';
    return;
  }

  promoSection.classList.remove('hidden');
  promoCards.innerHTML = activePromos.map(promo => {
    const label = promo.discount_type === 'percentage'
      ? `${promo.discount_value}% off`
      : `₱ ${Number(promo.discount_value).toFixed(2)} off`;
    return `
      <article class="promo-card">
        <img src="${promo.banner || '/images/bean_rooted_logo.png'}" alt="${escapeHtml(promo.title)} banner" loading="lazy" decoding="async" />
        <div>
          <h4>${escapeHtml(promo.title)}</h4>
          <p>${escapeHtml(promo.description)}</p>
          <div class="promo-badge">${escapeHtml(label)}</div>
          <p class="small text-muted mt-2">Valid ${formatDateLabel(promo.start_date)}–${formatDateLabel(promo.end_date)}</p>
          <div style="margin-top:.5rem">
            <button class="apply-promo-btn btn btn-sm btn-primary" data-id="${promo.id}">Use Promo</button>
            <button class="view-promo-btn btn btn-sm btn-outline-secondary" data-id="${promo.id}">View Details</button>
          </div>
        </div>
      </article>
    `;
  }).join('');

  // wire up apply buttons
  promoCards.querySelectorAll('.apply-promo-btn').forEach(btn => btn.addEventListener('click', () => {
    const id = Number(btn.dataset.id);
    const promo = activePromos.find(p => Number(p.id) === id);
    if (!promo) return;
    currentPromo = promo;
    currentPromoDiscount = promo.discount_type === 'fixed' ? Number(promo.discount_value) : 0;
    // compute percentage discount using existing getBestPromo logic
    if (promo.discount_type === 'percentage') {
      // recalc using cart gross
      const grossTotal = Object.values(cart).reduce((s, it) => s + it.price * it.quantity, 0);
      currentPromoDiscount = Number((grossTotal * promo.discount_value) / 100);
    }
    currentPromoTotal = Math.max(0, Object.values(cart).reduce((s, it) => s + it.price * it.quantity, 0) - currentPromoDiscount);
    updateCartDisplay();
    openCart();
  }));

  promoCards.querySelectorAll('.view-promo-btn').forEach(btn => btn.addEventListener('click', () => {
    const id = Number(btn.dataset.id);
    const promo = activePromos.find(p => Number(p.id) === id);
    if (!promo) return alert(promo.description || 'No details');
    alert(`${promo.title}\n\n${promo.description}\n\nValid ${promo.start_date} - ${promo.end_date}`);
  }));
      renderProducts(activeCategory);
    });
  });
}

async function loadCategoryButtons() {
  try {
    const res = await fetch('/api/categories.php');
    const data = await res.json();
    if (data.success && Array.isArray(data.categories)) {
      buildCategoryButtons(data.categories);
    }
  } catch (err) {
    console.error('Failed to load categories', err);
  }
}

async function loadPromos() {
  try {
    const res = await fetch('/api/promos.php');
    const data = await res.json();
    activePromos = Array.isArray(data.promos) ? data.promos : [];
    renderPromos();
    updateCartDisplay();
  } catch (err) {
    console.error('Failed to load promos', err);
  }
}

function renderPromos() {
  if (!promoSection || !promoCards) return;
  if (!Array.isArray(activePromos) || activePromos.length === 0) {
    promoSection.classList.add('hidden');
    promoCards.innerHTML = '';
    return;
  }

  promoSection.classList.remove('hidden');
  promoCards.innerHTML = activePromos.map(promo => {
    const label = promo.discount_type === 'percentage'
      ? `${promo.discount_value}% off`
      : `₱ ${Number(promo.discount_value).toFixed(2)} off`;
    return `
      <article class="promo-card">
        <img src="${promo.banner || '/images/bean_rooted_logo.png'}" alt="${escapeHtml(promo.title)} banner" loading="lazy" decoding="async" />
        <div>
          <h4>${escapeHtml(promo.title)}</h4>
          <p>${escapeHtml(promo.description)}</p>
          <div class="promo-badge">${escapeHtml(label)}</div>
          <p class="small text-muted mt-2">Valid ${formatDateLabel(promo.start_date)}–${formatDateLabel(promo.end_date)}</p>
          <div style="margin-top:.5rem">
            <button class="apply-promo-btn btn btn-sm btn-primary" data-id="${promo.id}">Use Promo</button>
            <button class="view-promo-btn btn btn-sm btn-outline-secondary" data-id="${promo.id}">View Details</button>
          </div>
        </div>
      </article>
    `;
  }).join('');
}
  }

  // wire up apply buttons
  promoCards.querySelectorAll('.apply-promo-btn').forEach(btn => btn.addEventListener('click', () => {
    const id = Number(btn.dataset.id);
    const promo = activePromos.find(p => Number(p.id) === id);
    if (!promo) return;
    currentPromo = promo;
    currentPromoDiscount = promo.discount_type === 'fixed' ? Number(promo.discount_value) : 0;
    // compute percentage discount using existing getBestPromo logic
    if (promo.discount_type === 'percentage') {
      // recalc using cart gross
      const grossTotal = Object.values(cart).reduce((s, it) => s + it.price * it.quantity, 0);
      currentPromoDiscount = Number((grossTotal * promo.discount_value) / 100);
    }
    currentPromoTotal = Math.max(0, Object.values(cart).reduce((s, it) => s + it.price * it.quantity, 0) - currentPromoDiscount);
    updateCartDisplay();
    openCart();
  }));

  promoCards.querySelectorAll('.view-promo-btn').forEach(btn => btn.addEventListener('click', () => {
    const id = Number(btn.dataset.id);
    const promo = activePromos.find(p => Number(p.id) === id);
    if (!promo) return alert(promo.description || 'No details');
    alert(`${promo.title}\n\n${promo.description}\n\nValid ${promo.start_date} - ${promo.end_date}`);
  }));
function formatDateLabel(value) {
  if (!value) return '-';
  const d = new Date(value);
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}

function getBestPromo(totalAmount) {
  let best = { promo: null, discount: 0 };
  activePromos.forEach(promo => {
    const discount = promo.discount_type === 'fixed'
      ? Number(promo.discount_value)
      : Number((totalAmount * promo.discount_value) / 100);
    if (discount > best.discount) {
      best = { promo, discount };
    }
  });
  return best;
}
const cartItemsContainer = document.getElementById('cart-items');
const cartCount = document.getElementById('cart-count');
const cartTotal = document.getElementById('cart-total');
const cartDiscount = document.getElementById('cart-discount');
const checkoutBtn = document.getElementById('checkout-btn');
const checkoutModal = document.getElementById('checkout-modal');
const closeCheckoutBtn = document.getElementById('close-checkout-btn');
const paymentForm = document.getElementById('payment-form');
const successModal = document.getElementById('success-modal');
const continueBtn = document.getElementById('continue-btn');
const qrModal = document.getElementById('qr-modal');
const qrImage = document.getElementById('qr-image');
const qrAmount = document.getElementById('qr-amount');
const confirmPaymentBtn = document.getElementById('confirm-payment-btn');
const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
const closeQrBtn = document.getElementById('close-qr-btn');
const viewMenuBtn = document.getElementById('view-menu-btn');
  let activePromos = [];
  let currentPromo = null;
  let currentPromoDiscount = 0;
  let currentPromoTotal = 0;
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');
const paymentMethodButtons = document.querySelectorAll('.payment-option');
const paymentDetails = document.querySelectorAll('.payment-detail');
const qrInstruction = document.getElementById('qr-instruction');
const receiptUploadInput = document.getElementById('receipt-upload');
const receiptPreviewWrapper = document.getElementById('receipt-preview-wrapper');
const receiptPreviewImage = document.getElementById('receipt-preview');
const mapPickerBtn = document.getElementById('map-picker-btn');
const addressInput = document.getElementById('address');
const otpStatus = document.getElementById('otp-status');
const emailInput = document.getElementById('email');
const verificationCodeInput = document.getElementById('verification-code');
const customerView = document.getElementById('customer-view');
const adminPanel = document.getElementById('admin-panel');
const cashierPanel = document.getElementById('cashier-panel');
const portalButtons = document.querySelectorAll('.portal-btn');
const adminTotalOrders = document.getElementById('admin-total-orders');
const adminPendingOrders = document.getElementById('admin-pending-orders');
const adminSales = document.getElementById('admin-sales');
const adminOrderList = document.getElementById('admin-order-list');
const cashierPendingOrders = document.getElementById('cashier-pending-orders');
const cashierSales = document.getElementById('cashier-sales');
const cashierOrderList = document.getElementById('cashier-order-list');

let cart = JSON.parse(localStorage.getItem('sweetBitesCart')) || {};
let activeCategory = 'all';
let selectedPaymentMethod = 'cash';
let currentLocationLabel = 'Your current location';
let otpCode = '';
let otpEmail = '';
let currentRole = 'customer';
let pendingOrderId = null;
let pendingReceiptData = '';
let orders = JSON.parse(localStorage.getItem('beanRootedOrders')) || [];

function applyTheme(theme) {
  document.body.setAttribute('data-theme', theme);
  themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
  themeToggle.setAttribute('aria-pressed', String(theme === 'dark'));
  localStorage.setItem('beanRootedTheme', theme);
}

function initializeTheme() {
  const savedTheme = localStorage.getItem('beanRootedTheme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = savedTheme || (prefersDark ? 'dark' : 'light');
  applyTheme(theme);
}

function formatCurrency(amount) {
  return `₱${amount.toFixed(2)}`;
}

function createTrackingCode() {
  const stamp = Date.now().toString(36).toUpperCase();
  const randomPart = Math.random().toString(36).slice(2, 6).toUpperCase();
  return `TRK-${stamp}-${randomPart}`;
}

function saveOrders() {
  localStorage.setItem('beanRootedOrders', JSON.stringify(orders));
}

function setActiveRole(role) {
  currentRole = role;
  if (customerView) {
    customerView.classList.toggle('hidden', role !== 'customer');
  }
  if (adminPanel) {
    adminPanel.classList.toggle('hidden', role !== 'admin');
  }
  if (cashierPanel) {
    cashierPanel.classList.toggle('hidden', role !== 'cashier');
  }
  if (openCartBtn) {
    openCartBtn.classList.toggle('hidden', role !== 'customer');
  }

  portalButtons.forEach(button => {
    const active = button.dataset.role === role;
    button.classList.toggle('active', active);
  });

  if (role !== 'customer') {
    closeCart();
  }
}

function renderOrderItems(items) {
  return items.map(item => `<li>${item.quantity} × ${item.name} — ${formatCurrency(item.price * item.quantity)}</li>`).join('');
}

function readFileAsDataUrl(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Unable to read the selected receipt image.'));
    reader.readAsDataURL(file);
  });
}

async function postJson(endpoint, payload) {
  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const contentType = response.headers.get('content-type') || '';
    let data = null;

    if (contentType.includes('application/json')) {
      data = await response.json();
    } else {
      const text = await response.text();
      throw new Error(text || 'The payment service is temporarily unavailable.');
    }

    if (!response.ok || !data?.success) {
      throw new Error(data?.message || 'The payment service is temporarily unavailable.');
    }

    return data;
  } catch (error) {
    const message = error instanceof Error && error.message && error.message !== 'Failed to fetch'
      ? error.message
      : 'The payment service is temporarily unavailable. Please check your connection and try again.';
    throw new Error(message);
  }
}

function showFeedback(elementId, message) {
  const feedback = document.getElementById(elementId);
  if (!feedback) return;
  feedback.textContent = message;
  feedback.classList.remove('hidden');
}

function clearFeedback(elementId) {
  const feedback = document.getElementById(elementId);
  if (!feedback) return;
  feedback.textContent = '';
  feedback.classList.add('hidden');
}

function renderDashboard() {
  const totalOrders = orders.length;
  const pendingOrders = orders.filter(order => !['Delivered', 'Cancelled'].includes(order.status)).length;
  const revenue = orders.filter(order => order.paymentStatus === 'Paid').reduce((sum, order) => sum + order.total, 0);

  if (adminTotalOrders) adminTotalOrders.textContent = totalOrders;
  if (adminPendingOrders) adminPendingOrders.textContent = pendingOrders;
  if (adminSales) adminSales.textContent = formatCurrency(revenue);
  if (cashierPendingOrders) cashierPendingOrders.textContent = orders.filter(order => order.paymentStatus !== 'Paid').length;
  if (cashierSales) cashierSales.textContent = formatCurrency(revenue);

  const sortedOrders = [...orders].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  if (adminOrderList) {
      adminOrderList.innerHTML = sortedOrders.length
      ? sortedOrders.map(order => `
          <article class="order-card">
            <div class="order-top">
              <div>
                <h3>${order.id}</h3>
                <div class="order-meta">${order.customer} • ${order.email} • ${new Date(order.createdAt).toLocaleString()}</div>
              </div>
              <span class="badge-pill">${order.paymentStatus}</span>
            </div>
            <div class="order-meta">Tracking: ${order.trackingCode || '—'}</div>
            <ul class="order-list">${renderOrderItems(order.items)}</ul>
            <div class="order-meta">Address: ${order.address || 'Not provided'}${order.landmark ? ` • Landmark: ${order.landmark}` : ''}</div>
            ${order.receiptImage ? `<div class="order-meta">Receipt uploaded</div><img src="${order.receiptImage}" alt="Receipt for ${order.id}" class="receipt-preview-image" />` : ''}
            ${order.receiptImage ? `<label><span class="order-meta">Receipt review</span><select class="order-select" data-action="receipt" data-id="${order.id}"><option value="Pending" ${order.receiptStatus === 'Pending' ? 'selected' : ''}>Pending</option><option value="Verified" ${order.receiptStatus === 'Verified' ? 'selected' : ''}>Verified</option><option value="Rejected" ${order.receiptStatus === 'Rejected' ? 'selected' : ''}>Rejected</option></select></label>` : ''}
            <div class="order-actions">
              <label>
                <span class="order-meta">Status</span>
                <select class="order-select" data-action="status" data-id="${order.id}">
                  <option value="Pending" ${order.status === 'Pending' ? 'selected' : ''}>Pending</option>
                  <option value="Preparing" ${order.status === 'Preparing' ? 'selected' : ''}>Preparing</option>
                  <option value="On Delivery" ${order.status === 'On Delivery' ? 'selected' : ''}>On Delivery</option>
                  <option value="Delivered" ${order.status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                  <option value="Cancelled" ${order.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
              </label>
              <label>
                <span class="order-meta">Payment</span>
                <select class="order-select" data-action="payment" data-id="${order.id}">
                  <option value="Pending" ${order.paymentStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                  <option value="Paid" ${order.paymentStatus === 'Paid' ? 'selected' : ''}>Paid</option>
                </select>
              </label>
              <button class="action-btn danger" data-action="delete" data-id="${order.id}">Delete</button>
            </div>
          </article>
        `).join('')
      : '<p class="empty-state">No orders have been placed yet.</p>';
  }

  if (cashierOrderList) {
      cashierOrderList.innerHTML = sortedOrders.length
      ? sortedOrders.map(order => `
          <article class="order-card">
            <div class="order-top">
              <div>
                <h3>${order.id}</h3>
                <div class="order-meta">${order.customer} • ${order.total ? formatCurrency(order.total) : '₱0.00'}</div>
              </div>
              <span class="badge-pill">${order.paymentStatus}</span>
            </div>
            <div class="order-meta">Status: ${order.status}</div>
            <div class="order-meta">Tracking: ${order.trackingCode || '—'}</div>
            ${order.receiptImage ? `<div class="order-meta">Receipt uploaded</div><img src="${order.receiptImage}" alt="Receipt for ${order.id}" class="receipt-preview-image" />` : ''}
            ${order.receiptImage ? `<label><span class="order-meta">Receipt review</span><select class="order-select" data-action="receipt" data-id="${order.id}"><option value="Pending" ${order.receiptStatus === 'Pending' ? 'selected' : ''}>Pending</option><option value="Verified" ${order.receiptStatus === 'Verified' ? 'selected' : ''}>Verified</option><option value="Rejected" ${order.receiptStatus === 'Rejected' ? 'selected' : ''}>Rejected</option></select></label>` : ''}
            <div class="order-actions">
              <button class="action-btn" data-action="mark-paid" data-id="${order.id}" ${order.paymentStatus === 'Paid' ? 'disabled' : ''}>Mark payment received</button>
              <button class="action-btn secondary" data-action="mark-delivery" data-id="${order.id}" ${order.status === 'Delivered' ? 'disabled' : ''}>Confirm on delivery</button>
              <button class="action-btn secondary" data-action="print" data-id="${order.id}">Print receipt</button>
            </div>
          </article>
        `).join('')
      : '<p class="empty-state">No orders are waiting for cashier action.</p>';
  }
}

function updateOrderStatus(orderId, status) {
  const order = orders.find(item => item.id === orderId);
  if (!order) return;
  order.status = status;
  saveOrders();
  renderDashboard();
}

function updatePaymentStatus(orderId, paymentStatus) {
  const order = orders.find(item => item.id === orderId);
  if (!order) return;
  order.paymentStatus = paymentStatus;
  if (paymentStatus === 'Paid' && order.status === 'Awaiting Payment') {
    order.status = 'Pending';
  }
  saveOrders();
  renderDashboard();
}

function updateReceiptStatus(orderId, receiptStatus) {
  const order = orders.find(item => item.id === orderId);
  if (!order) return;
  order.receiptStatus = receiptStatus;
  saveOrders();
  renderDashboard();
}

function printReceipt(order) {
  const printWindow = window.open('', '_blank', 'width=800,height=900');
  if (!printWindow) {
    alert('Please allow pop-ups to print the receipt.');
    return;
  }

  const itemRows = order.items.map(item => `
    <tr>
      <td>${item.quantity} × ${item.name}</td>
      <td>${formatCurrency(item.price * item.quantity)}</td>
    </tr>
  `).join('');

  printWindow.document.write(`<!DOCTYPE html><html><head><title>Receipt ${order.id}</title><style>body{font-family:Arial,sans-serif;padding:24px;}table{width:100%;border-collapse:collapse;}td,th{padding:8px;border-bottom:1px solid #ddd;}h2{margin-bottom:4px;}small{color:#666;}</style></head><body><h2>Bean Rooted Cafe</h2><p>Receipt ${order.id}</p><p>Customer: ${order.customer}</p><p>Phone: ${order.phone}</p><p>Address: ${order.address || 'Not provided'}</p><table><thead><tr><th>Item</th><th>Amount</th></tr></thead><tbody>${itemRows}</tbody></table><h3>Total: ${formatCurrency(order.total)}</h3><p><small>Payment method: ${order.paymentMethod}</small></p></body></html>`);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function renderProducts(filter = 'all') {
  productsContainer.innerHTML = '';
  const filtered = filter === 'all'
    ? products
    : products.filter(item => item.categorySlug === normalizeCategory(filter));
  filtered.forEach(item => {
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `
      <div class="card-media">
        <img src="${item.image}" alt="${item.name}" class="product-img" loading="lazy" decoding="async" />
      </div>
      <div class="card-body">
        <div class="card-topline">
          <span class="badge">${item.category.toUpperCase()}</span>
          <span class="card-price">${formatCurrency(item.price)}</span>
        </div>
        <h3>${item.name}</h3>
        <p>${item.description}</p>
        <div class="card-footer">
          <span class="card-meta">Freshly prepared</span>
          <button class="add-btn" data-id="${item.id}">Add</button>
        </div>
      </div>
    `;
    productsContainer.appendChild(card);
  });
}

function updateCartDisplay() {
  const items = Object.values(cart);
  const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
  const grossTotal = items.reduce((sum, item) => sum + item.quantity * item.price, 0);
  const promoResult = getBestPromo(grossTotal);
  currentPromo = promoResult.promo;
  currentPromoDiscount = promoResult.discount;
  currentPromoTotal = Math.max(0, grossTotal - currentPromoDiscount);

  cartCount.textContent = totalQuantity;
  cartTotal.textContent = formatCurrency(currentPromoTotal);
  checkoutBtn.disabled = totalQuantity === 0;

  if (cartDiscount) {
    if (currentPromo && currentPromoDiscount > 0) {
      cartDiscount.classList.remove('hidden');
      cartDiscount.innerHTML = `
        <div>
          <strong>Promo applied:</strong> ${escapeHtml(currentPromo.title)}
        </div>
        <div>
          <strong>Saved:</strong> ${formatCurrency(currentPromoDiscount)}
        </div>
      `;
    } else {
      cartDiscount.classList.add('hidden');
      cartDiscount.innerHTML = '';
    }
  }

  if (items.length === 0) {
    cartItemsContainer.innerHTML = '<p class="empty-cart">Your cart is empty. Add something tasty!</p>';
    return;
  }

  cartItemsContainer.innerHTML = '';
  items.forEach(item => {
    const cartItem = document.createElement('div');
    cartItem.className = 'cart-item';
    cartItem.innerHTML = `
      <div class="cart-thumb">
        <img src="${item.image}" alt="${item.name}" loading="lazy" decoding="async" />
      </div>
      <div class="cart-body">
        <div class="meta">
          <h4>${item.name}</h4>
          <span class="line-price">${formatCurrency(item.price * item.quantity)}</span>
        </div>
        <div class="cart-controls">
          <div class="quantity-controls">
            <button class="qty-btn" data-action="decrease" data-id="${item.id}" aria-label="Decrease quantity">−</button>
            <span class="qty-value">${item.quantity}</span>
            <button class="qty-btn" data-action="increase" data-id="${item.id}" aria-label="Increase quantity">+</button>
          </div>
          <button class="remove-btn" data-action="remove" data-id="${item.id}" aria-label="Remove item">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 6V4h6v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    `;
    cartItemsContainer.appendChild(cartItem);
  });
}

function saveCart() {
  localStorage.setItem('sweetBitesCart', JSON.stringify(cart));
}

function addToCart(id) {
  const product = products.find(item => item.id === id);
  if (!product) return;
  if (!cart[id]) {
    cart[id] = { ...product, quantity: 0 };
  }
  cart[id].quantity += 1;
  saveCart();
  updateCartDisplay();
  openCart();
}

function changeQuantity(id, action) {
  if (!cart[id]) return;
  if (action === 'increase') {
    cart[id].quantity += 1;
  } else if (action === 'decrease') {
    cart[id].quantity -= 1;
  }
  if (cart[id].quantity <= 0) {
    delete cart[id];
  }
  saveCart();
  updateCartDisplay();
}

function removeItem(id) {
  delete cart[id];
  saveCart();
  updateCartDisplay();
}

function openCart() {
  cartPanel.classList.add('visible');
  cartPanel.classList.remove('hidden');
}

function closeCart() {
  cartPanel.classList.remove('visible');
  cartPanel.classList.add('hidden');
}

function openCheckout() {
  checkoutModal.classList.remove('hidden');
  clearFeedback('payment-feedback');
  setPaymentStep(2);
}

function closeCheckout() {
  checkoutModal.classList.add('hidden');
  clearFeedback('payment-feedback');
}

function openSuccess() {
  successModal.classList.remove('hidden');
}

function closeSuccess() {
  successModal.classList.add('hidden');
}

function clearCart() {
  cart = {};
  saveCart();
  updateCartDisplay();
}

function showConfirmation(order) {
  const title = document.getElementById('success-title');
  const message = document.getElementById('success-message');
  const orderIdEl = document.getElementById('success-order-id');
  const trackingEl = document.getElementById('success-tracking');
  const etaEl = document.getElementById('success-eta');

  orderIdEl.textContent = order.id;
  trackingEl.textContent = order.trackingCode || '—';
  // Random-ish ETA to mimic delivery estimate like Foodpanda
  const etaMinutes = 20 + Math.floor(Math.random() * 21); // 20-40 mins
  etaEl.textContent = `${etaMinutes} mins`;

  if (order.paymentMethod === 'cash') {
    title.textContent = 'Order confirmed — pay on delivery';
    message.textContent = `Your order ${order.id} is confirmed. Please pay ${formatCurrency(order.total)} to the rider upon delivery. A rider will be assigned shortly.`;
  } else if (order.status === 'Pending') {
    title.textContent = 'Payment received — awaiting confirmation';
    message.textContent = `Payment for ${order.id} has been received and is awaiting staff confirmation. Your order will remain pending until it is accepted.`;
  } else {
    title.textContent = 'Payment received — order confirmed';
    message.textContent = `Payment for ${order.id} was successful. Your order is now being prepared and will arrive in about ${etaMinutes} minutes.`;
  }

  successModal.classList.remove('hidden');
  // store last shown order for actions like tracking
  window.lastShownOrder = order;
}

function setPaymentStep(step) {
  const progress = document.querySelector('.payment-progress');
  if (!progress) return;
  const steps = Array.from(progress.querySelectorAll('.step'));
  steps.forEach((s, i) => s.classList.toggle('active', i < step));
}

// Track order button: open the dedicated tracking page for the placed order.
const trackOrderBtn = document.getElementById('track-order-btn');
if (trackOrderBtn) {
  trackOrderBtn.addEventListener('click', () => {
    const ord = window.lastShownOrder;
    const trackingCode = ord?.trackingCode || ord?.id;
    if (trackingCode) {
      window.location.href = `tracking.html?tracking=${encodeURIComponent(trackingCode)}`;
    } else {
      window.location.href = 'tracking.html';
    }
    closeSuccess();
  });
}

function updatePaymentMethod(method) {
  selectedPaymentMethod = method;

  paymentMethodButtons.forEach(button => {
    const active = button.dataset.method === method;
    button.classList.toggle('active', active);
    button.setAttribute('aria-pressed', String(active));
  });

  paymentDetails.forEach(detail => {
    detail.classList.toggle('active', detail.id === `payment-${method}`);
  });
}

function updateOtpStatus(message) {
  otpStatus.textContent = message;
}

function setAddressFromLocation(locationText) {
  addressInput.value = locationText;
}

function useMapLocation() {
  if (!navigator.geolocation) {
    setAddressFromLocation('Current location unavailable. Please try again.');
    return;
  }

  navigator.geolocation.getCurrentPosition(
    position => {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      currentLocationLabel = 'Barangay San Pedro, Pagadian City, Zamboanga del Sur';
      setAddressFromLocation(currentLocationLabel);
    },
    () => {
      setAddressFromLocation('Unable to detect your current location. Please try again.');
    }
  );
}

function generateOtp() {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

async function sendOtpToEmail(email) {
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    updateOtpStatus('Please enter a valid email address first.');
    return;
  }

  otpCode = generateOtp();
  otpEmail = email;
  updateOtpStatus(`Sending verification code to ${email}...`);

  try {
    const formData = new URLSearchParams({
      name: 'Bean Rooted Cafe',
      email,
      subject: 'Bean Rooted Cafe verification code',
      message: `Hello!\n\nYour Bean Rooted Cafe verification code is: ${otpCode}\n\nPlease enter this 6-digit code in the checkout form to confirm your order.\n\nThank you,\nBean Rooted Cafe`
    });

    const response = await fetch(`https://formsubmit.co/ajax/${encodeURIComponent(email)}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: formData.toString()
    });

    if (!response.ok) {
      throw new Error('Email service responded with an error');
    }

    updateOtpStatus(`Verification code sent to ${email}. Check your inbox and spam folder.`);
  } catch (error) {
    updateOtpStatus(`OTP could not be sent automatically. Your code is ${otpCode}.`);
  }
}

productsContainer.addEventListener('click', event => {
  const button = event.target.closest('.add-btn');
  if (!button) return;
  const id = Number(button.dataset.id);
  addToCart(id);
});

cartItemsContainer.addEventListener('click', event => {
  const button = event.target.closest('button');
  if (!button) return;
  const id = Number(button.dataset.id);
  const action = button.dataset.action;
  if (action === 'remove') {
    removeItem(id);
  } else {
    changeQuantity(id, action);
  }
});

openCartBtn.addEventListener('click', () => {
  openCart();
});

closeCartBtn.addEventListener('click', () => {
  closeCart();
});

checkoutBtn.addEventListener('click', () => {
  openCheckout();
});

closeCheckoutBtn.addEventListener('click', () => {
  closeCheckout();
});

continueBtn.addEventListener('click', () => {
  closeSuccess();
  closeCheckout();
});

viewMenuBtn.addEventListener('click', () => {
  document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
});

themeToggle.addEventListener('click', () => {
  const currentTheme = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  applyTheme(currentTheme);
});

emailInput.addEventListener('blur', () => {
  const email = emailInput.value.trim();
  if (email) {
    sendOtpToEmail(email);
  }
});

verificationCodeInput.addEventListener('input', event => {
  const value = event.target.value.replace(/\D/g, '').slice(0, 6);
  event.target.value = value;
});

if (receiptUploadInput) {
  receiptUploadInput.addEventListener('change', async event => {
    const [file] = event.target.files || [];
    if (!file) return;

    try {
      pendingReceiptData = await readFileAsDataUrl(file);
      if (pendingOrderId) {
        const pendingOrder = orders.find(item => item.id === pendingOrderId);
        if (pendingOrder) {
          pendingOrder.receiptImage = pendingReceiptData;
          saveOrders();
          renderDashboard();
        }
      }
      if (receiptPreviewWrapper) {
        receiptPreviewWrapper.classList.remove('hidden');
      }
      if (receiptPreviewImage) {
        receiptPreviewImage.src = pendingReceiptData;
      }
    } catch (error) {
      pendingReceiptData = '';
      if (receiptPreviewWrapper) {
        receiptPreviewWrapper.classList.add('hidden');
      }
      if (receiptPreviewImage) {
        receiptPreviewImage.src = '';
      }
      alert(error.message);
    }
  });
}

paymentForm.addEventListener('submit', async event => {
  event.preventDefault();
  const items = Object.values(cart);
  if (!items.length) {
    alert('Please add items to your cart before checkout.');
    return;
  }
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const phone = document.getElementById('phone').value.trim();
  const address = document.getElementById('address').value.trim();

  if (!name || !email || !phone || !address) {
    alert('Please complete your name, email, phone and address fields.');
    return;
  }

  const fullNameParts = name.split(/\s+/).filter(Boolean);
  if (fullNameParts.length < 2) {
    alert('Please enter your full name, including first and last name.');
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    alert('Please enter a valid email address.');
    return;
  }

  if (!/^\d{11,}$/.test(phone)) {
    alert('Please enter a valid phone number with at least 11 digits.');
    return;
  }

  const totalPrice = Object.values(cart).reduce((sum, it) => sum + it.price * it.quantity, 0);
  const orderItems = Object.values(cart).map(it => ({ name: it.name, quantity: it.quantity, price: it.price }));
  const orderData = {
    id: `BR-${Date.now()}`,
    customer: name,
    email,
    phone,
    address,
    landmark: document.getElementById('landmark').value.trim(),
    items: orderItems,
    total: currentPromoTotal || totalPrice,
    promoTitle: currentPromo?.title || '',
    promoDiscount: currentPromoDiscount || 0,
    promoType: currentPromo?.discount_type || '',
    promoValue: currentPromo?.discount_value || 0,
    paymentMethod: selectedPaymentMethod,
    trackingCode: createTrackingCode(),
    status: selectedPaymentMethod === 'cash' ? 'Pending' : 'Awaiting Payment',
    paymentStatus: selectedPaymentMethod === 'cash' ? 'Paid' : 'Pending',
    receiptImage: '',
    receiptStatus: 'Pending',
    createdAt: new Date().toISOString()
  };

  clearFeedback('payment-feedback');

    try {
    const payload = await postJson('controllers/create_order.php', {
      action: 'create_order',
      customer: name,
      email,
      phone,
      address,
      landmark: document.getElementById('landmark').value.trim(),
      paymentMethod: selectedPaymentMethod,
      total: orderData.total, // discounted total when promo applied
      discountedTotal: orderData.total,
      promoTitle: orderData.promoTitle,
      promoType: orderData.promoType,
      promoValue: orderData.promoValue,
      promoDiscount: orderData.promoDiscount,
      items: orderItems
    });

    orderData.id = payload.order_number || orderData.id;
    orderData.dbId = payload.order_id || orderData.id;
    orderData.trackingCode = payload.tracking_code || orderData.trackingCode;
  } catch (error) {
    orderData.dbId = orderData.id;
    showFeedback('payment-feedback', `${error.message} Your order has been saved locally and can be confirmed once the service is reachable.`);
  }

  orders.unshift(orderData);
  saveOrders();
  renderDashboard();

  if (selectedPaymentMethod === 'cash') {
    checkoutModal.classList.add('hidden');
    clearCart();
    paymentForm.reset();
    showConfirmation(orderData);
    return;
  }

  pendingOrderId = orderData.dbId;
  const payUrl = `https://pay.beanrooted.example/checkout?order=${encodeURIComponent(orderData.id)}&amount=${encodeURIComponent(orderData.total)}`;
  const qrUrl = `https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=${encodeURIComponent(payUrl)}&choe=UTF-8`;

  qrImage.src = qrUrl;
  qrAmount.textContent = `Amount: ${formatCurrency(orderData.total)}`;
  qrInstruction.textContent = 'Your order is placed. Scan the QR to pay, then upload the receipt below.';
  checkoutModal.classList.add('hidden');
  qrModal.classList.remove('hidden');
  setPaymentStep(3);
});

function hideQrModal() {
  qrModal.classList.add('hidden');
  clearFeedback('qr-feedback');
}

confirmPaymentBtn.addEventListener('click', async () => {
  if (!pendingReceiptData) {
    alert('Please upload a photo of the receipt after scanning before confirming payment.');
    return;
  }

  clearFeedback('qr-feedback');

  let paidOrder = null;
  if (pendingOrderId) {
    const order = orders.find(item => item.dbId === pendingOrderId || item.id === String(pendingOrderId));
    if (order) {
      order.receiptImage = pendingReceiptData || order.receiptImage || '';
      order.receiptStatus = 'Pending';
      order.paymentStatus = 'Pending';
      order.status = 'Pending';
      saveOrders();
      renderDashboard();
      paidOrder = order;
    }
  }

  try {
    await postJson('controllers/create_order.php', {
      action: 'update_receipt',
      order_id: pendingOrderId,
      receipt_image: pendingReceiptData
    });
  } catch (error) {
    // Keep the confirmation flow active even if the receipt endpoint is unavailable.
  }

  pendingOrderId = null;
  pendingReceiptData = '';
  hideQrModal();
  clearCart();
  paymentForm.reset();
  if (receiptUploadInput) {
    receiptUploadInput.value = '';
  }
  if (receiptPreviewWrapper) {
    receiptPreviewWrapper.classList.add('hidden');
    receiptPreviewImage.src = '';
  }
  showConfirmation(paidOrder || orders[0]);
});

cancelPaymentBtn.addEventListener('click', () => {
  if (pendingOrderId) {
    const order = orders.find(item => item.id === pendingOrderId);
    if (order) {
      order.status = 'Cancelled';
      order.paymentStatus = 'Pending';
      saveOrders();
      renderDashboard();
    }
  }
  pendingOrderId = null;
  hideQrModal();
  checkoutModal.classList.remove('hidden');
});

closeQrBtn.addEventListener('click', () => {
  if (pendingOrderId) {
    const order = orders.find(item => item.id === pendingOrderId);
    if (order) {
      order.status = 'Cancelled';
      order.paymentStatus = 'Pending';
      saveOrders();
      renderDashboard();
    }
  }
  pendingOrderId = null;
  hideQrModal();
  checkoutModal.classList.remove('hidden');
});

categoryButtons.forEach(button => {
  button.addEventListener('click', () => {
    categoryButtons.forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');
    activeCategory = button.dataset.category;
    renderProducts(activeCategory);
  });
});

paymentMethodButtons.forEach(button => {
  button.addEventListener('click', () => {
    updatePaymentMethod(button.dataset.method);
  });
});

mapPickerBtn.addEventListener('click', useMapLocation);

portalButtons.forEach(button => {
  button.addEventListener('click', () => {
    setActiveRole(button.dataset.role);
  });
});

if (adminOrderList) {
  adminOrderList.addEventListener('change', event => {
    const select = event.target.closest('select');
    if (!select) return;
    const orderId = select.dataset.id;
    const action = select.dataset.action;
    if (action === 'status') {
      updateOrderStatus(orderId, select.value);
    }
    if (action === 'payment') {
      updatePaymentStatus(orderId, select.value);
    }
    if (action === 'receipt') {
      updateReceiptStatus(orderId, select.value);
    }
  });

  adminOrderList.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button) return;
    const orderId = button.dataset.id;
    if (button.dataset.action === 'delete') {
      orders = orders.filter(order => order.id !== orderId);
      saveOrders();
      renderDashboard();
    }
  });
}

if (cashierOrderList) {
  cashierOrderList.addEventListener('change', event => {
    const select = event.target.closest('select');
    if (!select) return;
    const orderId = select.dataset.id;
    const action = select.dataset.action;
    if (action === 'receipt') {
      updateReceiptStatus(orderId, select.value);
    }
  });

  cashierOrderList.addEventListener('click', event => {
    const button = event.target.closest('button');
    if (!button) return;
    const orderId = button.dataset.id;
    if (button.dataset.action === 'mark-paid') {
      updatePaymentStatus(orderId, 'Paid');
    }
    if (button.dataset.action === 'mark-delivery') {
      updateOrderStatus(orderId, 'On Delivery');
    }
    if (button.dataset.action === 'print') {
      const order = orders.find(item => item.id === orderId);
      if (order) {
        printReceipt(order);
      }
    }
  });
}

window.addEventListener('load', async () => {
  initializeTheme();
  await loadCategoryButtons();
  await loadPromos();
  renderProducts(activeCategory);
  updateCartDisplay();
  updatePaymentMethod(selectedPaymentMethod);
  renderDashboard();
  setActiveRole(currentRole);
});
