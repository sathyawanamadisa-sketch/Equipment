<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item_name = trim($_POST['item_name'] ?? '');
    $asset_number = trim($_POST['asset_number'] ?? '');
    $item_type = trim($_POST['item_type'] ?? '');
    $brand_model = trim($_POST['brand_model'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $section_division = trim($_POST['section_division'] ?? '');
    $date_added = trim($_POST['date_added'] ?? '');

    if ($item_name === '' || $asset_number === '' || $item_type === '' || $brand_model === '' || $status === '' || $section_division === '' || $date_added === '') {
        $error = "Please fill in all fields.";
    } elseif (!in_array($item_type, $item_types)) {
        $error = "Invalid item type selected.";
    } elseif (!in_array($status, ['Available', 'Issued', 'Workshop'])) {
        $error = "Invalid status selected.";
    } else {
        // Check if asset number already exists
        $check = $pdo->prepare("SELECT id FROM equipment WHERE asset_number = ?");
        $check->execute([$asset_number]);

        if ($check->fetch()) {
            $error = "This Serial Number already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO equipment (item_name, asset_number, item_type, brand_model, status, section_division, date_added) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$item_name, $asset_number, $item_type, $brand_model, $status, $section_division, $date_added]);

            header("Location: equipment.php?msg=Equipment added successfully");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment</title>
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
        <h1>Add Equipment</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" placeholder="e.g. Office CPU 1" value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="asset_number">Serial Number</label>
                <input type="text" id="asset_number" name="asset_number" value="<?php echo htmlspecialchars($_POST['asset_number'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="item_type">Item Type</label>
                <select id="item_type" name="item_type" required>
                    <option value="">-- Select Item Type --</option>
                    <?php foreach ($item_types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo (($_POST['item_type'] ?? '') === $t) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand_model">Brand / Model</label>
                <input type="text" id="brand_model" name="brand_model" value="<?php echo htmlspecialchars($_POST['brand_model'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="">-- Select Status --</option>
                    <option value="Available" <?php echo (($_POST['status'] ?? '') === 'Available') ? 'selected' : ''; ?>>Available</option>
                    <option value="Issued" <?php echo (($_POST['status'] ?? '') === 'Issued') ? 'selected' : ''; ?>>Issued</option>
                    <option value="Workshop" <?php echo (($_POST['status'] ?? '') === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                </select>
            </div>

            <div class="form-group">
                <label for="section_division">Section / Division</label>
                <input type="text" id="section_division" name="section_division" value="<?php echo htmlspecialchars($_POST['section_division'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_added">Date Added</label>
                <input type="date" id="date_added" name="date_added" value="<?php echo htmlspecialchars($_POST['date_added'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="btn-row">
                <a href="equipment.php" class="btn-cancel">Cancel</a>
                <button type="submit">Save</button>
            </div>

        </form>
    </div>

</body>
</html>
