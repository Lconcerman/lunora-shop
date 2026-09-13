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
  // INDEX.PHP - Wishlist & Quick Add Logic
  // ==========================================

  document.querySelectorAll('[data-wish]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      btn.classList.toggle('is-active');
      const card = btn.closest('.product-card');
      const name = card ? card.querySelector('.product-name')?.textContent.trim() : 'Item';
      showToast(btn.classList.contains('is-active') ? `Added ${name} to wishlist` : `Removed ${name} from wishlist`);
    });
  });

  document.querySelectorAll('[data-add]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const card = btn.closest('.product-card');
      const productId = btn.dataset.id || card.dataset.id || '';
      const name = btn.dataset.name;
      // Extract price number from string
      const price = parseFloat(card.dataset.price || card.querySelector('.product-price').textContent.replace(/[^0-9.]/g, ''));
      const img = card.querySelector('.product-photo__icon').src;

      // Check if item already exists in cart (match by product id when available)
      const existingItem = cart.find(i => (productId && i.productId === productId) || (!productId && i.name === name));
      if(existingItem) {
        existingItem.quantity += 1;
      } else {
        cart.push({ productId, name, price, img, quantity: 1 });
      }

      localStorage.setItem('lunora_cart', JSON.stringify(cart));
      updateBagCounter();
      showToast(`${name} added to your bag`);
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