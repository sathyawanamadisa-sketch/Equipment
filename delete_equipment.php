<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: equipment.php?msg=Equipment deleted successfully");
    exit();
}

header("Location: equipment.php");
exit();
