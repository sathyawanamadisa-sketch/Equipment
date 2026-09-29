<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM officers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=Officer deleted successfully");
    exit();
}

header("Location: index.php");
exit();
