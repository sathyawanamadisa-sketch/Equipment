<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$error = "";

// Only equipment currently Available can be sent to workshop
$available_equipment = $pdo->query("SELECT * FROM equipment WHERE status = 'Available' ORDER BY item_type, asset_number")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    $admit_date = trim($_POST['admit_date'] ?? '');
    $workshop_job_number = trim($_POST['workshop_job_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($equipment_id <= 0 || $admit_date === '' || $workshop_job_number === '') {
        $error = "Please select equipment, an admit date, and enter a workshop job number.";
    } else {
        $check = $pdo->prepare("SELECT status FROM equipment WHERE id = ?");
        $check->execute([$equipment_id]);
        $eq = $check->fetch(PDO::FETCH_ASSOC);

        if (!$eq || $eq['status'] !== 'Available') {
            $error = "This equipment is not available to send to workshop.";
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO workshop (equipment_id, admit_date, workshop_job_number, notes, status) VALUES (?, ?, ?, ?, 'In Workshop')");
                $stmt->execute([$equipment_id, $admit_date, $workshop_job_number, $notes]);

                $update = $pdo->prepare("UPDATE equipment SET status = 'Workshop' WHERE id = ?");
                $update->execute([$equipment_id]);

                $pdo->commit();

                header("Location: workshop.php?msg=" . urlencode("Equipment sent to workshop successfully"));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send to Workshop</title>
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
            width: 460px;
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

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border: none;
            border-radius: 10px;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        textarea { resize: vertical; min-height: 60px; }

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
        button:disabled { background: #64748b; cursor: not-allowed; }

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

        .no-stock {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid #f59e0b;
            color: #fcd34d;
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
        <h1>Send to Workshop (G7)</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($available_equipment) === 0): ?>
            <div class="no-stock">No available equipment to send to workshop right now.</div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="equipment_id">Equipment (Available only)</label>
                <select id="equipment_id" name="equipment_id" required <?php echo count($available_equipment) === 0 ? 'disabled' : ''; ?>>
                    <option value="">-- Select Equipment --</option>
                    <?php foreach ($available_equipment as $eq): ?>
                        <option value="<?php echo $eq['id']; ?>" data-serial="<?php echo htmlspecialchars($eq['asset_number']); ?>" <?php echo (($_POST['equipment_id'] ?? '') == $eq['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($eq['item_type'] . ' - ' . $eq['brand_model'] . ' (' . $eq['asset_number'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="serial_number_display">Serial Number</label>
                <input type="text" id="serial_number_display" readonly placeholder="Auto-filled after selecting equipment" style="background: rgba(255,255,255,0.7); cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label for="workshop_job_number">Workshop Job Number</label>
                <input type="text" id="workshop_job_number" name="workshop_job_number" placeholder="e.g. WJ-2026-045" value="<?php echo htmlspecialchars($_POST['workshop_job_number'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="admit_date">Admit Date</label>
                <input type="date" id="admit_date" name="admit_date" value="<?php echo htmlspecialchars($_POST['admit_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="notes">Notes / Fault Description (optional)</label>
                <textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <div class="btn-row">
                <a href="workshop.php" class="btn-cancel">Cancel</a>
                <button type="submit" <?php echo count($available_equipment) === 0 ? 'disabled' : ''; ?>>Send to Workshop</button>
            </div>

        </form>
    </div>

    <script>
        // Auto-fill the read-only Serial Number field based on the selected equipment
        const equipmentSelect = document.getElementById('equipment_id');
        const serialDisplay = document.getElementById('serial_number_display');

        function updateSerial() {
            const selected = equipmentSelect.options[equipmentSelect.selectedIndex];
            serialDisplay.value = selected ? (selected.getAttribute('data-serial') || '') : '';
        }

        if (equipmentSelect) {
            equipmentSelect.addEventListener('change', updateSerial);
            updateSerial(); // run once in case a value is pre-selected (e.g. after a validation error)
        }
    </script>

</body>
</html>
