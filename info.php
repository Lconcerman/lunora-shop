<?php
require_once __DIR__ . '/auth.php';
$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();

/**
 * LUNORA — generic content page for the footer's informational links.
 * Each footer <a> points here as info.php?slug=xxx; $pages below holds
 * the title + body markup for every slug. Unknown/missing slugs fall
 * back to a friendly "page not found" notice instead of a hard error.
 */
$pages = [
    'faq' => [
        'title' => 'Frequently Asked Questions',
        'body' => '
            <h3>Orders</h3>
            <p>Once your order ships you will receive a tracking link by email. You can also check the status any time from <a href="my-orders.php">Check Order Status</a> if you are signed in.</p>
            <h3>Payments</h3>
            <p>We accept major credit and debit cards as well as cash on delivery in select regions. All card payments are processed securely at checkout.</p>
            <h3>Sizing &amp; fit</h3>
            <p>Every product page lists exact dimensions. See our <a href="info.php?slug=size-guide">Size Guide</a> for help choosing between our bag silhouettes.</p>
            <h3>Returns</h3>
            <p>Unused items in original packaging can be returned within 30 days — details are on our <a href="info.php?slug=returns-exchanges">Returns &amp; Exchanges</a> page.</p>
            <h3>Still need help?</h3>
            <p>Reach our team any time through <a href="contact.php">Contact Us</a>.</p>
        ',
    ],
    'scam-awareness' => [
        'title' => 'Scam Awareness',
        'body' => '
            <p>LUNORA will never ask you for your password, one-time passcode, or full card number over email, text, or phone.</p>
            <p>Only trust order or payment links that point to this domain. If you receive a suspicious message claiming to be from LUNORA, do not click any links — forward it to us via <a href="contact.php">Contact Us</a> so we can investigate.</p>
            <ul>
                <li>We never request gift-card payments for orders.</li>
                <li>Official emails only come from confirmed LUNORA order or account notifications.</li>
                <li>Always check the URL matches this site before entering payment details.</li>
            </ul>
        ',
    ],
    'privilege-membership' => [
        'title' => 'Privilege Membership',
        'body' => '
            <p>Privilege is our free loyalty programme. Members earn points on every purchase, redeemable toward future orders, plus early access to new arrivals and members-only promotions.</p>
            <p><a href="' . ($lunora_user ? 'my-orders.php' : 'register.php') . '">' . ($lunora_user ? 'View your account' : 'Create a free account') . '</a> to start earning points on your next order.</p>
        ',
    ],
    'shipping-tracking' => [
        'title' => 'Shipping & Tracking',
        'body' => '
            <p>Standard delivery is free on every order with no minimum spend, and typically arrives within 3–7 business days.</p>
            <p>As soon as your order ships, a carrier and tracking number are attached to it — sign in and visit <a href="my-orders.php">Check Order Status</a> to follow its progress.</p>
        ',
    ],
    'returns-exchanges' => [
        'title' => 'Returns & Exchanges',
        'body' => '
            <p>Not the right fit? Unused items in their original packaging can be returned within 30 days of delivery for a full refund to your original payment method.</p>
            <p>To start a return, go to <a href="my-orders.php">Check Order Status</a>, select the order, and follow the return prompts, or reach out through <a href="contact.php">Contact Us</a> and our team will help directly.</p>
        ',
    ],
    'size-guide' => [
        'title' => 'Size Guide',
        'body' => '
            <p>Our bags are grouped into four general sizes — use this as a rough guide, and check each product page for exact measurements.</p>
            <ul>
                <li><strong>Mini</strong> — phone, cards, keys. Best worn crossbody or as an evening bag.</li>
                <li><strong>Shoulder / Top Handle</strong> — everyday essentials, small pouch, sunglasses.</li>
                <li><strong>Tote / Hobo</strong> — laptop or A4 documents fit comfortably.</li>
                <li><strong>XL / Weekender</strong> — overnight essentials or a change of clothes.</li>
            </ul>
        ',
    ],
    'product-care' => [
        'title' => 'Product Care',
        'body' => '
            <p>Keep your LUNORA bag looking its best with a little routine care.</p>
            <ul>
                <li>Store bags upright in their dust bag, away from direct sunlight.</li>
                <li>Wipe leather with a soft, dry cloth — avoid soaking or harsh cleaners.</li>
                <li>Use a suede protector spray on suede styles before first wear.</li>
                <li>Avoid overloading bags beyond their intended capacity to protect handles and stitching.</li>
            </ul>
        ',
    ],
    'brand-profile' => [
        'title' => 'Brand Profile',
        'body' => '
            <p>LUNORA designs considered, everyday bags for women who move between campus, work, and everything after. Every silhouette is developed with an emphasis on real-life proportions, durable materials, and quiet, lasting design over passing trends.</p>
        ',
    ],
    'sustainability' => [
        'title' => 'Sustainability',
        'body' => '
            <p>We are working toward a smaller footprint across our materials and packaging — including recycled shipping mailers, responsibly sourced leathers, and longer-lasting hardware designed to be repaired rather than replaced.</p>
        ',
    ],
    'franchising' => [
        'title' => 'Franchising Opportunities',
        'body' => '
            <p>Interested in bringing LUNORA to your city? We are open to conversations with partners who share our standards for space, service, and brand presentation.</p>
            <p>Tell us about your market and experience via <a href="contact.php">Contact Us</a> and our partnerships team will follow up.</p>
        ',
    ],
    'affiliates' => [
        'title' => 'Affiliates',
        'body' => '
            <p>Creators and publishers can apply to join the LUNORA affiliate programme to earn commission on referred sales.</p>
            <p>Send your platform details and audience size through <a href="contact.php">Contact Us</a> and our team will get back to you about next steps.</p>
        ',
    ],
    'store-locator' => [
        'title' => 'Store Locator',
        'body' => '
            <p>LUNORA is currently available online, with select shop-in-shop counters at partner department stores. Enter your city through <a href="contact.php">Contact Us</a> and we will let you know about the nearest location as we expand.</p>
        ',
    ],
    'virtual-store' => [
        'title' => 'Virtual Store Experience',
        'body' => '
            <p>Browse our full catalogue, filter by category, price, and colour, and use Quick Add on any product card to preview a bag before you buy — no appointment or app download required.</p>
            <p><a href="index.php">Start browsing the shop →</a></p>
        ',
    ],
    'fashion-guides' => [
        'title' => 'Fashion Guides',
        'body' => '
            <p>Not sure which silhouette suits your routine? A structured top-handle works well for the office, a slouchy hobo suits everyday errands, and a crossbody keeps hands free on campus.</p>
            <p>Browse by category on the <a href="index.php">shop page</a> to compare styles side by side.</p>
        ',
    ],
    'promotions' => [
        'title' => 'Promotions',
        'body' => '
            <p>Current offer: free standard delivery on every order, no minimum spend. Subscribe to our newsletter in the footer for first access to seasonal sales and a 10% welcome discount.</p>
        ',
    ],
    'unidays' => [
        'title' => 'UNiDAYS',
        'body' => '
            <p>Verified students can save on LUNORA through UNiDAYS. Verify your student status with UNiDAYS, then apply the discount code you receive at checkout.</p>
        ',
    ],
    'student-beans' => [
        'title' => 'Student Beans',
        'body' => '
            <p>Students verified through Student Beans receive an exclusive discount code to use at LUNORA checkout. Verify your status with Student Beans to get your code.</p>
        ',
    ],
    'youth-worker-discount' => [
        'title' => 'Youth & Essential Worker Discount',
        'body' => '
            <p>We offer a discount for young shoppers and essential workers (healthcare, education, and emergency services) as a thank-you for the work you do. Contact us with proof of eligibility via <a href="contact.php">Contact Us</a> to receive your code.</p>
        ',
    ],
    'social-follower-discount' => [
        'title' => 'Social Follower Discount',
        'body' => '
            <p>Follow LUNORA on any of our social channels in the footer, then message us your handle through <a href="contact.php">Contact Us</a> to receive a follower-exclusive discount code.</p>
        ',
    ],
    'terms-of-use' => [
        'title' => 'Terms of Use',
        'body' => '
            <p>By using this site and placing orders with LUNORA, you agree to shop in good faith, provide accurate order and payment information, and use the site only for its intended purpose of browsing and purchasing products.</p>
            <p>Product availability, pricing, and promotions may change without notice. All content on this site — including photography, text, and branding — belongs to LUNORA and may not be reused without permission.</p>
        ',
    ],
    'privacy-policy' => [
        'title' => 'Privacy Policy',
        'body' => '
            <p>We collect the information needed to process your orders — name, email, shipping address, and payment confirmation — and use it only for that purpose, order support, and (if you opt in) newsletter emails.</p>
            <p>We do not sell your personal information. You can ask us to update or delete your data at any time via <a href="contact.php">Contact Us</a>.</p>
        ',
    ],
    'cookies-policy' => [
        'title' => 'Cookies Policy',
        'body' => '
            <p>This site uses your browser\'s local storage to remember items in your bag and wishlist between visits, and a session cookie to keep you signed in. These are essential to core shopping features and are not used for third-party advertising.</p>
        ',
    ],
];

$slug = $_GET['slug'] ?? '';
$page = $pages[$slug] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page['title'] ?? 'Page Not Found') ?> — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/site_header.php'; ?>

<div class="breadcrumb">
  <div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span><?= htmlspecialchars($page['title'] ?? 'Not Found') ?></span></div>
</div>

<main class="info-page">
  <div class="info-page__inner">
    <?php if ($page): ?>
      <h1><?= htmlspecialchars($page['title']) ?></h1>
      <div class="info-page__body"><?= $page['body'] ?></div>
    <?php else: ?>
      <h1>Page Not Found</h1>
      <div class="info-page__body">
        <p>Sorry, we couldn't find that page. <a href="index.php">Return to the shop →</a></p>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>

<script src="script.js"></script>
</body>
</html>
