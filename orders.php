<?php
require 'config.php';
if (!isLoggedIn()) redirect('auth.php');
$user_id = $_SESSION['user_id'];
$success_order = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$orders = mysqli_query($conn,"SELECT * FROM orders WHERE user_id=$user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — BookStore</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f0f1a;color:#f1f5f9;animation:pageFade .4s ease}
@keyframes pageFade{from{opacity:0}to{opacity:1}}
body::before{content:'';position:fixed;inset:0;z-index:-1;background:
  radial-gradient(ellipse at 10% 20%,rgba(37,99,235,.15) 0%,transparent 50%),
  radial-gradient(ellipse at 90% 10%,rgba(124,58,237,.12) 0%,transparent 50%),
  radial-gradient(ellipse at 50% 80%,rgba(236,72,153,.08) 0%,transparent 50%),
  linear-gradient(135deg,#0f0f1a 0%,#0d1117 100%)}
#scroll-progress{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#2563eb,#7c3aed,#ec4899);z-index:9999;transition:width .1s}
.main-wrap{max-width:860px;margin:0 auto;padding:32px 20px}
.navbar{background:rgba(15,15,26,.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.08);position:sticky;top:0;z-index:100;padding:0 28px;height:64px;display:flex;align-items:center;gap:16px;box-shadow:0 4px 24px rgba(0,0,0,.3)}
.nav-logo{font-size:20px;font-weight:900;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.nav-links{display:flex;gap:6px;margin-left:auto;align-items:center}
.nav-links a{color:rgba(255,255,255,.65);font-size:13px;font-weight:500;padding:7px 14px;border-radius:22px;transition:all .2s;text-decoration:none}
.nav-links a:hover{background:rgba(255,255,255,.1);color:#fff}
.nav-links .active-link{background:rgba(37,99,235,.2);color:#60a5fa}
.page-heading{font-size:24px;font-weight:900;margin-bottom:24px;display:flex;align-items:center;gap:10px;animation:slideDown .4s ease}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.page-heading span{background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.success-banner{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:16px;padding:18px 22px;color:#4ade80;font-size:15px;font-weight:500;margin-bottom:24px;display:flex;align-items:center;gap:14px;animation:slideDown .4s ease;box-shadow:0 4px 20px rgba(34,197,94,.1)}
.order-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:18px;margin-bottom:16px;overflow:hidden;backdrop-filter:blur(10px);animation:cardIn .5s ease both;transition:all .3s}
.order-card:hover{border-color:rgba(37,99,235,.3);box-shadow:0 12px 40px rgba(37,99,235,.15);transform:translateY(-3px)}
.order-card:nth-child(1){animation-delay:.05s}.order-card:nth-child(2){animation-delay:.1s}.order-card:nth-child(3){animation-delay:.15s}.order-card:nth-child(4){animation-delay:.2s}
@keyframes cardIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.order-header{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.06)}
.order-id{font-weight:800;color:#f1f5f9;font-size:16px}
.order-date{font-size:12px;color:rgba(255,255,255,.35);margin-top:2px}
.status-badge{padding:5px 16px;border-radius:20px;font-size:12px;font-weight:700;animation:popIn .3s ease}
@keyframes popIn{from{transform:scale(0)}70%{transform:scale(1.1)}to{transform:scale(1)}}
.sb-pending{background:#fef3c7;color:#92400e}.sb-confirmed{background:#dbeafe;color:#1e40af}.sb-shipped{background:#ede9fe;color:#5b21b6}.sb-delivered{background:#dcfce7;color:#166534}.sb-cancelled{background:#fee2e2;color:#991b1b}
.order-items-list{padding:14px 20px}
.order-item-row{display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid #f8fafc}
.order-item-row:last-child{border-bottom:none}
.order-item-row img{border-radius:8px;object-fit:cover;height:52px;width:38px;border:1px solid #e2e8f0}
.item-title{font-size:14px;font-weight:600;color:#1e293b}
.item-author{font-size:12px;color:#94a3b8}
.item-qty{font-size:12px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:20px}
.item-price{font-size:14px;font-weight:700;color:#2563eb;margin-left:auto}
.order-footer{display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:#f8fafc;border-top:1px solid #f1f5f9}
.payment-tag{font-size:11px;padding:3px 10px;border-radius:20px;background:#e2e8f0;color:#475569;font-weight:600}
.order-total{font-size:16px;font-weight:800;color:#1e293b}
.empty-orders{text-align:center;padding:60px 20px;color:#94a3b8}
.empty-icon{font-size:56px;margin-bottom:16px;animation:float 3s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
#scroll-progress{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#2563eb,#7c3aed,#ec4899);z-index:9999}
.fab{position:fixed;bottom:24px;right:24px;width:46px;height:46px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:18px;box-shadow:0 4px 16px rgba(37,99,235,.4);opacity:0;transform:translateY(10px);transition:all .3s;z-index:999;display:flex;align-items:center;justify-content:center}
.fab.show{opacity:1;transform:translateY(0)}

/* ── BIG NAVBAR UPGRADE ── */
.navbar{height:80px !important;padding:0 40px !important}
.nav-logo{display:flex !important;align-items:center !important;gap:10px !important;text-decoration:none !important}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 14px rgba(37,99,235,.4);flex-shrink:0}
.logo-text{font-size:22px;font-weight:900;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo-sub{font-size:10px;color:rgba(255,255,255,.3);margin-top:-2px;letter-spacing:.05em}
.nav-links a{padding:9px 18px !important;font-size:13px !important;font-weight:600 !important;border-radius:24px !important}
</style>
</head>
<body>
<div id="scroll-progress"></div>
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="logo-icon">📚</div>
    <div>
      <div class="logo-text">BookStore</div>
      <div class="logo-sub">YOUR READING PARADISE</div>
    </div>
  </a>
  <div class="nav-links">
    <a href="index.php">🏠 Home</a>
    <a href="cart.php">🛒 Cart</a>
    <a href="orders.php" class="active-link">📦 My Orders</a>
    <a href="logout.php">👋 Logout</a>
  </div>
</nav>
<div class="main-wrap">
  <div class="page-heading">📦 <span>My Orders</span></div>

  <?php if ($success_order): ?>
  <div class="success-banner">
    🎉 <div><strong>Order #<?= $success_order ?> placed successfully!</strong><br><span style="font-size:13px;opacity:.8">Thank you for shopping with BookStore. Your order is being processed.</span></div>
  </div>
  <?php endif; ?>

  <?php if (mysqli_num_rows($orders) === 0): ?>
  <div class="empty-orders">
    <div class="empty-icon">📭</div>
    <h3 style="color:#1e293b;margin-bottom:8px">No orders yet</h3>
    <p style="margin-bottom:20px">You haven't placed any orders yet.</p>
    <a href="index.php" style="display:inline-block;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;padding:13px 32px;border-radius:14px;text-decoration:none;font-weight:700;font-size:15px;box-shadow:0 6px 20px rgba(37,99,235,.35)">Browse Books →</a>
  </div>
  <?php else: ?>
  <?php while($order=mysqli_fetch_assoc($orders)): ?>
  <div class="order-card">
    <div class="order-header">
      <div>
        <div class="order-id">Order #<?= $order['order_id'] ?></div>
        <div class="order-date"><?= date('d M Y, h:i A',strtotime($order['created_at'])) ?></div>
      </div>
      <span class="status-badge sb-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
    </div>
    <div class="order-items-list">
      <?php
      $items=mysqli_query($conn,"SELECT oi.*,b.title,b.author,b.image FROM order_items oi JOIN books b ON oi.book_id=b.book_id WHERE oi.order_id={$order['order_id']}");
      while($item=mysqli_fetch_assoc($items)):
      ?>
      <div class="order-item-row">
        <img src="images/<?= $item['image'] ?>" onerror="this.src='images/default.jpg'">
        <div style="flex:1">
          <div class="item-title"><?= htmlspecialchars($item['title']) ?></div>
          <div class="item-author"><?= htmlspecialchars($item['author']) ?></div>
        </div>
        <span class="item-qty">x<?= $item['quantity'] ?></span>
        <div class="item-price">Rs. <?= number_format($item['unit_price']*$item['quantity'],2) ?></div>
      </div>
      <?php endwhile; ?>
    </div>
    <div class="order-footer">
      <span class="payment-tag"><?= strtoupper($order['payment_mode']) ?></span>
      <div class="order-total">Total: Rs. <?= number_format($order['total_amount'],2) ?></div>
    </div>
  </div>
  <?php endwhile; ?>
  <?php endif; ?>
</div>
<button class="fab" id="fab" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>
<script>
window.addEventListener('scroll',()=>{
  const el=document.documentElement;
  document.getElementById('scroll-progress').style.width=(el.scrollTop/(el.scrollHeight-el.clientHeight)*100)+'%';
  document.getElementById('fab').classList.toggle('show',window.scrollY>200);
});
</script>
</body>
</html>
