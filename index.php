<?php
require 'config.php';
$search   = isset($_GET['search'])   ? clean($conn, $_GET['search'])   : '';
$category = isset($_GET['category']) ? (int)$_GET['category']          : 0;
$where = "WHERE 1=1";
if ($search)   $where .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
if ($category) $where .= " AND b.category_id = $category";
$books      = mysqli_query($conn,"SELECT b.*, c.category_name FROM books b LEFT JOIN categories c ON b.category_id=c.category_id $where ORDER BY b.created_at DESC");
$categories = mysqli_query($conn,"SELECT * FROM categories ORDER BY category_name");
$total_books = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM books"))['c'];
$total_cats  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM categories"))['c'];
$cart_count  = 0;
if (isLoggedIn()) {
  $cc = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(quantity) AS c FROM cart WHERE user_id={$_SESSION['user_id']}"));
  $cart_count = $cc['c'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BookStore — Discover Your Next Read</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#0f0f1a;color:#1e293b;animation:pageFade .4s ease}
/* Animated mesh background */
body::before{content:'';position:fixed;inset:0;z-index:-1;background:
  radial-gradient(ellipse at 10% 20%, rgba(37,99,235,.18) 0%, transparent 50%),
  radial-gradient(ellipse at 90% 10%, rgba(124,58,237,.15) 0%, transparent 50%),
  radial-gradient(ellipse at 50% 80%, rgba(236,72,153,.1) 0%, transparent 50%),
  radial-gradient(ellipse at 80% 60%, rgba(37,99,235,.12) 0%, transparent 40%),
  linear-gradient(135deg,#0f0f1a 0%,#0d1117 50%,#0f0f1a 100%);
  animation:meshMove 12s ease-in-out infinite alternate}
@keyframes meshMove{
  0%{background-position:0% 0%,100% 0%,50% 100%,80% 60%}
  100%{background-position:10% 10%,90% 5%,55% 95%,75% 65%}
}
@keyframes pageFade{from{opacity:0}to{opacity:1}}
a{text-decoration:none}

/* ── SCROLL PROGRESS ── */
#progress{position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#2563eb,#7c3aed,#ec4899);z-index:9999;transition:width .1s}

/* ── NAVBAR ── */
/* ── BIG IMPRESSIVE NAVBAR ── */
.navbar{background:rgba(10,10,20,.95);backdrop-filter:blur(24px);border-bottom:1px solid rgba(255,255,255,.07);position:sticky;top:0;z-index:200;padding:0 40px;height:80px;display:flex;align-items:center;gap:24px;box-shadow:0 4px 40px rgba(0,0,0,.5)}

/* Logo — bigger with icon */
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;white-space:nowrap}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 4px 14px rgba(37,99,235,.4);flex-shrink:0}
.logo-text{font-size:24px;font-weight:900;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-.5px}
.logo-sub{font-size:10px;color:rgba(255,255,255,.3);font-weight:500;margin-top:-2px;letter-spacing:.05em}

/* Search — taller and wider */
.nav-search{flex:1;max-width:560px;position:relative;display:flex;align-items:center}
.search-ico{position:absolute;left:16px;font-size:16px;pointer-events:none;z-index:1;color:rgba(255,255,255,.4)}
.nav-search input{width:100%;padding:13px 50px 13px 44px;border:1.5px solid rgba(255,255,255,.1);border-radius:32px;font-size:14px;background:rgba(255,255,255,.07);outline:none;transition:all .3s;color:#fff;height:48px}
.nav-search input::placeholder{color:rgba(255,255,255,.3)}
.nav-search input:focus{border-color:#2563eb;background:rgba(255,255,255,.1);box-shadow:0 0 0 4px rgba(37,99,235,.15)}
.search-btn{position:absolute;right:7px;width:36px;height:36px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;transition:all .2s;box-shadow:0 3px 10px rgba(37,99,235,.4)}
.search-btn:hover{transform:scale(1.1);box-shadow:0 5px 16px rgba(37,99,235,.5)}

/* Nav links — bigger pills */
.nav-links{display:flex;gap:8px;margin-left:auto;align-items:center;flex-wrap:nowrap}
.nav-links a{color:rgba(255,255,255,.7);font-size:13px;font-weight:600;padding:9px 18px;border-radius:24px;transition:all .2s;white-space:nowrap;display:flex;align-items:center;gap:6px}
.nav-links a:hover{background:rgba(255,255,255,.1);color:#fff;transform:translateY(-1px)}
.cart-wrap{position:relative;display:inline-block}
.cart-badge{position:absolute;top:-6px;right:-4px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:10px;font-weight:800;width:19px;height:19px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;animation:badgePop .4s cubic-bezier(.4,0,.2,1)}
@keyframes badgePop{0%{transform:scale(0)}70%{transform:scale(1.3)}100%{transform:scale(1)}}
.btn-login{background:linear-gradient(135deg,#2563eb,#7c3aed) !important;color:#fff !important;padding:8px 18px !important;border-radius:22px !important;font-weight:600 !important;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.btn-admin{background:linear-gradient(135deg,#7c3aed,#6d28d9) !important;color:#fff !important;padding:7px 14px !important;border-radius:22px !important;font-weight:600 !important}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 40%,#2563eb 75%,#7c3aed 100%);padding:64px 32px 56px;position:relative;overflow:hidden}
.hero-particle{position:absolute;border-radius:50%;pointer-events:none;animation:particleDrift linear infinite}
@keyframes particleDrift{0%{transform:translateY(0) rotate(0deg);opacity:.6}100%{transform:translateY(-100px) rotate(360deg);opacity:0}}
.hero-inner{max-width:1200px;margin:0 auto;position:relative;z-index:1;display:grid;grid-template-columns:1fr auto;gap:40px;align-items:center}
@media(max-width:700px){.hero-inner{grid-template-columns:1fr}}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:5px 14px;font-size:12px;color:rgba(255,255,255,.85);font-weight:500;margin-bottom:16px;backdrop-filter:blur(8px)}
.hero-title{font-size:42px;font-weight:900;color:#fff;line-height:1.15;margin-bottom:12px;animation:heroIn .7s ease}
.hero-title span{background:linear-gradient(135deg,#60a5fa,#a78bfa,#f472b6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
@keyframes heroIn{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.hero-sub{font-size:17px;color:rgba(255,255,255,.75);margin-bottom:28px;animation:heroIn .7s ease .1s both;line-height:1.6}
.hero-actions{display:flex;gap:12px;animation:heroIn .7s ease .2s both}
.hero-btn-primary{padding:13px 28px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:14px;font-size:15px;font-weight:700;border:none;cursor:pointer;box-shadow:0 6px 20px rgba(37,99,235,.4);transition:all .25s;display:inline-block}
.hero-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(37,99,235,.5);color:#fff}
.hero-btn-secondary{padding:13px 28px;background:rgba(255,255,255,.12);color:#fff;border-radius:14px;font-size:15px;font-weight:600;border:1px solid rgba(255,255,255,.25);cursor:pointer;backdrop-filter:blur(8px);transition:all .25s;display:inline-block}
.hero-btn-secondary:hover{background:rgba(255,255,255,.2);color:#fff}
.hero-stats{display:flex;flex-direction:column;gap:12px;animation:heroIn .7s ease .3s both}
.hero-stat{background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.18);border-radius:16px;padding:16px 22px;text-align:center;min-width:110px}
.hero-stat-num{font-size:28px;font-weight:800;color:#fff}
.hero-stat-label{font-size:11px;color:rgba(255,255,255,.65);margin-top:2px;font-weight:500}

/* ── FILTER BAR ── */
.filter-wrap{background:rgba(15,15,26,.8);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.07);padding:14px 32px;position:sticky;top:66px;z-index:100;box-shadow:0 4px 20px rgba(0,0,0,.2)}
.filter-inner{max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.filter-label{font-size:11px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.08em;margin-right:4px}
.chip{padding:7px 18px;border-radius:22px;border:1.5px solid rgba(255,255,255,.12);font-size:13px;font-weight:500;color:rgba(255,255,255,.65);background:rgba(255,255,255,.05);transition:all .2s;white-space:nowrap;cursor:pointer}
.chip:hover{border-color:#2563eb;color:#60a5fa;transform:translateY(-1px);background:rgba(37,99,235,.15)}
.chip.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-color:transparent;box-shadow:0 4px 14px rgba(37,99,235,.4)}

/* ── MAIN ── */
.main{max-width:1200px;margin:0 auto;padding:32px 20px}
.section-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.section-title{font-size:20px;font-weight:800;background:linear-gradient(135deg,#fff,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.results-count{font-size:13px;color:rgba(255,255,255,.35);font-weight:500;background:rgba(255,255,255,.06);padding:5px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.08)}

/* ── BOOK GRID ── */
.book-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:24px}

/* ── BOOK CARD ── */
.book-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:20px;overflow:hidden;transition:all .35s cubic-bezier(.4,0,.2,1);position:relative;animation:cardIn .5s ease both;cursor:pointer;backdrop-filter:blur(12px)}
.book-card:hover{transform:translateY(-10px);box-shadow:0 24px 60px rgba(37,99,235,.25),0 0 0 1px rgba(37,99,235,.3);border-color:rgba(37,99,235,.4);background:rgba(255,255,255,.09)}
@keyframes cardIn{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
.book-card:nth-child(1){animation-delay:.04s}
.book-card:nth-child(2){animation-delay:.08s}
.book-card:nth-child(3){animation-delay:.12s}
.book-card:nth-child(4){animation-delay:.16s}
.book-card:nth-child(5){animation-delay:.20s}
.book-card:nth-child(6){animation-delay:.24s}

/* Image with overlay */
.book-img{height:210px;background:rgba(255,255,255,.04);overflow:hidden;position:relative}
.book-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.4,0,.2,1)}
.book-card:hover .book-img img{transform:scale(1.08)}
.book-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(15,23,42,.88) 0%,rgba(15,23,42,.3) 50%,transparent 100%);opacity:0;transition:opacity .3s;display:flex;align-items:flex-end;justify-content:center;padding-bottom:18px}
.book-card:hover .book-overlay{opacity:1}
.overlay-view-btn{background:#fff;color:#2563eb;border:none;padding:9px 24px;border-radius:22px;font-size:13px;font-weight:700;cursor:pointer;transform:translateY(10px);transition:transform .3s;display:inline-block;box-shadow:0 4px 12px rgba(0,0,0,.2)}
.book-card:hover .overlay-view-btn{transform:translateY(0)}

/* Wishlist btn */
.wish-btn{position:absolute;top:10px;right:10px;width:32px;height:32px;background:rgba(255,255,255,.9);border:none;border-radius:50%;font-size:15px;cursor:pointer;opacity:0;transform:scale(.8);transition:all .25s;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px)}
.book-card:hover .wish-btn{opacity:1;transform:scale(1)}
.wish-btn:hover{background:#fff;transform:scale(1.15) !important}

/* New badge */
.new-badge{position:absolute;top:10px;left:10px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.05em}

/* Card info */
.book-info{padding:16px}
.cat-tag{display:inline-block;background:rgba(37,99,235,.2);color:#60a5fa;font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;margin-bottom:8px;border:1px solid rgba(37,99,235,.3)}
.book-title{font-size:15px;font-weight:700;color:#f1f5f9;line-height:1.3;margin-bottom:4px}
.book-author{font-size:12px;color:rgba(255,255,255,.4);margin-bottom:12px}
.book-footer{display:flex;align-items:center;justify-content:space-between}
.book-price{font-size:17px;font-weight:800;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.view-btn{padding:6px 16px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:22px;font-size:12px;font-weight:700;transition:all .2s;border:none;cursor:pointer;display:inline-block}
.view-btn:hover{transform:scale(1.08);box-shadow:0 4px 14px rgba(37,99,235,.5);color:#fff}
.out-badge{font-size:11px;color:#f87171;font-weight:600;background:rgba(239,68,68,.15);padding:4px 10px;border-radius:20px;border:1px solid rgba(239,68,68,.25)}

/* Stock bar */
.stock-bar{height:3px;background:rgba(255,255,255,.08);border-radius:2px;margin-top:10px;overflow:hidden}
.stock-fill{height:100%;border-radius:2px;background:linear-gradient(90deg,#2563eb,#7c3aed);width:0;transition:width 1.2s cubic-bezier(.4,0,.2,1)}

/* No results */
.no-results{grid-column:1/-1;text-align:center;padding:72px 20px;color:rgba(255,255,255,.3)}
.no-results-icon{font-size:56px;margin-bottom:14px;display:block;animation:shake .5s ease}
@keyframes shake{0%,100%{transform:rotate(0)}25%{transform:rotate(-12deg)}75%{transform:rotate(12deg)}}

/* FAB scroll top */
.fab{position:fixed;bottom:28px;right:28px;width:48px;height:48px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:20px;box-shadow:0 6px 20px rgba(37,99,235,.4);opacity:0;transform:translateY(12px);transition:all .3s;z-index:999;display:flex;align-items:center;justify-content:center}
.fab.show{opacity:1;transform:translateY(0)}
.fab:hover{transform:scale(1.12);box-shadow:0 10px 28px rgba(37,99,235,.5)}
</style>
</head>
<body>
<div id="progress"></div>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="logo-icon">📚</div>
    <div>
      <div class="logo-text">BookStore</div>
      <div class="logo-sub">YOUR READING PARADISE</div>
    </div>
  </a>
  <div class="nav-search">
    <span class="search-ico">🔍</span>
    <input type="text" id="live-search"
           placeholder="Search books, authors, genres..."
           value="<?= htmlspecialchars($search) ?>"
           oninput="liveSearch(this.value)"
           autocomplete="off">
    <button class="search-btn" onclick="liveSearch(document.getElementById('live-search').value)">→</button>
  </div>
  <div class="nav-links">
    <?php if (isLoggedIn()): ?>
      <div class="cart-wrap">
        <a href="cart.php">🛒 Cart</a>
        <?php if ($cart_count > 0): ?>
          <span class="cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
      </div>
      <a href="orders.php">📦 Orders</a>
      <?php if (isAdmin()): ?>
        <a href="admin/dashboard.php" class="btn-admin">⚙️ Admin</a>
      <?php endif; ?>
      <a href="logout.php">👋 <?= $_SESSION['name'] ?></a>
    <?php else: ?>
      <a href="auth.php" class="btn-login">Login / Register</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO BANNER -->
<section class="hero" id="hero">
  <div class="hero-inner">
    <div>
      <div class="hero-badge">✨ Welcome to BookStore</div>
      <h1 class="hero-title">Discover Your Next<br><span>Favourite Book</span></h1>
      <p class="hero-sub">Browse thousands of hand-picked books across all genres.<br>Fast delivery, great prices, and always free shipping.</p>
      <div class="hero-actions">
        <a href="#books" class="hero-btn-primary" onclick="document.getElementById('books').scrollIntoView({behavior:'smooth'});return false">Browse Books →</a>
        <?php if (!isLoggedIn()): ?>
        <a href="auth.php" class="hero-btn-secondary">Create Account</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-stats">
      <div class="hero-stat">
        <div class="hero-stat-num" id="cnt-books">0</div>
        <div class="hero-stat-label">Books</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num"><?= $total_cats ?></div>
        <div class="hero-stat-label">Categories</div>
      </div>
      <div class="hero-stat">
        <div class="hero-stat-num">Free</div>
        <div class="hero-stat-label">Delivery</div>
      </div>
    </div>
  </div>
</section>

<!-- FILTER BAR -->
<div class="filter-wrap">
  <div class="filter-inner">
    <span class="filter-label">Genre:</span>
    <a href="index.php" class="chip <?= !$category ? 'active' : '' ?>">✨ All</a>
    <?php
    mysqli_data_seek($categories, 0);
    $icons=['Biography'=>'👤','Children'=>'🧒','Fiction'=>'✨','History'=>'📜','Non-Fiction'=>'📰','Science'=>'🔬','Self-Help'=>'💪','Technology'=>'💻'];
    while ($cat=mysqli_fetch_assoc($categories)):
      $ic=$icons[$cat['category_name']]??'📁';
    ?>
      <a href="?category=<?= $cat['category_id'] ?>"
         class="chip <?= $category==$cat['category_id']?'active':'' ?>">
        <?= $ic ?> <?= $cat['category_name'] ?>
      </a>
    <?php endwhile; ?>
  </div>
</div>

<!-- BOOK GRID -->
<div class="main" id="books">
  <div class="section-bar">
    <div class="section-title">
      <?= $search ? "Results for \"".htmlspecialchars($search)."\"" : ($category ? 'Filtered Books' : '📚 All Books') ?>
    </div>
    <div class="results-count" id="res-count"><?= mysqli_num_rows($books) ?> books</div>
  </div>

  <div class="book-grid" id="book-grid">
    <?php if (mysqli_num_rows($books)===0): ?>
      <div class="no-results">
        <span class="no-results-icon">🔍</span>
        <p>No books found. Try a different search.</p>
      </div>
    <?php endif; ?>

    <?php $i=0; while ($book=mysqli_fetch_assoc($books)):
      $stock_pct = min(100, ($book['stock']/60)*100);
      $is_new = (strtotime($book['created_at']) > strtotime('-30 days'));
    ?>
    <div class="book-card"
         data-title="<?= strtolower(htmlspecialchars($book['title'])) ?>"
         data-author="<?= strtolower(htmlspecialchars($book['author'])) ?>"
         data-cat="<?= strtolower($book['category_name']) ?>"
         onclick="window.location='pages/book_detail.php?id=<?= $book['book_id'] ?>'">
      <div class="book-img">
        <img src="images/<?= $book['image'] ?>"
             alt="<?= htmlspecialchars($book['title']) ?>"
             onerror="this.src='images/default.jpg'"
             loading="lazy">
        <?php if ($is_new): ?><span class="new-badge">NEW</span><?php endif; ?>
        <button class="wish-btn" title="Add to wishlist" onclick="event.stopPropagation();toggleWish(this)">🤍</button>
        <div class="book-overlay">
          <a href="pages/book_detail.php?id=<?= $book['book_id'] ?>"
             class="overlay-view-btn"
             onclick="event.stopPropagation()">👁 Quick View</a>
        </div>
      </div>
      <div class="book-info">
        <span class="cat-tag"><?= $book['category_name'] ?></span>
        <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
        <div class="book-author">by <?= htmlspecialchars($book['author']) ?></div>
        <div class="book-footer">
          <span class="book-price">Rs. <?= number_format($book['price'],2) ?></span>
          <?php if ($book['stock']>0): ?>
            <a href="pages/book_detail.php?id=<?= $book['book_id'] ?>"
               class="view-btn"
               onclick="event.stopPropagation()">View →</a>
          <?php else: ?>
            <span class="out-badge">Out of Stock</span>
          <?php endif; ?>
        </div>
        <div class="stock-bar">
          <div class="stock-fill" data-w="<?= $stock_pct ?>%"></div>
        </div>
      </div>
    </div>
    <?php $i++; endwhile; ?>
  </div>
</div>

<button class="fab" id="fab" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<script>
// Hero counter
function animCount(el,target,dur=1000){
  let v=0,step=target/(dur/16);
  const t=setInterval(()=>{v+=step;if(v>=target){el.textContent=target;clearInterval(t);return;}el.textContent=Math.floor(v);},16);
}
animCount(document.getElementById('cnt-books'),<?= $total_books ?>);

// Stock bar intersection observer
const obs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting){const f=e.target.querySelector('.stock-fill');if(f)f.style.width=f.dataset.w;}});
},{threshold:.2});
document.querySelectorAll('.book-card').forEach(c=>obs.observe(c));

// Live search
function liveSearch(q){
  q=q.toLowerCase().trim();
  const cards=document.querySelectorAll('.book-card');
  let vis=0;
  cards.forEach(c=>{
    const match=!q||c.dataset.title.includes(q)||c.dataset.author.includes(q)||c.dataset.cat.includes(q);
    c.style.display=match?'':'none';
    if(match)vis++;
  });
  const rc=document.getElementById('res-count');
  if(rc)rc.textContent=vis+' books';
  let nr=document.getElementById('nr-live');
  if(!nr){nr=document.createElement('div');nr.id='nr-live';nr.className='no-results';nr.innerHTML='<span class="no-results-icon">🔍</span><p>No books found for "'+q+'"</p>';document.getElementById('book-grid').appendChild(nr);}
  nr.style.display=vis===0&&q?'':'none';
}

// Wishlist toggle
function toggleWish(btn){
  btn.textContent=btn.textContent==='🤍'?'❤️':'🤍';
  btn.style.transform='scale(1.3)';
  setTimeout(()=>btn.style.transform='scale(1)',200);
}

// Scroll progress + FAB
window.addEventListener('scroll',()=>{
  const el=document.documentElement;
  document.getElementById('progress').style.width=(el.scrollTop/(el.scrollHeight-el.clientHeight)*100)+'%';
  document.getElementById('fab').classList.toggle('show',window.scrollY>300);
});

// Run on load
window.addEventListener('load',()=>{
  const v=document.getElementById('live-search').value;
  if(v)liveSearch(v);
});

// Hero floating particles
const hero=document.getElementById('hero');
['📚','📖','✏️','🔖','📕','📗'].forEach((e,i)=>{
  const p=document.createElement('div');
  p.className='hero-particle';
  p.textContent=e;
  p.style.cssText=`left:${10+i*15}%;bottom:-20px;font-size:${18+i*4}px;animation-duration:${6+i*2}s;animation-delay:${-i*1.5}s;opacity:.12`;
  hero.appendChild(p);
});
</script>
</body>
</html>
