<?php
require 'session_timeout.php'; 
require_once 'config.php';

$isLoggedIn = isset($_SESSION['user_id']);

$stmt = $conn->query("SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>หน้าหลัก</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --bg:#f6f7fb; --ink:#0f172a; --muted:#64748b;
      --card:#ffffffcc;
      --stroke:#e6e8ef;
      --brand:#5b8cff;
      --brand2:#8b5bff;
    }
    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--ink);
      font-family:ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
    }
    .bg-abstract{position:fixed; inset:0; z-index:-1; pointer-events:none;
      background:
        radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
        radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
        radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);}
    .bg-abstract::after{content:""; position:absolute; inset:0;
      background:conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
      filter:blur(28px); opacity:.6;}

    .page{min-height:100svh; display:grid; place-items:start center; padding:24px;}
    .card-glass{
      width:min(1200px,96vw);
      background:var(--card); backdrop-filter:blur(12px);
      border:1px solid var(--stroke); border-radius:24px;
      box-shadow:0 12px 36px rgba(15,23,42,.12); overflow:hidden;
    }
    .nav-glass{border-bottom:1px solid rgba(15,23,42,.06);
      background:linear-gradient(#ffffffaa,#ffffffaa);
      backdrop-filter:saturate(110%) blur(12px);
      padding:12px 18px;}
    .navbar-brand{
      font-weight:900; letter-spacing:.2px;
      background:linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size:clamp(1.1rem, .8rem + 1.1vw, 1.6rem);
      margin-right:10px;
    }
    .hello-badge{color:var(--muted); font-weight:700; white-space:nowrap;
      border:1px solid #e5e7f2; border-radius:999px; padding:.35rem .7rem; background:#fff;}
    .btn-chip{border:1px solid #e5e7f2; background:#fff; color:#334155; font-weight:700;
      border-radius:999px; padding:.45rem .9rem;}
    .btn-chip:hover{background:linear-gradient(135deg,var(--brand),var(--brand2)); color:#fff; border-color:transparent;}
    .btn-ghost{border:1px solid #dfe3f3; background:#fff; color:#334155; font-weight:700;
      border-radius:999px; padding:.45rem .9rem;}
    .btn-ghost:hover{background:linear-gradient(135deg,var(--brand),var(--brand2)); color:#fff; border-color:transparent;}

    .body{padding:22px;}

    /* Toolbar 3 คอลัมน์: ซ้าย=ค้นหา กลาง=หมวดหมู่ ขวา=จัดเรียง+wishlist */
    .toolbar{display:grid; grid-template-columns: 1fr minmax(220px, 320px) auto; gap:12px; align-items:center;}
    @media (max-width: 992px){
      .toolbar{grid-template-columns: 1fr; align-items:stretch;}
    }

    .product-list{row-gap:22px;}
    .product-card{background:#fff; border:1px solid #ebeff5; border-radius:16px;
      box-shadow:0 4px 12px rgba(15,23,42,.06); transition:transform .12s, box-shadow .12s;
      display:flex; flex-direction:column; overflow:hidden; opacity:0; transform:translateY(10px);}
    .product-card:hover{transform:translateY(0); box-shadow:0 8px 18px rgba(15,23,42,.10);}
    .thumb-wrap{position:relative;}
    .product-thumb{width:100%; aspect-ratio:1/1; object-fit:cover; display:block; background:#f4f6fa;}
    @media (min-width: 992px){ .product-thumb{ max-height:240px; } }
    .wishlist{position:absolute; right:10px; top:10px; z-index:2; width:34px; height:34px; display:grid; place-items:center;
      background:#ffffffd9; border:1px solid #e9eef8; border-radius:10px; color:#94a3b8;}
    .wishlist:hover{color:#e11d48;}
    .wishlist.active{color:#e11d48; border-color:#ffc9d1; background:#fff5f7;}
    .card-chip{position:absolute; top:10px; left:10px; background:#eef2ff; color:#3f51b5; border:1px solid #e0e7ff;
      font-size:.72rem; padding:.2rem .5rem; border-radius:999px; box-shadow:0 2px 6px rgba(15,23,42,.08);}
    .card-chip.danger{background:#fff1f2; color:#e11d48; border-color:#ffe4e6;}
    .card-body-slim{padding:14px 14px 12px; display:flex; flex-direction:column;}
    .product-meta{font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; color:#8a94a6; margin-bottom:6px;}
    .product-title{font-size:1rem; font-weight:700; color:#0f172a; margin:0 0 6px; line-height:1.25;}
    .rating i{color:#ffc107; font-size:.9rem;}
    .price{font-weight:800; color:#0f172a;}
    .stock-badge{background:#f6f7fb; border:1px solid #ebeff5; color:#64748b; border-radius:999px; padding:.15rem .5rem; font-size:.75rem;}
    .card-actions{margin-top:10px; display:flex; gap:.5rem; align-items:center;}
    .card-actions .btn{white-space:nowrap;}
    .btn-detail{background:#fff; border:1px solid #dfe3f3; color:#334155; font-weight:700; border-radius:10px; padding:.42rem .8rem;}
    .btn-detail:hover{background:linear-gradient(135deg,var(--brand),var(--brand2)); color:#fff;}
    .btn-cart{background:linear-gradient(135deg,var(--brand),var(--brand2)); border:none; color:#fff; font-weight:800; border-radius:10px; padding:.42rem .8rem; box-shadow:0 8px 18px rgba(91,140,255,.18);}
    .btn-cart:disabled{opacity:.6; box-shadow:none;}

    .skeleton{position:relative; background:#eef2ff;}
    .skeleton::after{content:""; position:absolute; inset:0;
      background:linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent);
      animation:shimmer 1.2s infinite;}
    @keyframes shimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}

    .product-card.appear{opacity:1; transform:translateY(0); transition:opacity .35s ease, transform .35s ease;}
    .nav-glass.scrolled{box-shadow:0 8px 24px rgba(15,23,42,.08);}

    .toast-fixed{position:fixed; right:16px; bottom:16px; z-index:1080;}
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">

      <nav class="navbar navbar-expand-lg nav-glass">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">รายการสินค้า</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav"
                  aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse justify-content-end" id="topNav">
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
              <?php if ($isLoggedIn): ?>
                <li class="nav-item mb-2 mb-lg-0">
                  <span class="hello-badge">
                    ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['username'] ?? 'ผู้ใช้') ?>
                    (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)
                  </span>
                </li>
                <li class="nav-item"><a class="btn btn-chip ms-lg-2 mb-2 mb-lg-0" href="profile.php">ข้อมูลส่วนตัว</a></li>
                <li class="nav-item"><a class="btn btn-chip ms-lg-1 mb-2 mb-lg-0" href="orders.php">ประวัติการสั่งซื้อ</a></li>
                <li class="nav-item"><a class="btn btn-chip ms-lg-1 mb-2 mb-lg-0" href="cart.php">ดูตะกร้าสินค้า</a></li>
                <li class="nav-item"><a class="btn btn-ghost ms-lg-1" href="logout.php">ออกจากระบบ</a></li>
              <?php else: ?>
                <li class="nav-item"><a class="btn btn-chip mb-2 mb-lg-0" href="login.php"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ</a></li>
                <li class="nav-item"><a class="btn btn-ghost ms-lg-1" href="register.php">สมัครสมาชิก</a></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </nav>

      <!-- TOOLBAR (3 คอลัมน์) -->
      <div class="px-3 px-lg-4 pt-3">
        <div class="toolbar">
          <!-- ซ้าย: ค้นหา -->
          <div class="d-flex gap-2 align-items-center">
            <div class="input-group" style="max-width:480px; width:100%;">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input id="searchInput" type="text" class="form-control" placeholder="ค้นหาสินค้า...">
            </div>
            <span class="badge text-bg-light d-none d-lg-inline" id="resultCount">ทั้งหมด <?= count($products) ?> รายการ</span>
          </div>

          <!-- กลาง: หมวดหมู่ -->
          <div class="d-flex">
            <select id="catSelect" class="form-select w-100">
              <option value="">ทุกหมวดหมู่</option>
            </select>
          </div>

          <!-- ขวา: จัดเรียง + wishlist -->
          <div class="d-flex gap-2 justify-content-end">
            <select id="sortSelect" class="form-select" style="max-width:220px;">
              <option value="new">จัดเรียง: ใหม่ล่าสุด</option>
              <option value="priceAsc">ราคาต่ำ → สูง</option>
              <option value="priceDesc">ราคาสูง → ต่ำ</option>
              <option value="stockDesc">สต็อกมาก → น้อย</option>
            </select>
            <button class="btn btn-outline-secondary" id="wishlistToggle"><i class="bi bi-heart"></i> เฉพาะที่อยากได้</button>
          </div>
        </div>
      </div>

      <div class="body">
        <div class="row product-list">
          <?php foreach ($products as $p): ?>
            <?php
              $img = !empty($p['image']) ? 'product_images/' . rawurlencode($p['image']) : 'product_images/no-image.jpg';
              $isNew = isset($p['created_at']) && (time() - strtotime($p['created_at']) <= 7 * 24 * 3600);
              $isHot = (int)$p['stock'] > 0 && (int)$p['stock'] < 5;
              $rating = isset($p['rating']) ? (float)$p['rating'] : 4.5;
              $full = floor($rating); $half = ($rating - $full) >= 0.5 ? 1 : 0;
            ?>
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="product-card h-100"
                   data-id="<?= (int)$p['product_id'] ?>"
                   data-name="<?= htmlspecialchars($p['product_name']) ?>"
                   data-category="<?= htmlspecialchars($p['category_name'] ?? 'หมวดหมู่') ?>"
                   data-price="<?= (float)$p['price'] ?>"
                   data-created="<?= htmlspecialchars($p['created_at'] ?? '') ?>"
                   data-stock="<?= (int)$p['stock'] ?>">
                <div class="thumb-wrap">
                  <?php if ($isNew): ?><span class="card-chip">NEW</span>
                  <?php elseif ($isHot): ?><span class="card-chip danger">HOT</span><?php endif; ?>

                  <a href="product_detail.php?id=<?= (int)$p['product_id'] ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" class="product-thumb skeleton" loading="lazy">
                  </a>

                  <button class="wishlist" type="button" title="เพิ่มรายการที่อยากได้">
                    <i class="bi bi-heart"></i>
                  </button>
                </div>

                <div class="card-body-slim">
                  <div class="product-meta"><?= htmlspecialchars($p['category_name'] ?? 'หมวดหมู่') ?></div>
                  <a class="text-decoration-none" href="product_detail.php?id=<?= (int)$p['product_id'] ?>">
                    <h6 class="product-title"><?= htmlspecialchars($p['product_name']) ?></h6>
                  </a>

                  <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rating">
                      <?php for ($i=0; $i<$full; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                      <?php if ($half): ?><i class="bi bi-star-half"></i><?php endif; ?>
                      <?php for ($i=0; $i<5-$full-$half; $i++): ?><i class="bi bi-star"></i><?php endfor; ?>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between">
                    <div class="price"><?= number_format((float)$p['price'], 2) ?> บาท</div>
                    <span class="stock-badge">คงเหลือ <?= (int)$p['stock'] ?></span>
                  </div>

                  <div class="card-actions">
                    <?php if ($isLoggedIn): ?>
                      <form action="cart.php" method="post" class="d-inline-flex">
                        <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-sm btn-cart">
                          <i class="bi bi-cart-plus"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <a href="product_detail.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm btn-detail ms-auto">
                      <i class="bi bi-eye"></i> ดูรายละเอียด
                    </a>
                  </div>
                </div>

              </div>
            </div>
          <?php endforeach; ?>

          <?php if (!$products): ?>
            <div class="col-12">
              <div class="alert alert-warning mb-0">ยังไม่มีสินค้าในระบบ</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- Toast -->
  <div class="toast align-items-center toast-fixed text-bg-dark border-0" id="liveToast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMsg">ดำเนินการสำเร็จ</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>

  <!-- Quick View Modal -->
  <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="border-radius:18px;">
        <div class="modal-header">
          <h5 class="modal-title" id="qvTitle">รายละเอียดสินค้า</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <img id="qvImg" class="img-fluid rounded" alt="">
            </div>
            <div class="col-12 col-md-6">
              <div class="text-muted small" id="qvCategory"></div>
              <h5 class="mt-1" id="qvName"></h5>
              <div class="d-flex align-items-center gap-2 my-2">
                <span class="fw-bold fs-5" id="qvPrice"></span>
                <span class="badge text-bg-light" id="qvStock"></span>
              </div>
              <div class="d-flex gap-2 mt-2">
                <a id="qvDetailLink" href="#" class="btn btn-outline-secondary">
                  <i class="bi bi-box-arrow-up-right"></i> ไปหน้ารายละเอียด
                </a>
                <button id="qvAddCart" class="btn btn-primary">
                  <i class="bi bi-cart-plus"></i> ใส่ตะกร้า
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer small text-muted">
          เคล็ดลับ: กดหัวใจเพื่อบันทึก “ที่อยากได้” ไว้ดูภายหลัง
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  (function(){
    const $  = (s, r=document)=>r.querySelector(s);
    const $$ = (s, r=document)=>Array.from(r.querySelectorAll(s));

    // navbar shadow
    const nav=$('.nav-glass');
    const onScroll=()=>nav&&nav.classList.toggle('scrolled',window.scrollY>8);
    onScroll(); window.addEventListener('scroll', onScroll);

    // skeleton remove
    $$('.product-thumb').forEach(img=>{
      const clear=()=>img.classList.remove('skeleton');
      img.complete ? clear() : img.addEventListener('load', clear, {once:true});
      img.addEventListener('error', clear, {once:true});
    });

    // appear
    const io=new IntersectionObserver(ents=>{
      ents.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('appear'); io.unobserve(e.target); } });
    },{threshold:.15});
    $$('.product-card').forEach(c=>io.observe(c));

    // Wishlist
    const WKEY='wishlist_products';
    const getWish=()=>JSON.parse(localStorage.getItem(WKEY)||'[]');
    const setWish=(a)=>localStorage.setItem(WKEY, JSON.stringify(a));
    const toggleWish=(id)=>{let a=getWish(); a=a.includes(id)?a.filter(x=>x!==id):[...a,id]; setWish(a); return a.includes(id);};

    $$('.product-card').forEach(card=>{
      const id=Number(card.dataset.id);
      const btn=$('.wishlist', card);
      if(getWish().includes(id)) btn.classList.add('active');
      btn?.addEventListener('click', (ev)=>{
        ev.stopPropagation();
        const active=toggleWish(id);
        btn.classList.toggle('active', active);
        if ($('#wishlistToggle')?.classList.contains('active')) applyFilters();
        toast(active?'บันทึกไว้ในที่อยากได้แล้ว':'นำออกจากที่อยากได้แล้ว');
      });
    });

    // Filters
    const grid = $('.row.product-list');
    const searchInput = $('#searchInput');
    const sortSelect  = $('#sortSelect');
    const wishToggle  = $('#wishlistToggle');
    const resultCount = $('#resultCount');
    const catSelect   = $('#catSelect');

    // ใช้ "คอลัมน์ bootstrap" เป็นหน่วยย้าย
    const rows = $$('.product-card').map(card=>{
      const col = card.closest('.col-12');
      return {
        col, card,
        id: Number(card.dataset.id),
        name: (card.dataset.name||'').toLowerCase(),
        cat:  (card.dataset.category||'').toLowerCase(),
        price: Number(card.dataset.price||0),
        created: new Date(card.dataset.created||0),
        stock: Number(card.dataset.stock||0),
      };
    });

    // เติมตัวเลือกหมวดหมู่แบบไดนามิก
    (function fillCategories(){
      if(!catSelect) return;
      const set = new Set(rows.map(r=>r.cat).filter(Boolean));
      const cats = Array.from(set).sort((a,b)=>a.localeCompare(b));
      cats.forEach(c=>{
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        catSelect.appendChild(opt);
      });
    })();

    function applyFilters(){
      const q = (searchInput?.value||'').toLowerCase().trim();
      const onlyWish = wishToggle?.classList.contains('active');
      const wishset = new Set(getWish());
      const cat = (catSelect?.value||'').toLowerCase();

      let filtered = rows.filter(r=>{
        if (onlyWish && !wishset.has(r.id)) return false;
        if (cat && r.cat !== cat) return false;
        return !q || r.name.includes(q) || r.cat.includes(q);
      });

      switch (sortSelect?.value) {
        case 'priceAsc':  filtered.sort((a,b)=>a.price-b.price); break;
        case 'priceDesc': filtered.sort((a,b)=>b.price-a.price); break;
        case 'stockDesc': filtered.sort((a,b)=>b.stock-a.stock); break;
        default:          filtered.sort((a,b)=>b.created-a.created);
      }

      filtered.forEach(r=> grid.appendChild(r.col));
      rows.forEach(r=>{ r.col.style.display = filtered.includes(r) ? '' : 'none'; });

      if (resultCount) resultCount.textContent = `พบ ${filtered.length} รายการ`;
    }

    if (wishToggle){
      const setIcon=()=> wishToggle.innerHTML = wishToggle.classList.contains('active')
        ? `<i class="bi bi-heart-fill"></i> เฉพาะที่อยากได้`
        : `<i class="bi bi-heart"></i> เฉพาะที่อยากได้`;
      setIcon();
      wishToggle.addEventListener('click', ()=>{ wishToggle.classList.toggle('active'); setIcon(); applyFilters(); });
    }
    searchInput?.addEventListener('input', applyFilters);
    sortSelect?.addEventListener('change', applyFilters);
    catSelect?.addEventListener('change', applyFilters);
    applyFilters();

    function flyToCart(imgEl){
      try{
        const cartBtn = [...$$('a,button')].find(b=>/ตะกร้าสินค้า|cart\.php/i.test(b.getAttribute('href')||b.textContent||''));
        if(!imgEl || !cartBtn) return toast('เพิ่มลงตะกร้าแล้ว');
        const r=imgEl.getBoundingClientRect(), t=cartBtn.getBoundingClientRect();
        const clone=imgEl.cloneNode(true);
        Object.assign(clone.style,{position:'fixed', left:r.left+'px', top:r.top+'px',
          width:r.width+'px', height:r.height+'px', zIndex:1080, transition:'all .7s cubic-bezier(.2,.6,.2,1)'});
        document.body.appendChild(clone);
        requestAnimationFrame(()=>{
          clone.style.left=(t.left+t.width/2-r.width*0.2)+'px';
          clone.style.top=(t.top+t.height/2-r.height*0.2)+'px';
          clone.style.width=r.width*0.4+'px';
          clone.style.height=r.height*0.4+'px';
          clone.style.opacity='0.2';
        });
        setTimeout(()=>clone.remove(),750);
      }catch{}
    }

    let toastObj;
    function toast(msg){
      const el=$('#liveToast'); if(!el) return alert(msg);
      $('#toastMsg').textContent=msg;
      (toastObj ||= new bootstrap.Toast(el,{delay:2000})).show();
    }
  })();
  </script>
</body>
</html>
