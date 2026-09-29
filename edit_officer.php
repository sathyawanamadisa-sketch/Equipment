<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch existing officer
$stmt = $pdo->prepare("SELECT * FROM officers WHERE id = ?");
$stmt->execute([$id]);
$officer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$officer) {
    header("Location: index.php?msg=Officer not found");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_number = trim($_POST['service_number'] ?? '');
    $rank = trim($_POST['rank'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $section_division = trim($_POST['section_division'] ?? '');

    if ($service_number === '' || $rank === '' || $name === '' || $phone_number === '' || $section_division === '') {
        $error = "Please fill in all fields.";
    } elseif (!in_array($rank, $ranks)) {
        $error = "Invalid rank selected.";
    } else {
        // Check service number not used by another officer
        $check = $pdo->prepare("SELECT id FROM officers WHERE service_number = ? AND id != ?");
        $check->execute([$service_number, $id]);

        if ($check->fetch()) {
            $error = "This Service Number is already used by another officer.";
        } else {
            $update = $pdo->prepare("UPDATE officers SET service_number = ?, rank = ?, name = ?, phone_number = ?, section_division = ? WHERE id = ?");
            $update->execute([$service_number, $rank, $name, $phone_number, $section_division, $id]);

            header("Location: index.php?msg=Officer updated successfully");
            exit();
        }
    }

    // Keep entered values on error
    $officer['service_number'] = $service_number;
    $officer['rank'] = $rank;
    $officer['name'] = $name;
    $officer['phone_number'] = $phone_number;
    $officer['section_division'] = $section_division;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Officer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            padding: 20px;
        }

        .box {
            width: 450px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        h1 { color: white; text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; }

        label { display: block; color: white; margin-bottom: 6px; font-size: 14px; }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: none;
            border-radius: 10px;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 14px;
        }

        .btn-row { display: flex; gap: 10px; margin-top: 10px; }

        button, .btn-cancel {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: 0.2s;
        }

        button { background: #2563eb; color: white; }
        button:hover { background: #1d4ed8; }

        .btn-cancel { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .btn-cancel:hover { background: rgba(255,255,255,0.25); }

        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fecaca;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="box">
        <h1>Edit Officer</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo (int)$officer['id']; ?>">

            <div class="form-group">
                <label for="service_number">Service Number</label>
                <input type="text" id="service_number" name="service_number" value="<?php echo htmlspecialchars($officer['service_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="rank">Rank</label>
                <select id="rank" name="rank" required>
                    <option value="">-- Select Rank --</option>
                    <?php foreach ($ranks as $r): ?>
                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($officer['rank'] === $r) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($officer['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($officer['phone_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="section_division">Section / Division</label>
                <input type="text" id="section_division" name="section_division" value="<?php echo htmlspecialchars($officer['section_division']); ?>" required>
            </div>

            <div class="btn-row">
                <a href="index.php" class="btn-cancel">Cancel</a>
                <button type="submit">Update</button>
            </div>

        </form>
    </div>

</body>
</html>
