<?php
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
header("Location: ../login.php");
exit;
}
?>