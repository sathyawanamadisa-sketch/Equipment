<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$error = "";

// Only equipment currently Available can be issued
$available_equipment = $pdo->query("SELECT * FROM equipment WHERE status = 'Available' ORDER BY item_type, asset_number")->fetchAll(PDO::FETCH_ASSOC);
$all_officers = $pdo->query("SELECT * FROM officers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $officer_id = (int)($_POST['officer_id'] ?? 0);
    $issue_date = trim($_POST['issue_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $equipment_ids = $_POST['equipment_ids'] ?? []; // array of checked ids

    // Sanitize to integers
    $equipment_ids = array_map('intval', $equipment_ids);
    $equipment_ids = array_filter($equipment_ids, fn($v) => $v > 0);

    if ($officer_id <= 0 || $issue_date === '' || count($equipment_ids) === 0) {
        $error = "Please select an officer, at least one equipment item, and an issue date.";
    } else {
        $pdo->beginTransaction();
        try {
            // Re-check every selected item is still Available (avoid double-issue race)
            $placeholders = implode(',', array_fill(0, count($equipment_ids), '?'));
            $check = $pdo->prepare("SELECT id, status FROM equipment WHERE id IN ($placeholders) FOR UPDATE");
            $check->execute($equipment_ids);
            $rows = $check->fetchAll(PDO::FETCH_ASSOC);

            $unavailable = array_filter($rows, fn($r) => $r['status'] !== 'Available');

            if (count($rows) !== count($equipment_ids) || count($unavailable) > 0) {
                $pdo->rollBack();
                $error = "One or more selected items are no longer available. Please review and try again.";
            } else {
                $insert = $pdo->prepare("INSERT INTO issues (equipment_id, officer_id, issue_date, notes, status) VALUES (?, ?, ?, ?, 'Issued')");
                $updateEq = $pdo->prepare("UPDATE equipment SET status = 'Issued' WHERE id = ?");

                foreach ($equipment_ids as $eid) {
                    $insert->execute([$eid, $officer_id, $issue_date, $notes]);
                    $updateEq->execute([$eid]);
                }

                $pdo->commit();

                $count = count($equipment_ids);
                header("Location: issue.php?msg=" . urlencode("$count item(s) issued successfully"));
                exit();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Equipment</title>
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
            width: 520px;
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

        .checklist {
            max-height: 220px;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 8px;
            
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: black;    
        }

        .checklist-item:hover { background: rgba(37, 99, 235, 0.08); }

        .checklist-item input[type="checkbox"] {
            width: auto;
            accent-color: #2563eb;
        }

        .checklist-empty {
            color: #64748b;
            font-size: 14px;
            padding: 10px;
            text-align: center;
        }

        .selected-count {
            color: #93c5fd;
            font-size: 12px;
            margin-top: 6px;
        }

        .btn-row { display: flex; gap: 10px; margin-top: 20px; }

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
        <h1>Issue Equipment</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($available_equipment) === 0): ?>
            <div class="no-stock">No available equipment to issue right now.</div>
        <?php endif; ?>

        <form method="POST" action="" id="issueForm">

            <div class="form-group">
                <label for="officer_id">Officer</label>
                <select id="officer_id" name="officer_id" required>
                    <option value="">-- Select Officer --</option>
                    <?php foreach ($all_officers as $o): ?>
                        <option value="<?php echo $o['id']; ?>" <?php echo (($_POST['officer_id'] ?? '') == $o['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($o['rank'] . ' ' . $o['name'] . ' (' . $o['service_number'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Equipment (select one or more — Available only)</label>
                <div class="checklist">
                    <?php if (count($available_equipment) === 0): ?>
                        <div class="checklist-empty">No available items.</div>
                    <?php else: ?>
                        <?php
                        $posted_ids = array_map('intval', $_POST['equipment_ids'] ?? []);
                        foreach ($available_equipment as $eq):
                        ?>
                        <label class="checklist-item">
                            <input type="checkbox" name="equipment_ids[]" value="<?php echo $eq['id']; ?>" class="eq-checkbox" <?php echo in_array((int)$eq['id'], $posted_ids) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($eq['item_type'] . ' - ' . $eq['brand_model'] . ' (' . $eq['asset_number'] . ')'); ?></span>
                        </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="selected-count" id="selectedCount">0 item(s) selected</div>
            </div>

            <div class="form-group">
                <label for="issue_date">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($_POST['issue_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="notes">Notes (optional)</label>
                <textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <div class="btn-row">
                <a href="issue.php" class="btn-cancel">Cancel</a>
                <button type="submit" id="submitBtn" <?php echo count($available_equipment) === 0 ? 'disabled' : ''; ?>>Issue Selected Items</button>
            </div>

        </form>
    </div>

    <script>
        // Update the "N item(s) selected" counter live as checkboxes are toggled
        const checkboxes = document.querySelectorAll('.eq-checkbox');
        const counter = document.getElementById('selectedCount');

        function updateCount() {
            const checked = document.querySelectorAll('.eq-checkbox:checked').length;
            counter.textContent = checked + ' item(s) selected';
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
        updateCount();
    </script>

</body>
</html>
