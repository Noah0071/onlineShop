<?php
require_once '../config.php';
require_once 'auth_admin.php';

// ตรวจสอบว่ามี ID สินค้าส่งเข้ามาหรือไม่ ?
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}
$product_id = $_GET['id'];
// ดึงข้อมูลสินค้า
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo "<h3>ไม่พบข้อมูลสินค้า</h3>";
    exit;
}
// ดึงหมวดหมู่ทั้งหมด
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
// เมื่อมีการส่งฟอร์มมา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    // ค่ำรูปเดิมจำกฟอร์ม
    $oldImage = $_POST['old_image'] ?? null;
    $removeImage = isset($_POST['remove_image']); // true/false
    if ($name && $price > 0) {
        // เตรียมตัวแปรรูปที่จะบันทึก
        $newImageName = $oldImage; // default: คงรูปเดิมไว้
        // 1) ถ้ามีติ๊ก "ลบรูปเดิม" → ตั้งให้เป็น null
    if ($removeImage) {
        $newImageName = null;
    }
    // 2) ถ้าอัปโหลดไฟล์ใหม่ → ตรวจแล้วเซฟไฟล์และตั้งชื่อใหม่ทับค่า
    if (!empty($_FILES['product_image']['name'])) {
    $file = $_FILES['product_image'];
    // ตรวจชนิดไฟล์แบบง่ำย (แนะนำ: ตรวจ MIME จริงด้วย finfo)
    $allowed = ['image/jpeg', 'image/png'];
    if (in_array($file['type'], $allowed, true) && $file['error'] === UPLOAD_ERR_OK) {
        // สรา้งชื่อไฟล์ใหม่
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newImageName = 'product_' . time() . '.' . $ext;
        $uploadDir = realpath(__DIR__ . '/../product_images');
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newImageName;
        // ย้ำยไฟล์อัปโหลด
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        // ถ้าย้ายไม่ได้ อาจตั้ง flash message แล้วคงใช้รูปเดิมไว้
        $newImageName = $oldImage;
    }
}
}
// อัปเดต DB
$sql = "UPDATE products
SET product_name = ?, description = ?, price = ?, stock = ?, category_id = ?, image = ?
WHERE product_id = ?";
$args = [$name, $description, $price, $stock, $category_id, $newImageName, $product_id];
$stmt = $conn->prepare($sql);
$stmt->execute($args);
// ลบไฟล์เก่ำในดิสก์ ถ ้ำ:
// - มีรูปเดิม ($oldImage) และ
// - เกดิ กำรเปลยี่ นรปู (อัปโหลดใหมห่ รอื สั่งลบรปู เดมิ)
if (!empty($oldImage) && $oldImage !== $newImageName) {
$baseDir = realpath(__DIR__ . '/../product_images');
$filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $oldImage);
if ($filePath && strpos($filePath, $baseDir) === 0 && is_file($filePath)) {
@unlink($filePath);
}
}
header("Location: products.php");
exit;
}
}
// สร้าง src ให้รูปปัจจุบัน + fallback
$baseDir = realpath(__DIR__ . '/../product_images');
$currentImageSrc = ''; // ยังไม่กำหนด

// ถ้ามีชื่อรูปใน DB และไฟล์มีอยู่จริง ให้ใช้รูปนั้น
if (!empty($product['image'])) {
  $real = realpath($baseDir . DIRECTORY_SEPARATOR . $product['image']);
  if ($real && strpos($real, $baseDir) === 0 && is_file($real)) {
    $currentImageSrc = '../product_images/' . rawurlencode($product['image']);
  }
}

// ถ้าไม่เจอหรือไม่มีรูป -> ใช้ no-image.png ถ้ามี, ไม่งั้นใช้ SVG data URI
if ($currentImageSrc === '') {
  $noImgReal = realpath($baseDir . DIRECTORY_SEPARATOR . 'no-image.png');
  if ($noImgReal && strpos($noImgReal, $baseDir) === 0 && is_file($noImgReal)) {
    $currentImageSrc = '../product_images/no-image.png';
  } else {
    $svg = '
      <svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
        <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#e5e7eb"/><stop offset="100%" stop-color="#f1f5f9"/>
        </linearGradient></defs>
        <rect width="120" height="120" rx="16" fill="url(#g)"/>
        <g fill="none" stroke="#94a3b8" stroke-width="3">
          <rect x="20" y="28" width="80" height="64" rx="10"/>
          <circle cx="44" cy="56" r="10"/>
          <path d="M32 92l22-22 16 16 12-12 18 18"/>
        </g>
      </svg>';
    $currentImageSrc = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
  }
}


?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขสินค้า</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  :root{ --bg:#f6f7fb; --ink:#0f172a; --muted:#64748b; --card:#ffffffcc; --stroke:#e6e8ef; --brand:#5b8cff; --brand2:#8b5bff; }
  html,body{height:100%}
  body{ margin:0; background:var(--bg); color:var(--ink);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Thai", "Prompt", Arial, sans-serif; }
  .bg-abstract{ position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(1200px 600px at -10% -10%, #e0e7ff 0%, transparent 65%),
      radial-gradient(900px 500px at 110% 10%, #ffe4e6 0%, transparent 60%),
      radial-gradient(900px 500px at 20% 110%, #dcfce7 0%, transparent 60%); }
  .bg-abstract::after{ content:""; position:absolute; inset:0;
    background: conic-gradient(from 210deg at 40% 30%, #9cc1ff33, #b7a1ff33, #ffb1d433, #9cc1ff33 75%);
    filter: blur(28px); opacity:.6; }
  .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }
  .card-glass{ width:min(900px,96vw); background:var(--card); backdrop-filter: blur(12px);
    border:1px solid var(--stroke); border-radius:24px; box-shadow:0 12px 36px rgba(15,23,42,.12); overflow:hidden; }
  .head{ padding:22px 22px 12px; border-bottom:1px solid rgba(15,23,42,.06);
    display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
  .title{ margin:0; font-weight:900; letter-spacing:.2px;
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    -webkit-background-clip:text; background-clip:text; color:transparent;
    font-size: clamp(1.2rem, .9rem + 1.2vw, 1.8rem); }
  .body{ padding:22px; }
  .block{ background:#fff; border:1px solid #e8eaf3; border-radius:16px; box-shadow:0 10px 24px rgba(15,23,42,.06); padding:18px; }
  .btn-ghost{ border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155; }
  .btn-brand{ background:linear-gradient(135deg,var(--brand),var(--brand2)); border:none; color:#fff; font-weight:800;
              box-shadow:0 10px 24px rgba(91,140,255,.25); }
  .form-label{ font-weight:700; color:#1f2937; }
  .form-control,.form-select{ background:#fff; color:#0f172a; border:1px solid #dfe3f3; }
  .form-control::placeholder{ color:#9aa3b8; }
  .form-control:focus,.form-select:focus{ border-color:#b9c7ff; box-shadow:0 0 0 .25rem rgba(91,140,255,.18); }
  .grid{ display:grid; gap:14px; grid-template-columns: repeat(2,1fr); }
  @media (max-width: 720px){ .grid{ grid-template-columns: 1fr; } }
  .mb-18{ margin-bottom:18px; }
  .thumb{ width:120px; height:120px; object-fit:cover; border-radius:12px; background:#f1f5f9; border:1px solid #e5e7eb; }

</style>
</head>
<body>
  <div class="bg-abstract"></div>

  <div class="page">
    <div class="card-glass">
      <div class="head">
        <h1 class="title">แก้ไขสินค้า</h1>
        <a href="products.php" class="btn btn-ghost">← กลับไปยังรายการสินค้า</a>
      </div>

      <div class="body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger mb-18"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="block">
          <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="grid">
              <div>
                <label class="form-label">ชื่อสินค้า</label>
                <input type="text" name="product_name" class="form-control"
                       value="<?= htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div>
                <label class="form-label">ราคา (บาท)</label>
                <input type="number" step="0.01" min="0" name="price" class="form-control"
                       value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div>
                <label class="form-label">จำนวนในคลัง</label>
                <input type="number" min="0" name="stock" class="form-control"
                       value="<?= htmlspecialchars((string)$product['stock'], ENT_QUOTES, 'UTF-8') ?>" required>
              </div>

              <div>
                <label class="form-label">หมวดหมู่</label>
                <select name="category_id" class="form-select" required>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>"
                      <?= ((int)$product['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cat['category_name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mt-2">
              <label class="form-label">รายละเอียดสินค้า</label>
              <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!--TODO ===div แสดงรูปเดิม + เก็บค่ำเก่ำ -->
            <div class="col-md-6">
              <label class="form-label d-block">รูปปัจจุบัน</label>
              <img src="<?= $currentImageSrc ?>" width="120" height="120" class="thumb mb-2" alt="รูปสินค้า">
              <input type="hidden" name="old_image" value="<?= htmlspecialchars($product['image']) ?>">
            </div>


            <!--TODO === อัปโหลดรูปใหม่ (ทำงเลือก) -->
            <div class="col-md-6">
              <label class="form-label">อัปโหลดรูปใหม่ (jpg, png)</label>
                <input type="file" name="product_image" class="form-control">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image"value="1">
                <label class="form-check-label" for="remove_image">ลบรูปเดิม</label>
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button type="submit" class="btn btn-brand">บันทึกการแก้ไข</button>
              <a href="products.php" class="btn btn-ghost">ยกเลิก</a>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
