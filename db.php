<?php
// ---- Database connection ----
// ඔබේ actual database name/username/password දාන්න
$host = "localhost";
$dbname = "equipment_system";   // ඔබේ database name එකට වෙනස් කරන්න
$dbuser = "root";
$dbpass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Rank dropdown options - සියලුම pages වලින් මේකම පාවිච්චි කරනවා
$ranks = ["Sgm", "Sgw", "L/Cpl", "Cpl", "Sgt", "S/Sgt", "WO II", "WO I", "2/Lt", "Lt", "Capt", "Major", "Lt Col", "Col", "Brig"];

// Equipment item type dropdown options - මෙතනින් items add/remove කරන්න
$item_types = [
    "Screw",
    "Converter",
    "Headset",
    "CD",
    "VGA Cards",
    "Web Camera",
    "Bag",
    "HDMI To Display Cable",
    "Laptop",
    "UPS",
    "CPU",
    "Mouse",
    "Keyboard",
    "Monitor",
    "Router",
    "Laptop Charger",
    "VGA Cable",
    "Power cable",
    "External DVD Writer",
    "Monitor Switching Adepter",
    "Power Panel",
    "USB Hub",
    "Mounting Bracket",
    "External HDD Disk",
    "Internet Router",
    "IP CAMERA",
    "Mouse Pad",
];
?>
