<?php

require_once 'config.php';

$username = "admin";
$password = "admin123";
$full_name = "Administrator";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    INSERT INTO users (username, password, full_name)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $username,
    $hashedPassword,
    $full_name
]);

echo "Admin account created successfully!";

?>