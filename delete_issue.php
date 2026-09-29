<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);
$redirect_to = $_GET['from'] ?? 'issue.php';
$allowed_pages = ['issue.php', 'officer_profile.php'];
$redirect_base = explode('?', $redirect_to)[0];
if (!in_array($redirect_base, $allowed_pages)) {
    $redirect_to = 'issue.php';
}

function redirect_with_msg($base, $msg) {
    $sep = strpos($base, '?') !== false ? '&' : '?';
    header("Location: $base{$sep}msg=" . urlencode($msg));
    exit();
}

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM issues WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        $pdo->beginTransaction();
        try {
            // If the item was still issued, free it back up as Available
            if ($record['status'] === 'Issued') {
                $updateEq = $pdo->prepare("UPDATE equipment SET status = 'Available' WHERE id = ?");
                $updateEq->execute([$record['equipment_id']]);
            }

            $delete = $pdo->prepare("DELETE FROM issues WHERE id = ?");
            $delete->execute([$id]);

            $pdo->commit();
            redirect_with_msg($redirect_to, "Record removed successfully");
        } catch (Exception $e) {
            $pdo->rollBack();
            redirect_with_msg($redirect_to, "Something went wrong, please try again");
        }
    }
}

header("Location: issue.php");
exit();
