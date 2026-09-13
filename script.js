document.addEventListener('DOMContentLoaded', () => {
  const toast = document.getElementById('toast');
  const bagCount = document.getElementById('bagCount');
  
  // Load full cart items from localStorage (or empty array)
  let cart = JSON.parse(localStorage.getItem('lunora_cart')) || [];

  function showToast(message) {
    toast.textContent = message;
    toast.classList.add('is-visible');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toast.classList.remove('is-visible'), 2200);
  }

  function updateBagCounter() {
    if (!bagCount) return;
    // Count total quantities across all items
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    bagCount.textContent = totalItems;
  }
  updateBagCounter();

  // ==========================================
  // INDEX.PHP - Wishlist (persisted) & Quick Add Logic
  // ==========================================

  let wishlist = JSON.parse(localStorage.getItem('lunora_wishlist')) || [];
  const wishCountEl = document.getElementById('wishCount');
  const wishToggle  = document.getElementById('wishToggle');
  const wishPanel   = document.getElementById('wishPanel');
  const wishItemsEl = document.getElementById('wishPanelItems');

  function persistWishlist() {
    localStorage.setItem('lunora_wishlist', JSON.stringify(wishlist));
  }

  function updateWishBadge() {
    if (!wishCountEl) return;
    wishCountEl.textContent = wishlist.length;
    wishCountEl.hidden = wishlist.length === 0;
  }

  function renderWishPanel() {
    if (!wishItemsEl) return;
    if (wishlist.length === 0) {
      wishItemsEl.innerHTML = '<p class="wish-panel__empty">Your wishlist is empty.</p>';
      return;
    }
    wishItemsEl.innerHTML = wishlist.map((item, idx) => `
      <div class="wish-panel__item">
        <img src="${item.img}" alt="${item.name}">
        <div class="wish-panel__item-info">
          <span class="wish-panel__item-name">${item.name}</span>
          <span class="wish-panel__item-price">US$${item.price.toFixed(2)}</span>
        </div>
        <button type="button" class="wish-panel__move" data-move="${idx}" title="Move to bag">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        </button>
        <button type="button" class="wish-panel__remove" data-remove="${idx}" aria-label="Remove">&times;</button>
      </div>
    `).join('');
  }

  function syncWishButtons() {
    document.querySelectorAll('[data-wish]').forEach((btn) => {
      const card = btn.closest('.product-card');
      const id = card ? card.dataset.id : null;
      btn.classList.toggle('is-active', !!id && wishlist.some(w => w.productId === id));
    });
  }

  document.querySelectorAll('[data-wish]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const card = btn.closest('.product-card');
      const productId = card ? card.dataset.id : '';
      const name = card ? card.querySelector('.product-name')?.textContent.trim() : 'Item';
      const price = card ? parseFloat(card.dataset.price || card.querySelector('.product-price')?.textContent.replace(/[^0-9.]/g, '') || '0') : 0;
      const img = card ? card.querySelector('.product-photo__icon')?.src : '';

      const idx = wishlist.findIndex(w => w.productId === productId);
      if (idx > -1) {
        wishlist.splice(idx, 1);
        btn.classList.remove('is-active');
        showToast(`Removed ${name} from wishlist`);
      } else {
        wishlist.push({ productId, name, price, img });
        btn.classList.add('is-active');
        showToast(`Added ${name} to wishlist`);
      }
      persistWishlist();
      updateWishBadge();
      renderWishPanel();
    });
  });
  syncWishButtons();
  updateWishBadge();
  renderWishPanel();

  if (wishToggle && wishPanel) {
    wishToggle.addEventListener('click', (e) => {
      e.preventDefault();
      const isOpen = !wishPanel.hidden;
      wishPanel.hidden = isOpen;
      wishToggle.setAttribute('aria-expanded', String(!isOpen));
    });
    document.addEventListener('click', (e) => {
      if (!wishPanel.hidden && !wishPanel.contains(e.target) && !wishToggle.contains(e.target)) {
        wishPanel.hidden = true;
        wishToggle.setAttribute('aria-expanded', 'false');
      }
    });
    wishItemsEl.addEventListener('click', (e) => {
      const removeIdx = e.target.closest('[data-remove]')?.dataset.remove;
      const moveIdx = e.target.closest('[data-move]')?.dataset.move;
      if (removeIdx !== undefined) {
        const item = wishlist[removeIdx];
        wishlist.splice(removeIdx, 1);
        persistWishlist();
        updateWishBadge();
        renderWishPanel();
        syncWishButtons();
        if (item) showToast(`Removed ${item.name} from wishlist`);
      } else if (moveIdx !== undefined) {
        const item = wishlist[moveIdx];
        if (item) {
          const existing = cart.find(c => c.productId === item.productId && !c.color);
          if (existing) { existing.quantity += 1; } else { cart.push({ productId: item.productId, name: item.name, price: item.price, img: item.img, color: '', quantity: 1 }); }
          localStorage.setItem('lunora_cart', JSON.stringify(cart));
          updateBagCounter();
          wishlist.splice(moveIdx, 1);
          persistWishlist();
          updateWishBadge();
          renderWishPanel();
          syncWishButtons();
          showToast(`${item.name} moved to your bag`);
        }
      }
    });
  }

  // ------------------------------------------------------------
  // Quick Add modal — let the shopper pick a color before it's
  // added to the bag, instead of adding the first variant blind.
  // ------------------------------------------------------------
  const qaOverlay    = document.getElementById('qaModalOverlay');
  const qaImg        = document.getElementById('qaModalImg');
  const qaTitle      = document.getElementById('qaModalTitle');
  const qaPrice      = document.getElementById('qaModalPrice');
  const qaColors     = document.getElementById('qaModalColors');
  const qaColorName  = document.getElementById('qaModalColorName');
  const qaSwatches   = document.getElementById('qaModalSwatches');
  const qaQty        = document.getElementById('qaModalQty');
  const qaAddBtn     = document.getElementById('qaModalAdd');
  const qaCloseBtn   = document.getElementById('qaModalClose');
  const tonesMap     = window.LUNORA_TONES || {};

  let qaPending = null; // { productId, name, price, img, toneKey, toneLabel }

  function addToCart({ productId, name, price, img, color, quantity }) {
    const existingItem = cart.find(i =>
      (productId && i.productId === productId && (i.color || '') === (color || ''))
      || (!productId && i.name === name && (i.color || '') === (color || ''))
    );
    const qty = quantity || 1;
    if (existingItem) {
      existingItem.quantity += qty;
    } else {
      cart.push({ productId, name, price, img, color: color || '', quantity: qty });
    }
    localStorage.setItem('lunora_cart', JSON.stringify(cart));
    updateBagCounter();

    // Gentle one-time nudge for guests: doesn't block adding to the bag,
    // just lets them know they'll need to log in when they reach checkout.
    const isGuest = typeof window.LUNORA_LOGGED_IN !== 'undefined' && !window.LUNORA_LOGGED_IN;
    const nudgeShown = sessionStorage.getItem('lunora_guest_nudge_shown') === '1';
    if (isGuest && !nudgeShown) {
      sessionStorage.setItem('lunora_guest_nudge_shown', '1');
      showToast('Added to your bag — you\'ll need to log in or sign up at checkout.');
    } else {
      showToast(color ? `${name} (${color}) added to your bag` : `${name} added to your bag`);
    }
  }

  function openQuickAddModal({ productId, name, price, img, tones }) {
    qaPending = { productId, name, price, img };
    qaImg.src = img;
    qaImg.alt = name;
    qaTitle.textContent = name;
    qaPrice.textContent = `US$${price.toFixed(2)}`;
    if (qaQty) qaQty.value = '1';

    if (tones && tones.length) {
      qaColors.style.display = '';
      qaSwatches.innerHTML = '';
      let selectedKey = tones[0];
      tones.forEach((toneKey) => {
        const tone = tonesMap[toneKey];
        if (!tone) return;
        const sw = document.createElement('button');
        sw.type = 'button';
        sw.className = 'qa-swatch';
        sw.style.setProperty('--sw', tone.hex);
        sw.title = tone.label;
        sw.setAttribute('aria-label', tone.label);
        if (toneKey === selectedKey) sw.classList.add('is-selected');
        sw.addEventListener('click', () => {
          selectedKey = toneKey;
          qaPending.toneKey = toneKey;
          qaPending.toneLabel = tone.label;
          qaColorName.textContent = tone.label;
          qaSwatches.querySelectorAll('.qa-swatch').forEach(s => s.classList.remove('is-selected'));
          sw.classList.add('is-selected');
        });
        qaSwatches.appendChild(sw);
      });
      const firstTone = tonesMap[selectedKey];
      qaPending.toneKey = selectedKey;
      qaPending.toneLabel = firstTone ? firstTone.label : '';
      qaColorName.textContent = qaPending.toneLabel;
    } else {
      qaColors.style.display = 'none';
      qaPending.toneKey = null;
      qaPending.toneLabel = '';
    }

    qaOverlay.hidden = false;
    requestAnimationFrame(() => qaOverlay.classList.add('is-open'));
  }

  function closeQuickAddModal() {
    qaOverlay.classList.remove('is-open');
    setTimeout(() => { qaOverlay.hidden = true; }, 200);
    qaPending = null;
  }

  if (qaOverlay) {
    qaCloseBtn.addEventListener('click', closeQuickAddModal);
    qaOverlay.addEventListener('click', (e) => { if (e.target === qaOverlay) closeQuickAddModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !qaOverlay.hidden) closeQuickAddModal(); });
    qaAddBtn.addEventListener('click', () => {
      if (!qaPending) return;
      addToCart({
        productId: qaPending.productId,
        name: qaPending.name,
        price: qaPending.price,
        img: qaPending.img,
        color: qaPending.toneLabel,
        quantity: qaQty ? parseInt(qaQty.value, 10) || 1 : 1,
      });
      closeQuickAddModal();
    });
  }

  document.querySelectorAll('[data-add]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const card = btn.closest('.product-card');
      const productId = btn.dataset.id || card.dataset.id || '';
      const name = btn.dataset.name;
      const price = parseFloat(card.dataset.price || card.querySelector('.product-price').textContent.replace(/[^0-9.]/g, ''));
      const img = card.querySelector('.product-photo__icon').src;
      const tones = (btn.dataset.tones || '').split(',').map(t => t.trim()).filter(Boolean);

      if (tones.length) {
        openQuickAddModal({ productId, name, price, img, tones });
      } else {
        addToCart({ productId, name, price, img, color: '' });
      }
    });
  });

  // Navigate to checkout when bag icon is clicked on the homepage
  const bagToggle = document.getElementById('bagToggle');
  if (bagToggle && window.location.pathname.includes('index.php')) {
    bagToggle.addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = 'checkout.php';
    });
  }

  // ------------------------------------------------------------
  // Product grid: search, category filters, price/stock filters,
  // and sort — all client-side over the cards already in the DOM.
  // ------------------------------------------------------------
  const productGrid = document.getElementById('productGrid');
  if (productGrid) {
    const allCards = Array.from(productGrid.querySelectorAll('.product-card'));
    const originalOrder = allCards.slice();
    const resultsCountEl = document.getElementById('resultsCount');
    const noResultsEl = document.getElementById('noResultsMessage');

    const gridState = {
      search: '',
      categories: new Set(),
      inStockOnly: false,
      priceMin: null,
      priceMax: null,
      sort: 'Featured',
      trendingOnly: false,
    };

    function cardMatchesText(card, term) {
      if (!term) return true;
      const haystack = (
        (card.querySelector('.product-name')?.textContent || '') + ' ' +
        (card.dataset.category || '')
      ).toLowerCase();
      return haystack.includes(term.toLowerCase());
    }

    function renderGrid() {
      let visible = allCards.filter((card) => {
        if (gridState.trendingOnly && card.dataset.new !== '1') return false;
        if (gridState.inStockOnly && card.dataset.stock === '0') return false;
        const price = parseFloat(card.dataset.price || '0');
        if (gridState.priceMin !== null && price < gridState.priceMin) return false;
        if (gridState.priceMax !== null && price > gridState.priceMax) return false;
        if (gridState.categories.size > 0 && !gridState.categories.has(card.dataset.category)) return false;
        if (!cardMatchesText(card, gridState.search)) return false;
        return true;
      });

      // Sort (only affects order of the visible set)
      if (gridState.sort === 'Price: Low to High') {
        visible.sort((a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
      } else if (gridState.sort === 'Price: High to Low') {
        visible.sort((a, b) => parseFloat(b.dataset.price) - parseFloat(a.dataset.price));
      } else if (gridState.sort === 'Newest') {
        visible.sort((a, b) => originalOrder.indexOf(b) - originalOrder.indexOf(a));
      } else {
        visible.sort((a, b) => originalOrder.indexOf(a) - originalOrder.indexOf(b));
      }

      allCards.forEach(card => { card.style.display = 'none'; });
      visible.forEach(card => {
        card.style.display = '';
        productGrid.appendChild(card);
      });

      if (resultsCountEl) {
        resultsCountEl.textContent = `Showing ${visible.length} of ${allCards.length} item(s)`;
      }
      if (noResultsEl) {
        noResultsEl.hidden = visible.length !== 0;
      }
    }
    renderGrid();

    // Sort dropdown
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        gridState.sort = sortSelect.value;
        renderGrid();
      });
    }

    // Filter panel toggle
    const filterToggle = document.getElementById('filterToggle');
    const filterPanel = document.getElementById('filterPanel');
    if (filterToggle && filterPanel) {
      filterToggle.addEventListener('click', () => {
        const isOpen = !filterPanel.hidden;
        filterPanel.hidden = isOpen;
        filterToggle.setAttribute('aria-expanded', String(!isOpen));
      });
    }

    const categoryChecks = document.querySelectorAll('input[name="filterCategory"]');
    function syncCategoryChecks() {
      categoryChecks.forEach(cb => { cb.checked = gridState.categories.has(cb.value); });
    }
    categoryChecks.forEach(cb => {
      cb.addEventListener('change', () => {
        if (cb.checked) gridState.categories.add(cb.value); else gridState.categories.delete(cb.value);
        renderGrid();
      });
    });

    const inStockCheck = document.getElementById('filterInStock');
    if (inStockCheck) {
      inStockCheck.addEventListener('change', () => {
        gridState.inStockOnly = inStockCheck.checked;
        renderGrid();
      });
    }

    const priceMinInput = document.getElementById('filterPriceMin');
    const priceMaxInput = document.getElementById('filterPriceMax');
    if (priceMinInput) priceMinInput.addEventListener('input', () => {
      gridState.priceMin = priceMinInput.value === '' ? null : parseFloat(priceMinInput.value);
      renderGrid();
    });
    if (priceMaxInput) priceMaxInput.addEventListener('input', () => {
      gridState.priceMax = priceMaxInput.value === '' ? null : parseFloat(priceMaxInput.value);
      renderGrid();
    });

    function clearAllFilters() {
      gridState.search = '';
      gridState.categories.clear();
      gridState.inStockOnly = false;
      gridState.priceMin = null;
      gridState.priceMax = null;
      gridState.trendingOnly = false;
      if (searchInput) searchInput.value = '';
      if (inStockCheck) inStockCheck.checked = false;
      if (priceMinInput) priceMinInput.value = '';
      if (priceMaxInput) priceMaxInput.value = '';
      syncCategoryChecks();
      document.querySelectorAll('#subnavCategories a').forEach(a => a.classList.remove('is-current'));
      renderGrid();
    }

    const filterClear = document.getElementById('filterClear');
    if (filterClear) filterClear.addEventListener('click', clearAllFilters);
    const noResultsClear = document.getElementById('noResultsClear');
    if (noResultsClear) noResultsClear.addEventListener('click', clearAllFilters);

    // Search bar (header magnifying-glass icon)
    const searchToggle = document.getElementById('searchToggle');
    const searchBar = document.getElementById('searchBar');
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    if (searchToggle && searchBar && searchInput) {
      searchToggle.addEventListener('click', () => {
        const isOpen = !searchBar.hidden;
        searchBar.hidden = isOpen;
        searchToggle.setAttribute('aria-expanded', String(!isOpen));
        if (!isOpen) searchInput.focus();
      });
      searchInput.addEventListener('input', () => {
        gridState.search = searchInput.value.trim();
        renderGrid();
      });
      if (searchClear) searchClear.addEventListener('click', () => {
        searchInput.value = '';
        gridState.search = '';
        renderGrid();
        searchInput.focus();
      });
    }

    // Subnav category tabs
    document.querySelectorAll('#subnavCategories a').forEach((tab) => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('#subnavCategories a').forEach(a => a.classList.remove('is-current'));
        tab.classList.add('is-current');
        gridState.categories = new Set([tab.dataset.category]);
        syncCategoryChecks();
        renderGrid();
        productGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    // Editorial chips ("9 to 5", "Uni Bags", "Suede", "Trending Now")
    document.querySelectorAll('.chip[data-chip-term]').forEach((chip) => {
      chip.addEventListener('click', (e) => {
        e.preventDefault();
        const term = chip.dataset.chipTerm;
        gridState.categories.clear();
        document.querySelectorAll('#subnavCategories a').forEach(a => a.classList.remove('is-current'));
        syncCategoryChecks();
        if (term === '__trending__') {
          gridState.trendingOnly = true;
          gridState.search = '';
          if (searchInput) searchInput.value = '';
          const anyTrending = allCards.some(c => c.dataset.new === '1');
          if (!anyTrending) showToast('Nothing trending right now — try adding a badge to a product in the admin panel.');
        } else {
          gridState.trendingOnly = false;
          gridState.search = term;
          if (searchInput) searchInput.value = term;
        }
        renderGrid();
        productGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    // Primary nav: Shop / New In / On Campus
    const navShop = document.getElementById('navShop');
    const navNewIn = document.getElementById('navNewIn');
    function setActiveNav(link) {
      [navShop, navNewIn].forEach(a => a && a.classList.remove('active'));
      if (link) link.classList.add('active');
    }
    if (navShop) {
      navShop.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveNav(navShop);
        clearAllFilters();
        productGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
    if (navNewIn) {
      navNewIn.addEventListener('click', (e) => {
        e.preventDefault();
        setActiveNav(navNewIn);
        gridState.trendingOnly = true;
        gridState.search = '';
        gridState.categories.clear();
        if (searchInput) searchInput.value = '';
        document.querySelectorAll('#subnavCategories a').forEach(a => a.classList.remove('is-current'));
        syncCategoryChecks();
        renderGrid();
        productGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
  }

  // ==========================================
  // CHECKOUT.PHP - Render Cart & Form Logic
  // ==========================================

  const cartContainer = document.getElementById('cartItemsContainer');
  if (cartContainer) {
    function renderCart() {
      cartContainer.innerHTML = '';
      if (cart.length === 0) {
        cartContainer.innerHTML = '<p style="color: var(--ink-soft); font-size: 0.9rem;">Your bag is empty.</p>';
        document.getElementById('cartSubtotal').textContent = 'US$0.00';
        document.getElementById('cartTotal').textContent = 'US$0.00';
        return;
      }
      
      let subtotal = 0;
      cart.forEach((item, index) => {
        subtotal += (item.price * item.quantity);
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
          <img src="${item.img}" alt="${item.name}">
          <div class="cart-item-details">
            <h4>${item.name}</h4>
            ${item.color ? `<p class="cart-item-color">Color: ${item.color}</p>` : ''}
            <p>US$${item.price.toFixed(2)}</p>
            <div class="cart-item-actions">
              <label>Qty: 
                <select data-index="${index}" class="qty-select">
                  ${[1, 2, 3, 4, 5, 6, 7, 8].map(n => `<option value="${n}" ${item.quantity === n ? 'selected' : ''}>${n}</option>`).join('')}
                </select>
              </label>
              <button type="button" class="remove-btn" data-index="${index}">Remove</button>
            </div>
          </div>
        `;
        cartContainer.appendChild(div);
      });
      
      document.getElementById('cartSubtotal').textContent = `US$${subtotal.toFixed(2)}`;
      document.getElementById('cartTotal').textContent = `US$${subtotal.toFixed(2)}`;
      
      // Add event listeners to new quantity dropdowns and remove buttons
      document.querySelectorAll('.qty-select').forEach(sel => {
        sel.addEventListener('change', (e) => {
          const idx = e.target.dataset.index;
          cart[idx].quantity = parseInt(e.target.value);
          localStorage.setItem('lunora_cart', JSON.stringify(cart));
          updateBagCounter();
          renderCart(); // Re-render
        });
      });

      document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const idx = e.target.dataset.index;
          cart.splice(idx, 1); // Remove from array
          localStorage.setItem('lunora_cart', JSON.stringify(cart));
          updateBagCounter();
          renderCart(); // Re-render
        });
      });
    }
    
    renderCart(); // Initial render

    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
      checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (cart.length === 0) {
          showToast('Your bag is empty. Please add items before checking out.');
          return;
        }

        const submitBtn = checkoutForm.querySelector('.checkout-submit');
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Placing order…'; }

        const formData = new FormData(checkoutForm);
        const payload = {
          fullname: formData.get('fullname'),
          email: formData.get('email'),
          phone: formData.get('phone'),
          address: formData.get('address'),
          payment: formData.get('payment'),
          items: cart,
        };

        try {
          const res = await fetch('place_order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-Token': checkoutForm.dataset.csrf || '',
            },
            body: JSON.stringify(payload),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            showToast(data.error || 'Something went wrong placing your order.');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Place Order'; }
            return;
          }

          // Clear cart and go to the confirmation page
          cart = [];
          localStorage.removeItem('lunora_cart');
          window.location.href = 'order_success.php?id=' + encodeURIComponent(data.order_id);
        } catch (err) {
          showToast('Network error — please try again.');
          if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Place Order'; }
        }
      });
    }
  }

  // ==========================================
  // GENERAL - UI Helpers
  // ==========================================

  const menuToggle = document.getElementById('menuToggle');
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', String(!expanded));
      document.body.classList.toggle('nav-open', !expanded);
    });
  }

  // Reveal product cards on scroll
  const revealTargets = document.querySelectorAll('.product-card, .editorial-tile');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.transition = 'opacity .5s ease, transform .5s ease';
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    revealTargets.forEach((el) => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(14px)';
      io.observe(el);
    });
  }
});