<?php
require_once '../config.php'; // เชื่อมต่อฐานข้อมูล
require_once 'auth_admin.php';
$who = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root{
    --bg:#f6f7fb;
    --ink:#0f172a;
    --muted:#64748b;
    --card:#ffffffcc;           /* glass */
    --stroke:#e6e8ef;
    --brand:#5b8cff;            /* ฟ้าพาสเทล */
    --brand2:#8b5bff;           /* ม่วงพาสเทล */
  }

  html,body{height:100%}
  body{
    margin:0; background:var(--bg); color:var(--ink);
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
  }

  /* พื้นหลังนามธรรม (ให้เหมือน register.php) */
  .bg-abstract{
    position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
      radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
      radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%);
  }
  .bg-abstract::after{
    content:""; position:absolute; inset:0;
    background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
    filter: blur(28px); opacity:.6;
  }

  .page{
    min-height:100svh; display:grid; place-items:center; padding:24px;
  }

  /* การ์ดแบบ Glassmorphism */
  .card-glass{
    width:min(1100px, 96vw);
    background:var(--card);
    backdrop-filter: blur(12px);
    border:1px solid var(--stroke);
    border-radius:24px;
    box-shadow:0 12px 36px rgba(15,23,42,.12);
    overflow:hidden;
  }
  .head{
    padding:22px 22px 12px;
    border-bottom:1px solid rgba(15,23,42,.06);
  }
  .title{
    margin:0; font-weight:900; letter-spacing:.2px;
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    -webkit-background-clip:text; background-clip:text; color:transparent;
    font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
  }
  .sub{ margin:6px 0 0; color:var(--muted); }

  .body{ padding:22px; }

  /* ปุ่มสไตล์เดียวกับชุดใหม่ */
  .btn-brand{
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    border:none; color:#fff; font-weight:800;
    box-shadow:0 10px 24px rgba(91,140,255,.25);
  }
  .btn-ghost{
    border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155;
  }
  .btn-soft{
    border:1px solid #e8eaf3; background:#fff; color:#334155; font-weight:700;
  }

  /* กริดปุ่มเมนู */
  .grid{
    display:grid; gap:14px;
    grid-template-columns: repeat(3,1fr);
  }
  @media (max-width: 920px){ .grid{ grid-template-columns: repeat(2,1fr); } }
  @media (max-width: 600px){ .grid{ grid-template-columns: 1fr; } }

  .tile{
    background:#fff;
    border:1px solid #e8eaf3;
    border-radius:16px;
    padding:18px;
    display:flex; flex-direction:column; gap:10px;
    box-shadow:0 10px 24px rgba(15,23,42,.06);
    transition: transform .15s, box-shadow .15s;
    text-decoration:none; color:inherit;
  }
  .tile:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(15,23,42,.10); }
  .tile h5{ margin:0; font-weight:800; }
  .tile p{ margin:0; color:var(--muted); }
</style>
</head>

<body>
<div class="bg-abstract"></div>

<div class="page">
  <div class="card-glass">
    <div class="head">
      <h1 class="title">ระบบผู้ดูแลระบบ</h1>
      <p class="sub">ยินดีต้อนรับ, <strong><?= htmlspecialchars($who) ?></strong></p>
    </div>

    <div class="body">
      <div class="grid mb-2">
        <a href="products.php" class="tile">
          <h5>จัดการสินค้า</h5>
          <span class="btn btn-brand"> → </span>
        </a>

        <a href="orders.php" class="tile">
          <h5>จัดการคำสั่งซื้อ</h5>
          <span class="btn btn-brand"> → </span>
        </a>

        <a href="users.php" class="tile">
          <h5>จัดการสมาชิก</h5>
          <span class="btn btn-brand"> → </span>
        </a>

        <a href="category.php" class="tile">
          <h5>จัดการหมวดหมู่</h5>
          <span class="btn btn-brand"> → </span>
        </a>
      </div>

      <a href="../logout.php" class="btn btn-ghost">ออกจากระบบ</a>
    </div>
  </div>
</div>
</body>
</html>
