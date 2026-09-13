<footer class="site-footer">
  <div class="footer-columns">
    <div class="footer-col">
      <h4>Need Help?</h4>
      <a href="my-orders.php">Check Order Status</a>
      <a href="info.php?slug=faq">FAQ</a>
      <a href="contact.php">Contact Us</a>
      <a href="info.php?slug=scam-awareness">Scam Awareness</a>
      <a href="info.php?slug=privilege-membership">Privilege Membership</a>
      <a href="info.php?slug=shipping-tracking">Shipping &amp; Tracking</a>
      <a href="info.php?slug=returns-exchanges">Returns &amp; Exchanges</a>
      <a href="info.php?slug=size-guide">Size Guide</a>
      <a href="info.php?slug=product-care">Product Care</a>
    </div>
    <div class="footer-col">
      <h4>About Us</h4>
      <a href="info.php?slug=brand-profile">Brand Profile</a>
      <a href="info.php?slug=sustainability">Sustainability</a>
      <a href="info.php?slug=franchising">Franchising Opportunities</a>
      <a href="info.php?slug=affiliates">Affiliates</a>
    </div>
    <div class="footer-col">
      <h4>Shopping With Us</h4>
      <a href="info.php?slug=store-locator">Store Locator</a>
      <a href="info.php?slug=virtual-store">Virtual Store Experience</a>
      <a href="info.php?slug=fashion-guides">Fashion Guides</a>
      <a href="info.php?slug=promotions">Promotions</a>
      <a href="info.php?slug=unidays">UNiDAYS</a>
      <a href="info.php?slug=student-beans">Student Beans</a>
      <a href="info.php?slug=youth-worker-discount">Youth &amp; Essential Worker Discount</a>
      <a href="info.php?slug=social-follower-discount">Social Follower Discount</a>
    </div>
    <div class="footer-col">
      <h4>Legal</h4>
      <a href="info.php?slug=terms-of-use">Terms of Use</a>
      <a href="info.php?slug=privacy-policy">Privacy Policy</a>
      <a href="info.php?slug=cookies-policy">Cookies Policy</a>
    </div>
    <div class="footer-col">
      <h4>Be the First to Know</h4>
      <p style="margin:0; color:rgba(250,247,242,.8);">Enjoy 10% off your first purchase when you subscribe to our newsletter.</p>
      <form class="subscribe-form" id="subscribeForm" novalidate>
        <input type="email" name="email" id="subscribeEmail" placeholder="Enter Email" aria-label="Email address" required>
        <button type="submit" id="subscribeBtn">SUBSCRIBE</button>
      </form>
      <p class="subscribe-note" id="subscribeNote">By subscribing, you agree to LUNORA <a href="info.php?slug=terms-of-use" style="text-decoration:underline;">Terms &amp; Conditions</a> and <a href="info.php?slug=privacy-policy" style="text-decoration:underline;">Privacy Policy</a>.</p>
      <h4 style="margin-top:14px;">Follow Us</h4>
      <div class="socials">
        <a href="https://www.facebook.com/" target="_blank" rel="noopener" title="Facebook">FB</a>
        <a href="https://www.instagram.com/" target="_blank" rel="noopener" title="Instagram">IG</a>
        <a href="https://www.x.com/" target="_blank" rel="noopener" title="X">X</a>
        <a href="https://www.pinterest.com/" target="_blank" rel="noopener" title="Pinterest">PIN</a>
        <a href="https://www.tiktok.com/" target="_blank" rel="noopener" title="TikTok">TT</a>
        <a href="https://www.youtube.com/" target="_blank" rel="noopener" title="YouTube">YT</a>
        <a href="https://telegram.org/" target="_blank" rel="noopener" title="Telegram">TG</a>
        <a href="https://web.whatsapp.com/" target="_blank" rel="noopener" title="WhatsApp">WA</a>
      </div>
    </div>
  </div>
  <div class="footer-base">
    <span class="wordmark--footer">LUNORA</span>
    <span>&copy; <?= date('Y') ?> LUNORA. All rights reserved.</span>
  </div>
</footer>

<script>
(function(){
  var form = document.getElementById('subscribeForm');
  if (!form) return;
  var emailInput = document.getElementById('subscribeEmail');
  var note = document.getElementById('subscribeNote');
  var btn = document.getElementById('subscribeBtn');
  var defaultNote = note.innerHTML;
  form.addEventListener('submit', function(e){
    e.preventDefault();
    var email = emailInput.value.trim();
    if (!email) return;
    btn.disabled = true;
    var originalLabel = btn.textContent;
    btn.textContent = '...';
    fetch('subscribe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'email=' + encodeURIComponent(email)
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
      btn.disabled = false;
      btn.textContent = originalLabel;
      if (data && data.ok) {
        emailInput.value = '';
        note.innerHTML = data.message || 'Thanks — you\'re on the list!';
        note.style.color = '#C9A277';
      } else {
        note.innerHTML = (data && data.message) ? data.message : 'Something went wrong. Please try again.';
        note.style.color = '#E0A79B';
      }
      setTimeout(function(){ note.innerHTML = defaultNote; note.style.color = ''; }, 5000);
    })
    .catch(function(){
      btn.disabled = false;
      btn.textContent = originalLabel;
      note.textContent = 'Something went wrong. Please try again.';
      note.style.color = '#E0A79B';
    });
  });
})();
</script>
