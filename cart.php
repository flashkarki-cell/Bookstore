<?php
require 'config.php';
if (!isLoggedIn()) redirect('auth.php');
$user_id = $_SESSION['user_id'];
if (isset($_GET['add'])) {
    $book_id=(int)$_GET['add'];
    $check=mysqli_query($conn,"SELECT cart_id,quantity FROM cart WHERE user_id=$user_id AND book_id=$book_id");
    if(mysqli_num_rows($check)>0){$row=mysqli_fetch_assoc($check);mysqli_query($conn,"UPDATE cart SET quantity=".($row['quantity']+1)." WHERE cart_id={$row['cart_id']}");}
    else{mysqli_query($conn,"INSERT INTO cart (user_id,book_id,quantity) VALUES ($user_id,$book_id,1)");}
    redirect('cart.php');
}
if (isset($_GET['remove'])){$cid=(int)$_GET['remove'];mysqli_query($conn,"DELETE FROM cart WHERE cart_id=$cid AND user_id=$user_id");redirect('cart.php');}
if (isset($_POST['update'])){foreach($_POST['qty'] as $cid=>$qty){$cid=(int)$cid;$qty=max(1,(int)$qty);mysqli_query($conn,"UPDATE cart SET quantity=$qty WHERE cart_id=$cid AND user_id=$user_id");}redirect('cart.php');}
if (isset($_POST['place_order'])) {
    $address=clean($conn,$_POST['address']);
    $payment=clean($conn,$_POST['payment_mode']);
    $cart_items=mysqli_query($conn,"SELECT c.*,b.price,b.stock FROM cart c JOIN books b ON c.book_id=b.book_id WHERE c.user_id=$user_id");
    if(mysqli_num_rows($cart_items)===0){$error="Your cart is empty!";}
    else {
        $total=0;$items=[];
        while($item=mysqli_fetch_assoc($cart_items)){$total+=$item['price']*$item['quantity'];$items[]=$item;}
        mysqli_query($conn,"INSERT INTO orders (user_id,total_amount,status,payment_mode,address) VALUES ($user_id,$total,'pending','$payment','$address')");
        $oid=mysqli_insert_id($conn);
        foreach($items as $item){$bid=$item['book_id'];$qty=$item['quantity'];$price=$item['price'];mysqli_query($conn,"INSERT INTO order_items (order_id,book_id,quantity,unit_price) VALUES ($oid,$bid,$qty,$price)");mysqli_query($conn,"UPDATE books SET stock=stock-$qty WHERE book_id=$bid");}
        mysqli_query($conn,"DELETE FROM cart WHERE user_id=$user_id");
        redirect("orders.php?success=$oid");
    }
}
$cart=mysqli_query($conn,"SELECT c.cart_id,c.quantity,b.book_id,b.title,b.author,b.price,b.image,b.stock FROM cart c JOIN books b ON c.book_id=b.book_id WHERE c.user_id=$user_id");
$total=0;$rows=[];
while($row=mysqli_fetch_assoc($cart)){$row['subtotal']=$row['price']*$row['quantity'];$total+=$row['subtotal'];$rows[]=$row;}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart — BookStore</title>
<link rel="stylesheet" href="css/style.css">
<style>
*{box-sizing:border-box}
body{background:#0f0f1a;font-family:'Segoe UI',Arial,sans-serif;color:#f1f5f9}
body::before{content:'';position:fixed;inset:0;z-index:-1;background:
  radial-gradient(ellipse at 15% 25%,rgba(37,99,235,.15) 0%,transparent 50%),
  radial-gradient(ellipse at 85% 10%,rgba(124,58,237,.12) 0%,transparent 50%),
  linear-gradient(135deg,#0f0f1a 0%,#0d1117 100%)}
#scroll-progress{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#2563eb,#7c3aed,#ec4899);z-index:9999;transition:width .1s}
.navbar{background:rgba(15,15,26,.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.08);position:sticky;top:0;z-index:100;padding:0 24px;height:62px;display:flex;align-items:center;gap:16px;box-shadow:0 4px 24px rgba(0,0,0,.3)}
.nav-logo{font-size:20px;font-weight:800;background:linear-gradient(135deg,#2563eb,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.nav-links{display:flex;gap:8px;margin-left:auto;align-items:center}
.nav-links a{color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;font-weight:500;padding:6px 14px;border-radius:20px;transition:all .2s}
.nav-links a:hover{background:rgba(255,255,255,.1);color:#fff}
.nav-links .active-link{background:linear-gradient(135deg,#eff6ff,#ede9fe);color:#2563eb;font-weight:600}

.main-wrap{max-width:1100px;margin:0 auto;padding:32px 20px}
.page-heading{font-size:26px;font-weight:800;margin-bottom:28px;display:flex;align-items:center;gap:10px;color:#f1f5f9}
.page-heading .grad{background:linear-gradient(135deg,#2563eb,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent}

/* Empty cart */
.empty-wrap{text-align:center;padding:80px 20px;animation:fadeIn .5s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.empty-icon{font-size:72px;margin-bottom:20px;animation:float 3s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.empty-title{font-size:22px;font-weight:700;color:#f1f5f9;margin-bottom:8px}
.empty-sub{font-size:15px;color:rgba(255,255,255,.4);margin-bottom:28px}
.btn-browse{display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:14px;text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 6px 20px rgba(37,99,235,.35);transition:all .2s}
.btn-browse:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(37,99,235,.4);text-decoration:none;color:#fff}

/* Cart layout */
.cart-layout{display:grid;grid-template-columns:1fr 360px;gap:28px}
@media(max-width:900px){.cart-layout{grid-template-columns:1fr}}

/* Cart items card */
.cart-items-card{background:rgba(255,255,255,.05);border-radius:20px;box-shadow:0 4px 30px rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.08);overflow:hidden;animation:slideUp .5s ease;backdrop-filter:blur(12px)}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.cart-items-header{padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.04)}
.cart-items-title{font-size:16px;font-weight:700;color:#f1f5f9;display:flex;align-items:center;gap:8px}
.item-count-badge{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px}

/* Cart item rows */
.cart-item{display:flex;align-items:center;gap:16px;padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.05);transition:background .2s;animation:rowIn .4s ease both}
@keyframes rowIn{from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)}}
.cart-item:nth-child(1){animation-delay:.05s}.cart-item:nth-child(2){animation-delay:.1s}.cart-item:nth-child(3){animation-delay:.15s}
.cart-item:hover{background:rgba(37,99,235,.07)}
.cart-item:last-child{border-bottom:none}
.item-img{width:56px;height:72px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.item-details{flex:1}
.item-title{font-size:15px;font-weight:700;color:#f1f5f9;margin-bottom:3px}
.item-author{font-size:13px;color:rgba(255,255,255,.4)}
.item-unit-price{font-size:12px;color:rgba(255,255,255,.3);margin-top:3px}
.qty-wrap{display:flex;align-items:center;gap:0;background:rgba(255,255,255,.07);border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.12)}
.qty-btn{width:32px;height:32px;border:none;background:transparent;font-size:16px;cursor:pointer;color:rgba(255,255,255,.6);transition:all .15s;display:flex;align-items:center;justify-content:center}
.qty-btn:hover{background:rgba(37,99,235,.2);color:#60a5fa}
.qty-display{width:36px;text-align:center;font-size:14px;font-weight:600;color:#f1f5f9;border:none;background:transparent;border-left:1px solid rgba(255,255,255,.1);border-right:1px solid rgba(255,255,255,.1);height:32px;padding:0}
.item-subtotal{font-size:16px;font-weight:800;background:linear-gradient(135deg,#2563eb,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;min-width:80px;text-align:right}
.remove-btn{width:30px;height:30px;border:none;background:#fef2f2;color:#ef4444;border-radius:8px;cursor:pointer;font-size:14px;transition:all .2s;display:flex;align-items:center;justify-content:center}
.remove-btn:hover{background:#fee2e2;transform:scale(1.1)}
.cart-actions{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.03);display:flex;justify-content:flex-end}
.btn-update{padding:9px 22px;background:rgba(37,99,235,.1);color:#60a5fa;border:1.5px solid rgba(37,99,235,.3);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s}
.btn-update:hover{background:rgba(37,99,235,.2);transform:translateY(-1px)}

/* Order summary card */
.summary-card{background:rgba(255,255,255,.05);border-radius:20px;box-shadow:0 8px 32px rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.08);overflow:hidden;height:fit-content;animation:slideUp .5s ease .1s both;position:sticky;top:80px;backdrop-filter:blur(12px)}
.summary-header{padding:18px 24px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff}
.summary-title{font-size:16px;font-weight:700}
.summary-body{padding:20px 24px}
.summary-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:14px;color:rgba(255,255,255,.5)}
.summary-row:last-of-type{border-bottom:none}
.summary-row strong{color:#f1f5f9}
.summary-total{display:flex;justify-content:space-between;align-items:center;padding:14px 0 0;margin-top:4px;border-top:1px solid rgba(255,255,255,.1)}
.total-label{font-size:16px;font-weight:700;color:#f1f5f9}
.total-amount{font-size:22px;font-weight:800;background:linear-gradient(135deg,#2563eb,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent}

/* Payment methods */
.payment-label{font-size:13px;font-weight:600;color:rgba(255,255,255,.4);margin:16px 0 8px;text-transform:uppercase;letter-spacing:.05em}
.payment-options{display:flex;flex-direction:column;gap:8px;margin-bottom:16px}
.payment-option{border:1.5px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:all .2s;background:rgba(255,255,255,.03)}
.payment-option:hover{border-color:rgba(37,99,235,.5)}
.payment-option.selected-cod{border-color:#22c55e;background:rgba(34,197,94,.1)}
.payment-option.selected-online{border-color:#2563eb;background:rgba(37,99,235,.12)}
.payment-option input{accent-color:#2563eb;width:15px;height:15px}
.pay-icon{font-size:20px}
.pay-title{font-size:13px;font-weight:600;color:#f1f5f9}
.pay-desc{font-size:11px;color:rgba(255,255,255,.35)}

/* Address */
.address-label{font-size:13px;font-weight:600;color:rgba(255,255,255,.4);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em}
.address-input{width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.1);border-radius:12px;font-size:14px;resize:none;min-height:72px;font-family:inherit;transition:all .2s;margin-bottom:16px;background:rgba(255,255,255,.05);color:#f1f5f9}
.address-input:focus{border-color:#2563eb;outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.15);background:rgba(37,99,235,.08)}

/* Place order button */
.place-order-btn{width:100%;padding:15px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:700;cursor:pointer;transition:all .25s;box-shadow:0 6px 20px rgba(37,99,235,.35);position:relative;overflow:hidden;letter-spacing:.3px}
.place-order-btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(37,99,235,.45)}
.place-order-btn:active{transform:translateY(0)}
.ripple-fx{position:absolute;border-radius:50%;background:rgba(255,255,255,.3);transform:scale(0);animation:ripAnim .6s linear;pointer-events:none}
@keyframes ripAnim{to{transform:scale(4);opacity:0}}

/* Error */
.err-banner{background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 18px;color:#991b1b;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px;animation:slideDown .3s ease}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}

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
    <a href="cart.php" class="active-link">🛒 Cart <?php if(count($rows)>0): ?>(<?= count($rows) ?>)<?php endif; ?></a>
    <a href="orders.php">📦 My Orders</a>
    <a href="logout.php">👋 Logout</a>
  </div>
</nav>

<div class="main-wrap">
  <div class="page-heading">🛒 <span class="grad">Shopping Cart</span></div>

  <?php if (isset($error)): ?>
  <div class="err-banner">⚠️ <?= $error ?></div>
  <?php endif; ?>

  <?php if (empty($rows)): ?>
  <div class="empty-wrap">
    <div class="empty-icon">🛒</div>
    <div class="empty-title">Your cart is empty!</div>
    <div class="empty-sub">Looks like you haven't added any books yet.</div>
    <a href="index.php" class="btn-browse">Browse Books →</a>
  </div>

  <?php else: ?>
  <div class="cart-layout">

    <!-- Cart Items -->
    <div class="cart-items-card">
      <div class="cart-items-header">
        <div class="cart-items-title">
          🛒 Your Items
          <span class="item-count-badge"><?= count($rows) ?> item<?= count($rows)>1?'s':'' ?></span>
        </div>
      </div>
      <form method="POST" id="cart-form">
      <?php foreach ($rows as $i => $item): ?>
      <div class="cart-item" id="item-<?= $item['cart_id'] ?>">
        <img src="images/<?= $item['image'] ?>" class="item-img"
             onerror="this.src='images/default.jpg'"
             alt="<?= htmlspecialchars($item['title']) ?>">
        <div class="item-details">
          <div class="item-title"><?= htmlspecialchars($item['title']) ?></div>
          <div class="item-author">by <?= htmlspecialchars($item['author']) ?></div>
          <div class="item-unit-price">Rs. <?= number_format($item['price'],2) ?> each</div>
        </div>
        <div class="qty-wrap">
          <button type="button" class="qty-btn" onclick="changeQty(<?= $item['cart_id'] ?>,-1)">−</button>
          <input type="number" class="qty-display" id="qty-<?= $item['cart_id'] ?>"
                 name="qty[<?= $item['cart_id'] ?>]"
                 value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                 onchange="updateSubtotal(<?= $item['cart_id'] ?>,<?= $item['price'] ?>)">
          <button type="button" class="qty-btn" onclick="changeQty(<?= $item['cart_id'] ?>,1,<?= $item['stock'] ?>)">+</button>
        </div>
        <div class="item-subtotal" id="sub-<?= $item['cart_id'] ?>">
          Rs. <?= number_format($item['subtotal'],2) ?>
        </div>
        <a href="?remove=<?= $item['cart_id'] ?>" class="remove-btn" title="Remove"
           onclick="return confirmRemove(this)">✕</a>
      </div>
      <?php endforeach; ?>
      <div class="cart-actions">
        <button type="submit" name="update" class="btn-update">🔄 Update Cart</button>
      </div>
      </form>
    </div>

    <!-- Order Summary -->
    <div class="summary-card">
      <div class="summary-header">
        <div class="summary-title">📋 Order Summary</div>
      </div>
      <div class="summary-body">
        <div class="summary-row"><span>Subtotal</span><strong id="cart-total">Rs. <?= number_format($total,2) ?></strong></div>
        <div class="summary-row"><span>Shipping</span><strong style="color:#22c55e">✓ Free</strong></div>
        <div class="summary-row"><span>Discount</span><strong>Rs. 0.00</strong></div>
        <div class="summary-total">
          <span class="total-label">Total</span>
          <span class="total-amount" id="grand-total">Rs. <?= number_format($total,2) ?></span>
        </div>

        <form method="POST">
          <!-- Payment -->
          <div class="payment-label">Payment Method</div>
          <div class="payment-options">
            <label class="payment-option selected-cod" id="lbl-cod">
              <input type="radio" name="payment_mode" value="cod" checked onchange="stylePayment()">
              <span class="pay-icon">💵</span>
              <div><div class="pay-title">Cash on Delivery</div><div class="pay-desc">Pay when order arrives</div></div>
            </label>
            <label class="payment-option" id="lbl-online">
              <input type="radio" name="payment_mode" value="online" onchange="stylePayment()">
              <span class="pay-icon">💳</span>
              <div><div class="pay-title">Online Payment</div><div class="pay-desc">Pay now securely</div></div>
            </label>
          </div>

          <!-- Online Payment Form (hidden by default) -->
          <div id="online-payment-form" style="display:none;margin-bottom:14px;animation:slideDown .3s ease">
            <div style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);border-radius:14px;padding:16px">
              <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">💳 Card Details</div>
              <div style="margin-bottom:10px">
                <label style="font-size:11px;font-weight:600;color:rgba(255,255,255,.4);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Card Number</label>
                <input type="text" id="card-num" maxlength="19" placeholder="1234 5678 9012 3456"
                  style="width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.1);border-radius:10px;font-size:14px;background:rgba(255,255,255,.06);color:#f1f5f9;outline:none;font-family:monospace;letter-spacing:2px"
                  oninput="formatCard(this)">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                  <label style="font-size:11px;font-weight:600;color:rgba(255,255,255,.4);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Expiry Date</label>
                  <input type="text" id="card-exp" maxlength="5" placeholder="MM/YY"
                    style="width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.1);border-radius:10px;font-size:14px;background:rgba(255,255,255,.06);color:#f1f5f9;outline:none;font-family:monospace"
                    oninput="formatExpiry(this)">
                </div>
                <div>
                  <label style="font-size:11px;font-weight:600;color:rgba(255,255,255,.4);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">CVV</label>
                  <input type="text" id="card-cvv" maxlength="3" placeholder="•••"
                    style="width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.1);border-radius:10px;font-size:14px;background:rgba(255,255,255,.06);color:#f1f5f9;outline:none;font-family:monospace;letter-spacing:4px">
                </div>
              </div>
              <div>
                <label style="font-size:11px;font-weight:600;color:rgba(255,255,255,.4);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Cardholder Name</label>
                <input type="text" id="card-name" placeholder="Name on card"
                  style="width:100%;padding:10px 12px;border:1.5px solid rgba(255,255,255,.1);border-radius:10px;font-size:14px;background:rgba(255,255,255,.06);color:#f1f5f9;outline:none">
              </div>
              <div style="margin-top:10px;display:flex;align-items:center;gap:6px;font-size:11px;color:rgba(255,255,255,.3)">
                🔒 Your card details are secure and encrypted
              </div>
            </div>
          </div>

          <!-- Hidden qty fields for order -->
          <?php foreach($rows as $item): ?>
          <input type="hidden" name="qty[<?= $item['cart_id'] ?>]" id="hqty-<?= $item['cart_id'] ?>" value="<?= $item['quantity'] ?>">
          <?php endforeach; ?>

          <!-- Address -->
          <div class="address-label">Delivery Address</div>
          <textarea name="address" class="address-input"
                    placeholder="Enter your full delivery address..." required></textarea>

          <button type="submit" name="place_order" class="place-order-btn" id="place-btn" onclick="ripple(event,this)">
            🚀 Place Order — Rs. <?= number_format($total,2) ?>
          </button>
        </form>
      </div>
    </div>

  </div>
  <?php endif; ?>
</div>

<button class="fab" id="fab" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script>
// Scroll progress + FAB
window.addEventListener('scroll',()=>{
  const el=document.documentElement;
  document.getElementById('scroll-progress').style.width=(el.scrollTop/(el.scrollHeight-el.clientHeight)*100)+'%';
  document.getElementById('fab').classList.toggle('show',window.scrollY>200);
});

// Change qty with +/- buttons
function changeQty(id, delta, max=999) {
  const inp = document.getElementById('qty-'+id);
  let val = parseInt(inp.value) + delta;
  if (val < 1) val = 1;
  if (val > max) val = max;
  inp.value = val;
  // sync hidden field
  const h = document.getElementById('hqty-'+id);
  if (h) h.value = val;
  updateSubtotalFromInput(id);
}

function updateSubtotalFromInput(id) {
  const inp = document.getElementById('qty-'+id);
  const price = parseFloat(inp.dataset.price || 0);
  // prices stored in data attr — we'll grab from display instead
}

// Update subtotals live
const prices = {<?php foreach($rows as $r): ?><?= $r['cart_id'] ?>:<?= $r['price'] ?>,<?php endforeach; ?>};
function updateSubtotal(id) {
  const qty = parseInt(document.getElementById('qty-'+id).value) || 1;
  const price = prices[id] || 0;
  const sub = qty * price;
  document.getElementById('sub-'+id).textContent = 'Rs. ' + sub.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
  updateTotal();
}
function updateTotal() {
  let t = 0;
  Object.keys(prices).forEach(id => {
    const qty = parseInt(document.getElementById('qty-'+id)?.value) || 0;
    t += qty * prices[id];
  });
  const fmt = 'Rs. ' + t.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');
  const ct = document.getElementById('cart-total');
  const gt = document.getElementById('grand-total');
  if (ct) ct.textContent = fmt;
  if (gt) gt.textContent = fmt;
}
// Attach to qty inputs
document.querySelectorAll('.qty-display').forEach(inp => {
  inp.addEventListener('input', () => updateSubtotal(inp.name.match(/\d+/)[0]));
});
// Attach to qty buttons
document.querySelectorAll('.qty-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.closest('.cart-item').id.replace('item-','');
    updateSubtotal(id);
  });
});

// Confirm remove with animation
function confirmRemove(el) {
  const item = el.closest('.cart-item');
  item.style.transition = 'all .3s ease';
  item.style.opacity = '0';
  item.style.transform = 'translateX(20px)';
  return true;
}

// Payment option styling
function stylePayment() {
  const cod = document.querySelector('input[value="cod"]').checked;
  document.getElementById('lbl-cod').className    = 'payment-option' + (cod ? ' selected-cod' : '');
  document.getElementById('lbl-online').className = 'payment-option' + (!cod ? ' selected-online' : '');
  // Show/hide online payment form
  const onlineForm = document.getElementById('online-payment-form');
  if (onlineForm) {
    onlineForm.style.display = cod ? 'none' : 'block';
  }
  // Update button text
  const btn = document.getElementById('place-btn');
  if (btn) {
    btn.innerHTML = cod
      ? '🚀 Place Order — Rs. ' + document.getElementById('grand-total').textContent.replace('Rs. ','')
      : '💳 Pay Now & Place Order';
  }
}

// Card number formatter
function formatCard(input) {
  let v = input.value.replace(/\D/g,'').substring(0,16);
  let parts = [];
  for(let i=0; i<v.length; i+=4) parts.push(v.substring(i,i+4));
  input.value = parts.join(' ');
  input.style.borderColor = v.length === 16 ? '#22c55e' : 'rgba(255,255,255,.1)';
}

// Expiry formatter
function formatExpiry(input) {
  let v = input.value.replace(/\D/g,'').substring(0,4);
  if(v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
  input.value = v;
}

// Ripple effect
function ripple(e, btn) {
  const r = document.createElement('span');
  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  r.className = 'ripple-fx';
  r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
  btn.appendChild(r);
  setTimeout(()=>r.remove(),600);
}
</script>
</body>
</html>
