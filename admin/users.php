<?php
require_once '../config.php';
require_once 'auth_admin.php';

// ลบสมาชิก (เฉพาะ role = member) และกันลบตัวเอง
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'member'");
        $stmt->execute([$user_id]);
    }
    header("Location: users.php");
    exit;
}

// ดึงข้อมูลสมาชิก
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  :root{
    --bg: #f6f7fb;
    --ink: #0f172a;
    --muted: #64748b;
    --card: #ffffffcc;    /* glass */
    --stroke: #e6e8ef;
    --brand: #5b8cff;
    --brand2:#8b5bff;
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

  .page{ min-height:100svh; display:grid; place-items:start center; padding:24px; }

  /* การ์ดครอบหน้าแบบ Glassmorphism */
  .card-glass{
    width:min(1100px, 96vw);
    background: var(--card);
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

  /* ปุ่มให้โทนเดียวกับชุดใหม่ */
  .btn-brand{
    background: linear-gradient(135deg, var(--brand), var(--brand2));
    border:none; color:#fff; font-weight:800;
    box-shadow:0 10px 24px rgba(91,140,255,.25);
  }
  .btn-ghost{
    border:1px solid #dfe3f3; background:#fff; font-weight:700; color:#334155;
  }

  /* กล่องบล็อก */
  .block{
    background:#fff;
    border:1px solid #e8eaf3;
    border-radius:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.06);
    padding:18px;
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
      <h1 class="title">จัดการสมาชิก</h1>
      <a href="index.php" class="btn btn-ghost">← กลับหน้าผู้ดูแล</a>
    </div>

    <div class="body">
      <?php if (count($users) === 0): ?>
        <div class="block">
          <div class="alert alert-warning mb-0">ยังไม่มีสมาชิกในระบบ</div>
        </div>
      <?php else: ?>
        <div class="block">
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>ชื่อผู้ใช้</th>
                  <th>ชื่อ-นามสกุล</th>
                  <th>อีเมล</th>
                  <th>วันที่สมัคร</th>
                  <th>จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['created_at'] ?></td>
                    <td>
                      <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>

                      <!-- ปุ่มลบ (ใช้ SweetAlert2) -->
                      <form action="deluser_sweet.php" method="POST" style="display:inline;">
                        <input type="hidden" name="u_id" value="<?php echo $user['user_id']; ?>">
                        <button type="button" class="delete-button btn btn-danger btn-sm" data-user-id="<?php echo $user['user_id']; ?>">ลบ</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// ฟังก์ชันยืนยันการลบด้วย SweetAlert2 (คงตรรกะเดิม)
function showDeleteConfirmation(userId) {
  Swal.fire({
    title: 'คุณแน่ใจหรือไม่?',
    text: 'คุณไม่สามารถเรียกคืนข้อมูลได้!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
  }).then((result) => {
    if (result.isConfirmed) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'deluser_sweet.php';
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'u_id';
      input.value = userId;
      form.appendChild(input);
      document.body.appendChild(form);
      form.submit();
    }
  });
}

document.querySelectorAll('.delete-button').forEach((button) => {
  button.addEventListener('click', () => {
    const userId = button.getAttribute('data-user-id');
    showDeleteConfirmation(userId);
  });
});
</script>
</body>
</html>
