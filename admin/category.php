<?php
require '../config.php'; // เชื่อมต่อข้อมูลด้วย PDO
require 'auth_admin.php'; // กำหนดสิทธิ์ (Admin Guard)

// เพิ่มหมวดหมู่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
  $category_name = trim($_POST['category_name']);
  if ($category_name) {
      $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
      $stmt->execute([$category_name]);
      header("Location: category.php");
      exit;
  }
}

// ลบหมวดหมู่ (เช็คว่ามีสินค้าในหมวดหรือไม่)
if (isset($_GET['delete'])) {
  $category_id = $_GET['delete'];
  $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
  $stmt->execute([$category_id]);
  $productCount = $stmt->fetchColumn();
  if ($productCount > 0) {
      $_SESSION['error'] = "ไม่สามารถลบได้เนื่องจากยังมีสินค้าอยู่";
  } else {
      $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
      $stmt->execute([$category_id]);
      $_SESSION['success'] = "ลบหมวดหมู่นี้เรียบร้อยแล้ว";
  }
  header("Location: category.php");
  exit;
}

// แก้ไขหมวดหมู่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
  $category_id = $_POST['category_id'];
  $category_name = trim($_POST['new_name']);
  if ($category_name) {
      $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
      $stmt->execute([$category_name, $category_id]);
      header("Location: category.php");
      exit;
  }
}

// ดึงหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT * FROM categories ORDER BY category_id ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>จัดการหมวดหมู่</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- โทนสว่าง-มินิมอล + Glass + พื้นหลังนามธรรม ให้เหมือน register.php -->
  <style>
    :root{
      --bg:#f6f7fb;
      --ink:#0f172a;
      --muted:#64748b;
      --card:#ffffffcc;        /* glass */
      --stroke:#e6e8ef;
      --brand:#5b8cff;         /* ฟ้าพาสเทล */
      --brand2:#8b5bff;        /* ม่วงพาสเทล */
    }

    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif;
    }

    /* พื้นหลังนามธรรม */
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

    .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }

    /* การ์ดครอบหน้า (Glassmorphism) */
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
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .title{
      margin:0; font-weight:900; letter-spacing:.2px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      -webkit-background-clip:text; background-clip:text; color:transparent;
      font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem);
    }

    .body{ padding:22px; }

    /* ปุ่มโทนเดียวกับชุดใหม่ */
    .btn-brand{
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      border:none; color:#fff; font-weight:800;
      box-shadow:0 10px 24px rgba(91,140,255,.25);
    }
    .btn-ghost{
      border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155;
    }

    /* กล่องบล็อก (ฟอร์ม/ตาราง) */
    .block{
      background:#fff;
      border:1px solid #e8eaf3;
      border-radius:16px;
      box-shadow:0 10px 24px rgba(15,23,42,.06);
      padding:18px;
    }

    /* ฟอร์ม */
    .form-label{ font-weight:700; color:#1f2937; }
    .form-control, .form-select{
      background:#fff; color:#0f172a; border:1px solid #dfe3f3;
    }
    .form-control::placeholder{ color:#9aa3b8; }
    .form-control:focus, .form-select:focus{
      border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18);
    }

    /* ตาราง */
    table.table{ background:#fff; border-color:#eef1f7; margin-bottom:0; }
    thead.table-light th{
      background:#f3f6ff; border-bottom:1px solid #e5e9f5;
      color:#334155; font-weight:800; letter-spacing:.2px;
    }
    tbody td{ vertical-align: middle; }

    .mb-18{ margin-bottom:18px; }
  </style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">
      <div class="head">
        <h1 class="title">จัดการหมวดหมู่</h1>
        <a href="index.php" class="btn btn-ghost">← กลับหน้าผู้ดูแล</a>
      </div>

      <div class="body">
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger mb-18"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success mb-18"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- ฟอร์มเพิ่มหมวดหมู่ -->
        <div class="block mb-18">
          <form method="post" class="row g-3">
            <div class="col-md-6">
              <label for="catname" class="form-label">ชื่อหมวดหมู่</label>
              <input id="catname" type="text" name="category_name" class="form-control" placeholder="เช่น เสื้อผ้า, อุปกรณ์ไอที" required>
            </div>
            <div class="col-md-2 align-self-end">
              <button type="submit" name="add_category" class="btn btn-brand">เพิ่มหมวดหมู่</button>
            </div>
          </form>
        </div>

        <!-- ตารางรายการหมวดหมู่ -->
        <div class="block">
          <h5 class="mb-3">รายการหมวดหมู่</h5>
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>ชื่อหมวดหมู่</th>
                  <th>แก้ไขชื่อ</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($categories as $cat): ?>
                  <tr>
                    <td><?= htmlspecialchars($cat['category_name']) ?></td>
                    <td>
                      <form method="post" class="d-flex">
                        <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                        <input type="text" name="new_name" class="form-control me-2" placeholder="ชื่อใหม่" required>
                        <button type="submit" name="update_category" class="btn btn-sm btn-warning">แก้ไข</button>
                      </form>
                    </td>
                    <td>
                      <a href="category.php?delete=<?= $cat['category_id'] ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('คุณต้องการลบหมวดหมู่นี้หรือไม่ ?')">ลบ</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
