<?php

require_once 'config.php';

$username = "admin";
$newPassword = "admin123";

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "UPDATE users SET password = ? WHERE username = ?"
);

$stmt->execute([
    $hashedPassword,
    $username
]);

echo "Password reset successfully!";

?>