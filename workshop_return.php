<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM workshop WHERE id = ? AND status = 'In Workshop'");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare("UPDATE workshop SET status = 'Repaired', return_date = ? WHERE id = ?");
            $update->execute([date('Y-m-d'), $id]);

            $updateEq = $pdo->prepare("UPDATE equipment SET status = 'Available' WHERE id = ?");
            $updateEq->execute([$record['equipment_id']]);

            $pdo->commit();
            header("Location: workshop.php?msg=" . urlencode("Equipment marked as repaired and returned to store"));
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: workshop.php?msg=" . urlencode("Something went wrong, please try again"));
            exit();
        }
    }
}

header("Location: workshop.php");
exit();
