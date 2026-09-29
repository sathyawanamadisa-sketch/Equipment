<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$search = trim($_GET['search'] ?? '');

// ---- Currently in workshop ----
$pendingSql = "SELECT w.*, e.asset_number, e.item_type, e.brand_model
               FROM workshop w
               JOIN equipment e ON w.equipment_id = e.id
               WHERE w.status = 'In Workshop'";
$pendingParams = [];

if ($search !== '') {
    $pendingSql .= " AND (e.asset_number LIKE ? OR e.item_type LIKE ? OR e.brand_model LIKE ? OR w.workshop_job_number LIKE ?)";
    $like = "%$search%";
    array_push($pendingParams, $like, $like, $like, $like);
}

$pendingSql .= " ORDER BY w.admit_date DESC, w.id DESC";

$stmt = $pdo->prepare($pendingSql);
$stmt->execute($pendingParams);
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Repair history ----
$historySql = "SELECT w.*, e.asset_number, e.item_type, e.brand_model
               FROM workshop w
               JOIN equipment e ON w.equipment_id = e.id
               WHERE w.status = 'Repaired'";
$historyParams = [];

if ($search !== '') {
    $historySql .= " AND (e.asset_number LIKE ? OR e.item_type LIKE ? OR e.brand_model LIKE ? OR w.workshop_job_number LIKE ?)";
    array_push($historyParams, $like, $like, $like, $like);
}

$historySql .= " ORDER BY w.return_date DESC, w.id DESC";

$stmt2 = $pdo->prepare($historySql);
$stmt2->execute($historyParams);
$history = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop (G7)</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            padding: 25px 30px;
        }

        .container { width: 100%; max-width: none; margin: 0; }

        .top-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin-bottom: 25px;
        }

        h1 { color: white; margin-bottom: 6px; text-align: center; }

        .subtitle {
            color: #cbd5e1;
            text-align: center;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        form.search-form { display: flex; gap: 8px; flex: 1; min-width: 250px; }

        input[type="text"] {
            flex: 1;
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 14px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }

        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-add { background: #16a34a; color: white; white-space: nowrap; }
        .btn-add:hover { background: #15803d; }

        .btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); white-space: nowrap; }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        .btn-return { background: #16a34a; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-return:hover { background: #15803d; }

        .btn-delete { background: #dc2626; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-delete:hover { background: #b91c1c; }

        .message {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid #22c55e;
            color: #bbf7d0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .section-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .section-heading h2 { color: white; font-size: 18px; }

        .count-pill {
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            margin-bottom: 25px;
        }

        table { width: 100%; border-collapse: collapse; color: white; }

        th, td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 14px;
        }

        th { background: rgba(255, 255, 255, 0.05); font-weight: bold; font-size: 12px; text-transform: uppercase; color: #cbd5e1; }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(255, 255, 255, 0.04); }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-workshop { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .status-repaired { background: rgba(34, 197, 94, 0.2); color: #86efac; }

        .action-cell { display: flex; gap: 6px; }

        .empty {
            color: #cbd5e1;
            text-align: center;
            padding: 25px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 25px;
        }

        @media (max-width: 700px) {
            body { padding: 15px 10px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tbody tr { padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,0.08); }
            td { border: none; padding: 5px 10px; }
            td::before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                width: 130px;
                color: #93c5fd;
            }
        }
    </style>
</head>
<body>

    <div class="container">

        <div class="top-panel">
            <h1>Workshop (G7)</h1>
            <p class="subtitle">Equipment sent for repair</p>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="top-bar">
                <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
                <form class="search-form" method="GET" action="">
                    <input type="text" name="search" placeholder="Search by serial no, item type, job number..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <a href="add_workshop.php" class="btn btn-add">+ Send to Workshop</a>
            </div>
        </div>

        <div class="section-heading">
            <h2>In Workshop</h2>
            <span class="count-pill"><?php echo count($pending); ?> item(s)</span>
        </div>

        <?php if (count($pending) === 0): ?>
            <div class="empty">No equipment currently in workshop.</div>
        <?php else: ?>
        <div class="box">
            <table>
                <thead>
                    <tr>
                        <th>Item Type</th>
                        <th>Brand / Model</th>
                        <th>Serial No</th>
                        <th>Admit Date</th>
                        <th>Job No</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $w): ?>
                    <tr>
                        <td data-label="Item Type"><?php echo htmlspecialchars($w['item_type']); ?></td>
                        <td data-label="Brand / Model"><?php echo htmlspecialchars($w['brand_model']); ?></td>
                        <td data-label="Serial No"><?php echo htmlspecialchars($w['asset_number']); ?></td>
                        <td data-label="Admit Date"><?php echo htmlspecialchars($w['admit_date']); ?></td>
                        <td data-label="Job No"><?php echo htmlspecialchars($w['workshop_job_number']); ?></td>
                        <td data-label="Status"><span class="status-badge status-workshop">In Workshop</span></td>
                        <td data-label="Action">
                            <div class="action-cell">
                                <a href="workshop_return.php?id=<?php echo $w['id']; ?>" class="btn btn-return" onclick="return confirm('Mark this item as repaired and returned to store?');">Repaired</a>
                                <a href="delete_workshop.php?id=<?php echo $w['id']; ?>" class="btn btn-delete" onclick="return confirm('Remove this workshop record completely?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="section-heading">
            <h2>Repair History</h2>
            <span class="count-pill"><?php echo count($history); ?> item(s)</span>
        </div>

        <?php if (count($history) === 0): ?>
            <div class="empty">No repair history yet.</div>
        <?php else: ?>
        <div class="box">
            <table>
                <thead>
                    <tr>
                        <th>Item Type</th>
                        <th>Brand / Model</th>
                        <th>Serial No</th>
                        <th>Admit Date</th>
                        <th>Job No</th>
                        <th>Returned Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $w): ?>
                    <tr>
                        <td data-label="Item Type"><?php echo htmlspecialchars($w['item_type']); ?></td>
                        <td data-label="Brand / Model"><?php echo htmlspecialchars($w['brand_model']); ?></td>
                        <td data-label="Serial No"><?php echo htmlspecialchars($w['asset_number']); ?></td>
                        <td data-label="Admit Date"><?php echo htmlspecialchars($w['admit_date']); ?></td>
                        <td data-label="Job No"><?php echo htmlspecialchars($w['workshop_job_number']); ?></td>
                        <td data-label="Returned Date"><?php echo htmlspecialchars($w['return_date']); ?></td>
                        <td data-label="Status"><span class="status-badge status-repaired">Repaired</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
