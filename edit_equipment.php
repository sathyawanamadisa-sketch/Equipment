<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: equipment.php");
    exit();
}

// Fetch existing equipment
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: equipment.php?msg=Equipment not found");
    exit();
}

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
        // Check asset number not used by another item
        $check = $pdo->prepare("SELECT id FROM equipment WHERE asset_number = ? AND id != ?");
        $check->execute([$asset_number, $id]);

        if ($check->fetch()) {
            $error = "This Serial Number is already used by another item.";
        } else {
            $update = $pdo->prepare("UPDATE equipment SET item_name = ?, asset_number = ?, item_type = ?, brand_model = ?, status = ?, section_division = ?, date_added = ? WHERE id = ?");
            $update->execute([$item_name, $asset_number, $item_type, $brand_model, $status, $section_division, $date_added, $id]);

            header("Location: equipment.php?msg=Equipment updated successfully");
            exit();
        }
    }

    // Keep entered values on error
    $item['item_name'] = $item_name;
    $item['asset_number'] = $asset_number;
    $item['item_type'] = $item_type;
    $item['brand_model'] = $brand_model;
    $item['status'] = $status;
    $item['section_division'] = $section_division;
    $item['date_added'] = $date_added;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment</title>
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
        <h1>Edit Equipment</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">

            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" placeholder="e.g. Office CPU 1" value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="asset_number">Serial Number</label>
                <input type="text" id="asset_number" name="asset_number" value="<?php echo htmlspecialchars($item['asset_number']); ?>" required>
            </div>

            <div class="form-group">
                <label for="item_type">Item Type</label>
                <select id="item_type" name="item_type" required>
                    <option value="">-- Select Item Type --</option>
                    <?php foreach ($item_types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($item['item_type'] === $t) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand_model">Brand / Model</label>
                <input type="text" id="brand_model" name="brand_model" value="<?php echo htmlspecialchars($item['brand_model']); ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="Available" <?php echo ($item['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                    <option value="Issued" <?php echo ($item['status'] === 'Issued') ? 'selected' : ''; ?>>Issued</option>
                    <option value="Workshop" <?php echo ($item['status'] === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                </select>
            </div>

            <div class="form-group">
                <label for="section_division">Section / Division</label>
                <input type="text" id="section_division" name="section_division" value="<?php echo htmlspecialchars($item['section_division']); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_added">Date Added</label>
                <input type="date" id="date_added" name="date_added" value="<?php echo htmlspecialchars($item['date_added']); ?>" required>
            </div>

            <div class="btn-row">
                <a href="equipment.php" class="btn-cancel">Cancel</a>
                <button type="submit">Update</button>
            </div>

        </form>
    </div>

</body>
</html>
