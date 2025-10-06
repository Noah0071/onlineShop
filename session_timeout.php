
<?php
// session_timeout.php

// เริ่มต้น session (ถ้ายังไม่เริ่ม)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ระยะเวลาที่ต้องการให้ session มีอายุ (วินาที) เช่น 30 นาที
$timeout = 30 * 60;
// $timeout = 1 * 60; // 1 นาที

// ถ้าเคยบันทึกเวลาไว้แล้ว ให้ตรวจสอบว่าหมดอายุหรือยัง
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    // ถ้าเกินกำหนด -> ล้างและทำลาย session
    session_unset();
    session_destroy();

    // Redirect ไปหน้า login
    header("Location: login.php?timeout=1");
    exit;
}

// บันทึกเวลาล่าสุดของการทำงาน (รีเฟรชทุกครั้งที่ผู้ใช้มีการเคลื่อนไหว)
$_SESSION['LAST_ACTIVITY'] = time();

?>
