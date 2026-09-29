<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM officers WHERE id = ?");
$stmt->execute([$id]);
$officer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$officer) {
    header("Location: index.php?msg=Officer not found");
    exit();
}

// Currently issued equipment
$issuedStmt = $pdo->prepare("SELECT i.*, e.asset_number, e.item_type, e.brand_model
                              FROM issues i
                              JOIN equipment e ON i.equipment_id = e.id
                              WHERE i.officer_id = ? AND i.status = 'Issued'
                              ORDER BY i.issue_date DESC");
$issuedStmt->execute([$id]);
$currentlyIssued = $issuedStmt->fetchAll(PDO::FETCH_ASSOC);

// Return history
$historyStmt = $pdo->prepare("SELECT i.*, e.asset_number, e.item_type, e.brand_model
                               FROM issues i
                               JOIN equipment e ON i.equipment_id = e.id
                               WHERE i.officer_id = ? AND i.status = 'Returned'
                               ORDER BY i.return_date DESC");
$historyStmt->execute([$id]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Profile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            padding: 30px 15px;
        }

        .container {
            max-width: 950px;
            margin: 0 auto;
        }

        .top-bar { margin-bottom: 20px; }

        .btn-back {
            padding: 10px 18px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        .profile-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-details h1 {
            color: white;
            font-size: 24px;
            margin-bottom: 4px;
        }

        .profile-details .rank-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.3);
            color: #93c5fd;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .profile-meta {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .profile-meta div {
            color: #cbd5e1;
            font-size: 13px;
        }

        .profile-meta strong {
            display: block;
            color: white;
            font-size: 14px;
        }

        .section-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 25px;
            margin-bottom: 25px;
        }

        .section-box h2 {
            color: white;
            font-size: 17px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 11px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }

        th { background: rgba(255, 255, 255, 0.1); font-weight: bold; }

        tr:hover { background: rgba(255, 255, 255, 0.05); }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-Issued { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .status-Returned { background: rgba(34, 197, 94, 0.2); color: #86efac; }

        .btn-return {
            background: #f59e0b;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-return:hover { background: #d97706; }

        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-delete:hover { background: #b91c1c; }

        .empty {
            color: #cbd5e1;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr {
                margin-bottom: 12px;
                background: rgba(255,255,255,0.05);
                border-radius: 10px;
                padding: 10px;
            }
            td { border: none; padding: 6px 10px; }
            td::before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                width: 120px;
                color: #93c5fd;
            }
        }
    </style>
</head>
<body>

    <div class="container">

        <div class="top-bar">
            <a href="index.php" class="btn-back">&larr; Officer Directory</a>
        </div>

        <div class="profile-card">
            <div class="avatar"><?php echo strtoupper(substr($officer['name'], 0, 1)); ?></div>
            <div class="profile-details">
                <span class="rank-badge"><?php echo htmlspecialchars($officer['rank']); ?></span>
                <h1><?php echo htmlspecialchars($officer['name']); ?></h1>

                <div class="profile-meta">
                    <div><strong><?php echo htmlspecialchars($officer['service_number']); ?></strong>Service Number</div>
                    <div><strong><?php echo htmlspecialchars($officer['phone_number']); ?></strong>Phone Number</div>
                    <div><strong><?php echo htmlspecialchars($officer['section_division']); ?></strong>Section / Division</div>
                    <div><strong><?php echo count($currentlyIssued); ?></strong>Items Currently Held</div>
                </div>
            </div>
        </div>

        <div class="section-box">
            <h2>Currently Issued Equipment</h2>

            <?php if (count($currentlyIssued) === 0): ?>
                <div class="empty">No equipment currently issued to this officer.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Serial No</th>
                        <th>Issue Date</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentlyIssued as $r): ?>
                    <tr>
                        <td data-label="Item"><?php echo htmlspecialchars($r['item_type'] . ' - ' . $r['brand_model']); ?></td>
                        <td data-label="Serial No"><?php echo htmlspecialchars($r['asset_number']); ?></td>
                        <td data-label="Issue Date"><?php echo htmlspecialchars($r['issue_date']); ?></td>
                        <td data-label="Notes"><?php echo htmlspecialchars($r['notes'] ?: '—'); ?></td>
                        <td data-label="Action">
                            <div style="display:flex; gap:6px;">
                                <a href="return_equipment.php?id=<?php echo $r['id']; ?>&from=officer_profile.php?id=<?php echo $id; ?>" class="btn-return" onclick="return confirm('Mark this item as returned?');">Return</a>
                                <a href="delete_issue.php?id=<?php echo $r['id']; ?>&from=officer_profile.php?id=<?php echo $id; ?>" class="btn-delete" onclick="return confirm('Remove this record completely? This cannot be undone.');">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="section-box">
            <h2>Return History</h2>

            <?php if (count($history) === 0): ?>
                <div class="empty">No return history for this officer.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Serial No</th>
                        <th>Issue Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $r): ?>
                    <tr>
                        <td data-label="Item"><?php echo htmlspecialchars($r['item_type'] . ' - ' . $r['brand_model']); ?></td>
                        <td data-label="Serial No"><?php echo htmlspecialchars($r['asset_number']); ?></td>
                        <td data-label="Issue Date"><?php echo htmlspecialchars($r['issue_date']); ?></td>
                        <td data-label="Return Date"><?php echo htmlspecialchars($r['return_date']); ?></td>
                        <td data-label="Status"><span class="status-badge status-Returned">Returned</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
