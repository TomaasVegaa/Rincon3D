/**
 * RINCÓN 3D - Tienda Online & Gestión de Pedidos WhatsApp
 * Experiencia interactiva para catálogo de impresiones 3D
 */

(function () {
  'use strict';

  // Configuración de negocio (con valores por defecto si no vienen del backend)
  const CONFIG = {
    whatsappPhone: window.APP_CONFIG?.whatsappPhone || '5491122334455',
    currencySymbol: window.APP_CONFIG?.currencySymbol || '$',
    shippingCost: window.APP_CONFIG?.shippingCost || 3500,
    freeShippingThreshold: window.APP_CONFIG?.freeShippingThreshold || 35000,
    bankDiscountPercent: window.APP_CONFIG?.bankDiscountPercent || 10,
    storeName: 'Rincón 3D'
  };

  // Estado global de la aplicación
  const state = {
    products: [],
    filteredProducts: [],
    currentCategory: 'all',
    searchQuery: '',
    sortBy: 'featured',
    cart: [],
    selectedProductForModal: null,
    modalSelectedImageIndex: 0,
    modalSelectedColor: '',
    modalQuantity: 1,
    bannerVisible: true
  };

  // Elementos DOM principales
  const DOM = {
    productsGrid: document.getElementById('products-grid'),
    productsCount: document.getElementById('products-count'),
    categoryPills: document.querySelectorAll('.cat-pill'),
    searchInput: document.getElementById('search-input'),
    searchClearBtn: document.getElementById('search-clear-btn'),
    sortSelect: document.getElementById('sort-select'),
    
    // Banner toggle
    heroBanner: document.getElementById('hero-banner'),
    toggleBannerBtn: document.getElementById('toggle-banner-btn'),
    toggleBannerText: document.getElementById('toggle-banner-text'),

    // Modal de detalle
    productModal: document.getElementById('product-modal'),
    modalCloseBtn: document.getElementById('modal-close-btn'),
    modalGalleryMain: document.getElementById('modal-gallery-main'),
    modalThumbnailsStrip: document.getElementById('modal-thumbnails-strip'),
    modalCategoryBadge: document.getElementById('modal-category-badge'),
    modalTitle: document.getElementById('modal-title'),
    modalPrice: document.getElementById('modal-price'),
    modalDescription: document.getElementById('modal-description'),
    modalColorsList: document.getElementById('modal-colors-list'),
    modalCustomInputGroup: document.getElementById('modal-custom-input-group'),
    modalCustomInput: document.getElementById('modal-custom-input'),
    modalQtyMinus: document.getElementById('modal-qty-minus'),
    modalQtyPlus: document.getElementById('modal-qty-plus'),
    modalQtyVal: document.getElementById('modal-qty-val'),
    modalAddCartBtn: document.getElementById('modal-add-cart-btn'),
    modalSpecsTable: document.getElementById('modal-specs-table'),

    // Carrito y Checkout Drawer
    cartTriggerBtn: document.getElementById('cart-trigger-btn'),
    cartBadgeCount: document.getElementById('cart-badge-count'),
    cartDrawerBackdrop: document.getElementById('cart-drawer-backdrop'),
    cartDrawer: document.getElementById('cart-drawer'),
    cartCloseBtn: document.getElementById('cart-close-btn'),
    cartItemsList: document.getElementById('cart-items-list'),
    cartEmptyView: document.getElementById('cart-empty-view'),
    cartFilledView: document.getElementById('cart-filled-view'),
    cartSubtotalVal: document.getElementById('cart-subtotal-val'),
    cartShippingVal: document.getElementById('cart-shipping-val'),
    cartDiscountRow: document.getElementById('cart-discount-row'),
    cartDiscountVal: document.getElementById('cart-discount-val'),
    cartTotalVal: document.getElementById('cart-total-val'),
    toCheckoutBtn: document.getElementById('to-checkout-btn'),

    // Formulario de Checkout
    checkoutView: document.getElementById('checkout-view'),
    backToCartBtn: document.getElementById('back-to-cart-btn'),
    checkoutForm: document.getElementById('checkout-form'),
    deliveryMethodSelect: document.getElementById('delivery-method'),
    addressGroup: document.getElementById('address-group'),
    paymentTransferCard: document.getElementById('pay-transfer'),
    paymentCashCard: document.getElementById('pay-cash'),
    toastContainer: document.getElementById('toast-container')
  };

  // Método auxiliar de formato de precio
  function formatMoney(amount) {
    return CONFIG.currencySymbol + Number(amount).toLocaleString('es-AR');
  }

  // Inicialización
  async function init() {
    loadCartFromStorage();
    loadBannerPreference();
    setupEventListeners();
    await loadProducts();
    updateCartUI();
  }

  // Cargar productos (desde variable embebida de PHP o fetch JSON)
  async function loadProducts() {
    try {
      if (window.INITIAL_PRODUCTS && Array.isArray(window.INITIAL_PRODUCTS) && window.INITIAL_PRODUCTS.length > 0) {
        state.products = window.INITIAL_PRODUCTS;
      } else {
        const response = await fetch('data/products.json');
        state.products = await response.json();
      }
      applyFilters();
    } catch (error) {
      console.error('Error cargando productos:', error);
      if (DOM.productsGrid) {
        DOM.productsGrid.innerHTML = `
          <div class="empty-catalog-state">
            <h3>No pudimos cargar los productos</h3>
            <p>Por favor refresca la página o intenta nuevamente más tarde.</p>
          </div>
        `;
      }
    }
  }

  // Preferencia del banner (ocultar / mostrar según audio del usuario)
  function loadBannerPreference() {
    const saved = localStorage.getItem('rincon3d_show_banner');
    if (saved !== null) {
      state.bannerVisible = saved === 'true';
    }
    updateBannerState();
  }

  function updateBannerState() {
    if (!DOM.heroBanner) return;
    if (state.bannerVisible) {
      DOM.heroBanner.classList.remove('banner-hidden');
      if (DOM.toggleBannerText) DOM.toggleBannerText.textContent = 'Ocultar marca';
    } else {
      DOM.heroBanner.classList.add('banner-hidden');
      if (DOM.toggleBannerText) DOM.toggleBannerText.textContent = 'Ver marca';
    }
    localStorage.setItem('rincon3d_show_banner', state.bannerVisible);
  }

  // Filtros, búsqueda y ordenamiento
  function applyFilters() {
    let result = [...state.products];

    // Categoría
    if (state.currentCategory !== 'all') {
      result = result.filter(p => p.category === state.currentCategory);
    }

    // Búsqueda
    if (state.searchQuery.trim() !== '') {
      const q = state.searchQuery.toLowerCase().trim();
      result = result.filter(p => {
        const inTitle = p.title.toLowerCase().includes(q);
        const inDesc = p.description.toLowerCase().includes(q);
        const inCat = p.categoryName ? p.categoryName.toLowerCase().includes(q) : false;
        return inTitle || inDesc || inCat;
      });
    }

    // Ordenamiento
    if (state.sortBy === 'price-asc') {
      result.sort((a, b) => a.price - b.price);
    } else if (state.sortBy === 'price-desc') {
      result.sort((a, b) => b.price - a.price);
    } else if (state.sortBy === 'title-asc') {
      result.sort((a, b) => a.title.localeCompare(b.title));
    }

    state.filteredProducts = result;
    renderCatalog();
  }

  // Renderizar catálogo
  function renderCatalog() {
    if (!DOM.productsGrid) return;

    if (DOM.productsCount) {
      DOM.productsCount.textContent = `${state.filteredProducts.length} ${state.filteredProducts.length === 1 ? 'producto' : 'productos'}`;
    }

    if (state.filteredProducts.length === 0) {
      DOM.productsGrid.innerHTML = `
        <div class="empty-catalog-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="8" y1="11" x2="14" y2="11"></line>
          </svg>
          <h3>No encontramos productos</h3>
          <p>Prueba con otros términos de búsqueda o selecciona otra categoría.</p>
          <button type="button" class="add-cart-btn" id="reset-filter-btn" style="margin: 0 auto;">Ver todos los productos</button>
        </div>
      `;
      const resetBtn = document.getElementById('reset-filter-btn');
      if (resetBtn) {
        resetBtn.addEventListener('click', () => {
          state.currentCategory = 'all';
          state.searchQuery = '';
          if (DOM.searchInput) DOM.searchInput.value = '';
          if (DOM.searchClearBtn) DOM.searchClearBtn.classList.remove('active');
          DOM.categoryPills.forEach(p => p.classList.toggle('active', p.dataset.category === 'all'));
          applyFilters();
        });
      }
      return;
    }

    DOM.productsGrid.innerHTML = state.filteredProducts.map(product => {
      const mainImage = product.images && product.images[0] ? product.images[0] : 'assets/img/logo.jpg';
      const colorDots = (product.colors || []).slice(0, 4).map(c => {
        let bg = '#651226';
        const cl = c.toLowerCase();
        if (cl.includes('rosa')) bg = '#E8A598';
        else if (cl.includes('blanco') || cl.includes('marfil')) bg = '#F4EDE4';
        else if (cl.includes('negro') || cl.includes('obsidiana')) bg = '#222222';
        else if (cl.includes('oro') || cl.includes('cobre')) bg = '#C5983F';
        else if (cl.includes('verde') || cl.includes('salvia')) bg = '#8A9A86';
        else if (cl.includes('gris')) bg = '#8E8E93';
        return `<span class="color-dot" style="background-color: ${bg};" title="${c}"></span>`;
      }).join('');

      return `
        <article class="product-card" data-id="${product.id}">
          <div class="product-image-container" onclick="window.RinconApp.openModal(${product.id})">
            <img src="${mainImage}" alt="${product.title}" class="product-thumb-img" loading="lazy" />
            ${product.badge ? `<span class="product-badge-tag">${product.badge}</span>` : ''}
            ${product.customizable ? `<span class="product-custom-tag">✨ Personalizable</span>` : ''}
            <div class="product-quick-view-overlay">
              <span class="quick-view-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Ver Detalle & Fotos
              </span>
            </div>
          </div>
          
          <div class="product-card-body">
            <span class="product-category-label">${product.categoryName || 'Impresión 3D'}</span>
            <h3 class="product-card-title" onclick="window.RinconApp.openModal(${product.id})">${product.title}</h3>
            <p class="product-short-desc">${product.description}</p>
            
            ${product.colors && product.colors.length > 0 ? `
              <div class="product-color-dots">
                ${colorDots}
                ${product.colors.length > 4 ? `<span class="color-dots-more">+${product.colors.length - 4}</span>` : ''}
              </div>
            ` : ''}

            <div class="product-card-footer">
              <div class="product-price-box">
                <span class="product-price-label">Precio</span>
                <span class="product-price-val">${formatMoney(product.price)}</span>
              </div>
              <button type="button" class="add-cart-btn" onclick="window.RinconApp.quickAddToCart(${product.id}, event)" aria-label="Agregar ${product.title} al carrito">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Agregar
              </button>
            </div>
          </div>
        </article>
      `;
    }).join('');
  }

  // =========================================================================
  // MODAL DE DETALLE CON PASARELA / GALERÍA DE FOTOS
  // =========================================================================
  function openProductModal(productId) {
    const product = state.products.find(p => p.id === productId);
    if (!product) return;

    state.selectedProductForModal = product;
    state.modalSelectedImageIndex = 0;
    state.modalSelectedColor = product.colors && product.colors.length > 0 ? product.colors[0] : '';
    state.modalQuantity = 1;

    // Renderizar datos básicos
    DOM.modalTitle.textContent = product.title;
    DOM.modalCategoryBadge.textContent = product.categoryName || 'Impresión 3D';
    DOM.modalPrice.textContent = formatMoney(product.price);
    DOM.modalDescription.textContent = product.description;
    DOM.modalQtyVal.textContent = '1';

    // Pasarela de fotos (Main + Miniaturas)
    renderModalGallery();

    // Colores disponibles
    renderModalColors();

    // Personalización opcional
    if (product.customizable) {
      DOM.modalCustomInputGroup.style.display = 'flex';
      DOM.modalCustomInput.value = '';
    } else {
      DOM.modalCustomInputGroup.style.display = 'none';
      DOM.modalCustomInput.value = '';
    }

    // Ficha técnica
    renderModalSpecs();

    // Mostrar modal
    DOM.productModal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function renderModalGallery() {
    const product = state.selectedProductForModal;
    const images = product.images && product.images.length > 0 ? product.images : ['assets/img/logo.jpg'];
    
    // Foto principal
    DOM.modalGalleryMain.src = images[state.modalSelectedImageIndex] || images[0];
    DOM.modalGalleryMain.alt = product.title;

    // Miniaturas
    DOM.modalThumbnailsStrip.innerHTML = images.map((img, idx) => `
      <button type="button" class="gallery-thumb-btn ${idx === state.modalSelectedImageIndex ? 'active' : ''}" 
              onclick="window.RinconApp.selectGalleryImage(${idx})"
              aria-label="Ver foto ${idx + 1} de ${product.title}">
        <img src="${img}" alt="Miniatura ${idx + 1}" />
      </button>
    `).join('');
  }

  function selectGalleryImage(index) {
    state.modalSelectedImageIndex = index;
    const images = state.selectedProductForModal.images || [];
    if (images[index]) {
      DOM.modalGalleryMain.style.opacity = '0.3';
      setTimeout(() => {
        DOM.modalGalleryMain.src = images[index];
        DOM.modalGalleryMain.style.opacity = '1';
      }, 120);
    }
    const thumbs = DOM.modalThumbnailsStrip.querySelectorAll('.gallery-thumb-btn');
    thumbs.forEach((t, i) => t.classList.toggle('active', i === index));
  }

  function renderModalColors() {
    const product = state.selectedProductForModal;
    if (!product.colors || product.colors.length === 0) {
      DOM.modalColorsList.innerHTML = `<span style="font-size:0.85rem;color:var(--text-muted);">Estándar de fábrica</span>`;
      return;
    }

    DOM.modalColorsList.innerHTML = product.colors.map(color => `
      <button type="button" class="color-option-chip ${color === state.modalSelectedColor ? 'selected' : ''}"
              onclick="window.RinconApp.selectProductColor('${color.replace(/'/g, "\\'")}')">
        ${color}
      </button>
    `).join('');
  }

  function selectProductColor(colorName) {
    state.modalSelectedColor = colorName;
    const chips = DOM.modalColorsList.querySelectorAll('.color-option-chip');
    chips.forEach(c => c.classList.toggle('selected', c.textContent.trim() === colorName));
  }

  function renderModalSpecs() {
    const product = state.selectedProductForModal;
    if (!product.specs) {
      DOM.modalSpecsTable.innerHTML = '';
      return;
    }
    DOM.modalSpecsTable.innerHTML = Object.entries(product.specs).map(([key, value]) => `
      <tr>
        <td class="specs-label">${key}</td>
        <td class="specs-value">${value}</td>
      </tr>
    `).join('');
  }

  function closeModal() {
    DOM.productModal.classList.remove('active');
    document.body.style.overflow = '';
    state.selectedProductForModal = null;
  }

  // =========================================================================
  // CARRITO & LOCALSTORAGE
  // =========================================================================
  function loadCartFromStorage() {
    try {
      const saved = localStorage.getItem('rincon3d_cart');
      if (saved) {
        state.cart = JSON.parse(saved);
      }
    } catch (e) {
      state.cart = [];
    }
  }

  function saveCartToStorage() {
    localStorage.setItem('rincon3d_cart', JSON.stringify(state.cart));
    updateCartUI();
  }

  function quickAddToCart(productId, event) {
    if (event) event.stopPropagation();
    const product = state.products.find(p => p.id === productId);
    if (!product) return;

    const defaultColor = product.colors && product.colors.length > 0 ? product.colors[0] : '';
    addToCart(product, 1, defaultColor, '');
  }

  function addModalToCart() {
    if (!state.selectedProductForModal) return;
    const product = state.selectedProductForModal;
    const qty = state.modalQuantity || 1;
    const color = state.modalSelectedColor || (product.colors ? product.colors[0] : '');
    const customText = DOM.modalCustomInput ? DOM.modalCustomInput.value.trim() : '';

    addToCart(product, qty, color, customText);
    closeModal();
  }

  function addToCart(product, quantity, color, customText) {
    // Clave única según producto, color y personalización
    const cartItemId = `${product.id}_${color}_${customText}`.replace(/\s+/g, '_');
    const existingIndex = state.cart.findIndex(item => item.cartItemId === cartItemId);

    if (existingIndex > -1) {
      state.cart[existingIndex].quantity += quantity;
    } else {
      state.cart.push({
        cartItemId,
        productId: product.id,
        title: product.title,
        price: product.price,
        image: product.images && product.images[0] ? product.images[0] : 'assets/img/logo.jpg',
        color: color,
        customText: customText,
        quantity: quantity
      });
    }

    saveCartToStorage();
    showToast(`✨ Se agregó <strong>${product.title}</strong> al carrito`);

    // Animación del botón del carrito
    if (DOM.cartTriggerBtn) {
      DOM.cartTriggerBtn.style.transform = 'scale(1.15)';
      setTimeout(() => {
        DOM.cartTriggerBtn.style.transform = '';
      }, 250);
    }
  }

  function updateCartItemQuantity(cartItemId, delta) {
    const item = state.cart.find(i => i.cartItemId === cartItemId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
      state.cart = state.cart.filter(i => i.cartItemId !== cartItemId);
    }
    saveCartToStorage();
  }

  function removeCartItem(cartItemId) {
    state.cart = state.cart.filter(i => i.cartItemId !== cartItemId);
    saveCartToStorage();
    showToast('Producto eliminado del carrito');
  }

  function calculateCartTotals() {
    const subtotal = state.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    // Método de entrega actual
    const deliveryMethod = DOM.deliveryMethodSelect ? DOM.deliveryMethodSelect.value : 'envio';
    let shipping = 0;
    if (deliveryMethod === 'envio' && subtotal > 0) {
      shipping = subtotal >= CONFIG.freeShippingThreshold ? 0 : CONFIG.shippingCost;
    }

    // Método de pago actual
    const isTransfer = DOM.paymentTransferCard ? DOM.paymentTransferCard.classList.contains('selected') : true;
    let discount = 0;
    if (isTransfer && subtotal > 0) {
      discount = Math.round(subtotal * (CONFIG.bankDiscountPercent / 100));
    }

    const total = Math.max(0, subtotal + shipping - discount);

    return { subtotal, shipping, discount, total, isTransfer, deliveryMethod };
  }

  function updateCartUI() {
    const totalCount = state.cart.reduce((acc, item) => acc + item.quantity, 0);
    
    // Badge navbar
    if (DOM.cartBadgeCount) {
      DOM.cartBadgeCount.textContent = totalCount;
      DOM.cartBadgeCount.style.display = totalCount > 0 ? 'inline-block' : 'none';
    }

    // Vistas del drawer
    if (state.cart.length === 0) {
      if (DOM.cartEmptyView) DOM.cartEmptyView.style.display = 'block';
      if (DOM.cartFilledView) DOM.cartFilledView.style.display = 'none';
    } else {
      if (DOM.cartEmptyView) DOM.cartEmptyView.style.display = 'none';
      if (DOM.cartFilledView) DOM.cartFilledView.style.display = 'flex';
    }

    // Renderizar items
    if (DOM.cartItemsList) {
      DOM.cartItemsList.innerHTML = state.cart.map(item => `
        <div class="cart-item">
          <img src="${item.image}" alt="${item.title}" class="cart-item-thumb" />
          <div class="cart-item-info">
            <h4 class="cart-item-title">${item.title}</h4>
            ${item.color ? `<span class="cart-item-meta">🎨 Color: ${item.color}</span>` : ''}
            ${item.customText ? `<span class="cart-item-meta">✏️ Nota: "${item.customText}"</span>` : ''}
            <span class="cart-item-price">${formatMoney(item.price * item.quantity)}</span>
          </div>
          <div class="cart-item-actions">
            <button type="button" class="cart-remove-btn" onclick="window.RinconApp.removeCartItem('${item.cartItemId}')" title="Eliminar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
            <div class="quantity-stepper" style="transform: scale(0.85); transform-origin: right center;">
              <button type="button" class="qty-btn" onclick="window.RinconApp.updateQty('${item.cartItemId}', -1)">−</button>
              <span class="qty-number">${item.quantity}</span>
              <button type="button" class="qty-btn" onclick="window.RinconApp.updateQty('${item.cartItemId}', 1)">+</button>
            </div>
          </div>
        </div>
      `).join('');
    }

    // Totales
    const totals = calculateCartTotals();
    if (DOM.cartSubtotalVal) DOM.cartSubtotalVal.textContent = formatMoney(totals.subtotal);
    if (DOM.cartShippingVal) {
      if (totals.deliveryMethod === 'retiro') {
        DOM.cartShippingVal.textContent = 'Gratis (Retiro en Taller)';
      } else {
        DOM.cartShippingVal.textContent = totals.shipping === 0 ? '¡GRATIS!' : formatMoney(totals.shipping);
      }
    }
    if (DOM.cartDiscountRow) {
      if (totals.discount > 0) {
        DOM.cartDiscountRow.style.display = 'flex';
        DOM.cartDiscountVal.textContent = `-${formatMoney(totals.discount)}`;
      } else {
        DOM.cartDiscountRow.style.display = 'none';
      }
    }
    if (DOM.cartTotalVal) DOM.cartTotalVal.textContent = formatMoney(totals.total);
  }

  function toggleCartDrawer(open) {
    const shouldOpen = open !== undefined ? open : !DOM.cartDrawer.classList.contains('active');
    if (shouldOpen) {
      DOM.cartDrawerBackdrop.classList.add('active');
      DOM.cartDrawer.classList.add('active');
      document.body.style.overflow = 'hidden';
      // Reiniciar a vista de lista
      showCartListView();
    } else {
      DOM.cartDrawerBackdrop.classList.remove('active');
      DOM.cartDrawer.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  function showCartListView() {
    if (DOM.cartFilledView) DOM.cartFilledView.style.display = state.cart.length > 0 ? 'flex' : 'none';
    if (DOM.checkoutView) DOM.checkoutView.classList.remove('active');
  }

  function showCheckoutView() {
    if (state.cart.length === 0) return;
    if (DOM.cartFilledView) DOM.cartFilledView.style.display = 'none';
    if (DOM.checkoutView) DOM.checkoutView.classList.add('active');
    updateCartUI();
  }

  // =========================================================================
  // ENVÍO DE PEDIDO POR WHATSAPP (REQUERIMIENTO CLAVE)
  // =========================================================================
  function handleCheckoutSubmit(e) {
    e.preventDefault();

    if (state.cart.length === 0) {
      showToast('Tu carrito está vacío');
      return;
    }

    const nameInput = document.getElementById('client-name');
    const phoneInput = document.getElementById('client-phone');
    const streetInput = document.getElementById('client-address');
    const notesInput = document.getElementById('client-notes');

    const name = nameInput ? nameInput.value.trim() : '';
    const phone = phoneInput ? phoneInput.value.trim() : '';
    const address = streetInput ? streetInput.value.trim() : '';
    const notes = notesInput ? notesInput.value.trim() : '';
    const delivery = DOM.deliveryMethodSelect ? DOM.deliveryMethodSelect.value : 'envio';
    const isTransfer = DOM.paymentTransferCard ? DOM.paymentTransferCard.classList.contains('selected') : true;

    if (!name) {
      showToast('⚠️ Por favor ingresa tu nombre');
      if (nameInput) nameInput.focus();
      return;
    }

    if (!phone) {
      showToast('⚠️ Por favor ingresa tu WhatsApp de contacto');
      if (phoneInput) phoneInput.focus();
      return;
    }

    if (delivery === 'envio' && !address) {
      showToast('⚠️ Por favor ingresa tu domicilio para la entrega');
      if (streetInput) streetInput.focus();
      return;
    }

    const totals = calculateCartTotals();
    const paymentMethodText = isTransfer 
      ? `Transferencia Bancaria (Aplica ${CONFIG.bankDiscountPercent}% OFF)` 
      : 'Efectivo';

    const deliveryText = delivery === 'envio' 
      ? `Envío a Domicilio (${address})` 
      : 'Retiro gratuito por el taller';

    // Guardar pedido en Base de Datos de forma asíncrona
    let orderCode = 'R3D-' + Math.random().toString(36).substring(2, 8).toUpperCase();
    try {
      fetch('api/save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          clientName: name,
          clientPhone: phone,
          deliveryMethod: delivery,
          clientAddress: address,
          paymentMethod: isTransfer ? 'transferencia' : 'efectivo',
          subtotal: totals.subtotal,
          shipping: totals.shipping,
          discount: totals.discount,
          total: totals.total,
          clientNotes: notes,
          cart: state.cart
        })
      }).then(res => res.json()).then(data => {
        if (data && data.codigoPedido) {
          orderCode = data.codigoPedido;
        }
      }).catch(err => console.log('Guardado local en proceso:', err));
    } catch (e) {
      console.warn('API error:', e);
    }

    // Construcción del mensaje elegante y estructurado para WhatsApp
    let message = `✨ *NUEVO PEDIDO - ${CONFIG.storeName.toUpperCase()}* ✨\n`;
    message += `🔖 *Código de Pedido:* #${orderCode}\n`;
    message += `───────────────────────\n`;
    message += `👤 *Cliente:* ${name}\n`;
    message += `📱 *Teléfono:* ${phone}\n`;
    message += `📍 *Entrega:* ${deliveryText}\n`;
    message += `💳 *Forma de Pago:* ${paymentMethodText}\n`;
    message += `───────────────────────\n\n`;

    message += `🛍️ *PRODUCTOS SELECCIONADOS:*\n`;
    state.cart.forEach((item, index) => {
      message += `${index + 1}. *${item.title}* x${item.quantity}\n`;
      if (item.color) message += `   • Color: ${item.color}\n`;
      if (item.customText) message += `   • Personalización: "${item.customText}"\n`;
      message += `   • Subtotal: ${formatMoney(item.price * item.quantity)}\n\n`;
    });

    message += `───────────────────────\n`;
    message += `📦 *Subtotal:* ${formatMoney(totals.subtotal)}\n`;
    if (delivery === 'envio') {
      message += `🚚 *Envío:* ${totals.shipping === 0 ? '¡GRATIS!' : formatMoney(totals.shipping)}\n`;
    }
    if (totals.discount > 0) {
      message += `🏷️ *Descuento transferencia:* -${formatMoney(totals.discount)}\n`;
    }
    message += `💰 *TOTAL A ABONAR:* ${formatMoney(totals.total)}\n`;
    message += `───────────────────────\n`;

    if (notes) {
      message += `📝 *Aclaraciones / Notas:* ${notes}\n`;
    }

    message += `\n¡Hola! Acabo de armar mi pedido en la tienda online y quiero coordinar los detalles. Muchas gracias 😊`;

    // Codificar URL para WhatsApp
    const encoded = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${CONFIG.whatsappPhone}?text=${encoded}`;

    // Abrir WhatsApp en nueva pestaña
    window.open(whatsappUrl, '_blank');

    // Vaciar carrito tras envío exitoso
    state.cart = [];
    saveCartToStorage();
    toggleCartDrawer(false);

    showToast('🎉 ¡Pedido registrado y enviado a WhatsApp!');
  }

  // =========================================================================
  // SISTEMA DE NOTIFICACIONES TOAST
  // =========================================================================
  function showToast(htmlMessage) {
    if (!DOM.toastContainer) return;

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <div class="toast-content">${htmlMessage}</div>
    `;

    DOM.toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 350);
    }, 3500);
  }

  // =========================================================================
  // CONFIGURACIÓN DE EVENTOS
  // =========================================================================
  function setupEventListeners() {
    // Categorías
    DOM.categoryPills.forEach(pill => {
      pill.addEventListener('click', () => {
        DOM.categoryPills.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        state.currentCategory = pill.dataset.category || 'all';
        applyFilters();
      });
    });

    // Búsqueda
    if (DOM.searchInput) {
      DOM.searchInput.addEventListener('input', e => {
        state.searchQuery = e.target.value;
        if (DOM.searchClearBtn) {
          DOM.searchClearBtn.classList.toggle('active', state.searchQuery.length > 0);
        }
        applyFilters();
      });
    }

    if (DOM.searchClearBtn) {
      DOM.searchClearBtn.addEventListener('click', () => {
        if (DOM.searchInput) {
          DOM.searchInput.value = '';
          DOM.searchInput.focus();
        }
        state.searchQuery = '';
        DOM.searchClearBtn.classList.remove('active');
        applyFilters();
      });
    }

    // Ordenamiento
    if (DOM.sortSelect) {
      DOM.sortSelect.addEventListener('change', e => {
        state.sortBy = e.target.value;
        applyFilters();
      });
    }

    // Toggle banner de marca (pedido en audio)
    if (DOM.toggleBannerBtn) {
      DOM.toggleBannerBtn.addEventListener('click', () => {
        state.bannerVisible = !state.bannerVisible;
        updateBannerState();
      });
    }

    // Modal de producto
    if (DOM.modalCloseBtn) DOM.modalCloseBtn.addEventListener('click', closeModal);
    if (DOM.productModal) {
      DOM.productModal.addEventListener('click', e => {
        if (e.target === DOM.productModal) closeModal();
      });
    }

    // Cantidad en modal
    if (DOM.modalQtyMinus) {
      DOM.modalQtyMinus.addEventListener('click', () => {
        if (state.modalQuantity > 1) {
          state.modalQuantity--;
          DOM.modalQtyVal.textContent = state.modalQuantity;
        }
      });
    }
    if (DOM.modalQtyPlus) {
      DOM.modalQtyPlus.addEventListener('click', () => {
        state.modalQuantity++;
        DOM.modalQtyVal.textContent = state.modalQuantity;
      });
    }
    if (DOM.modalAddCartBtn) {
      DOM.modalAddCartBtn.addEventListener('click', addModalToCart);
    }

    // Drawer del carrito
    if (DOM.cartTriggerBtn) DOM.cartTriggerBtn.addEventListener('click', () => toggleCartDrawer(true));
    if (DOM.cartCloseBtn) DOM.cartCloseBtn.addEventListener('click', () => toggleCartDrawer(false));
    if (DOM.cartDrawerBackdrop) DOM.cartDrawerBackdrop.addEventListener('click', () => toggleCartDrawer(false));

    // Pasos checkout
    if (DOM.toCheckoutBtn) DOM.toCheckoutBtn.addEventListener('click', showCheckoutView);
    if (DOM.backToCartBtn) DOM.backToCartBtn.addEventListener('click', showCartListView);

    // Métodos de pago
    if (DOM.paymentTransferCard && DOM.paymentCashCard) {
      DOM.paymentTransferCard.addEventListener('click', () => {
        DOM.paymentTransferCard.classList.add('selected');
        DOM.paymentCashCard.classList.remove('selected');
        updateCartUI();
      });
      DOM.paymentCashCard.addEventListener('click', () => {
        DOM.paymentCashCard.classList.add('selected');
        DOM.paymentTransferCard.classList.remove('selected');
        updateCartUI();
      });
    }

    // Método de entrega
    if (DOM.deliveryMethodSelect) {
      DOM.deliveryMethodSelect.addEventListener('change', e => {
        if (DOM.addressGroup) {
          DOM.addressGroup.style.display = e.target.value === 'retiro' ? 'none' : 'flex';
        }
        updateCartUI();
      });
    }

    // Enviar por WhatsApp
    if (DOM.checkoutForm) {
      DOM.checkoutForm.addEventListener('submit', handleCheckoutSubmit);
    }

    // Tecla ESC para cerrar modales
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        if (DOM.productModal && DOM.productModal.classList.contains('active')) {
          closeModal();
        } else if (DOM.cartDrawer && DOM.cartDrawer.classList.contains('active')) {
          toggleCartDrawer(false);
        }
      }
    });
  }

  // Exponer API para eventos en el DOM (onclicks)
  window.RinconApp = {
    openModal: openProductModal,
    closeModal: closeModal,
    selectGalleryImage: selectGalleryImage,
    selectProductColor: selectProductColor,
    quickAddToCart: quickAddToCart,
    updateQty: updateCartItemQuantity,
    removeCartItem: removeCartItem,
    toggleCart: toggleCartDrawer
  };

  // Arrancar aplicación al cargar el DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
