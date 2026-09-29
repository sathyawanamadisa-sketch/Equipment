<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$search = trim($_GET['search'] ?? '');

// ---- Pending returns (currently Issued), grouped by officer ----
$pendingSql = "SELECT i.*, 
                      e.asset_number, e.item_type, e.brand_model,
                      o.service_number, o.rank, o.name AS officer_name, o.section_division
               FROM issues i
               JOIN equipment e ON i.equipment_id = e.id
               JOIN officers o ON i.officer_id = o.id
               WHERE i.status = 'Issued'";
$pendingParams = [];

if ($search !== '') {
    $pendingSql .= " AND (e.asset_number LIKE ? OR e.item_type LIKE ? OR o.name LIKE ? OR o.service_number LIKE ?)";
    $like = "%$search%";
    array_push($pendingParams, $like, $like, $like, $like);
}

$pendingSql .= " ORDER BY o.name ASC, i.id DESC";

$stmt = $pdo->prepare($pendingSql);
$stmt->execute($pendingParams);
$pendingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingGrouped = [];
foreach ($pendingRows as $r) {
    $oid = $r['officer_id'];
    if (!isset($pendingGrouped[$oid])) {
        $pendingGrouped[$oid] = [
            'officer_name'     => $r['officer_name'],
            'rank'             => $r['rank'],
            'service_number'   => $r['service_number'],
            'section_division' => $r['section_division'],
            'items'            => [],
        ];
    }
    $pendingGrouped[$oid]['items'][] = $r;
}

// ---- Return history (already Returned), flat list, most recent first ----
$historySql = "SELECT i.*, 
                      e.asset_number, e.item_type, e.brand_model,
                      o.service_number, o.rank, o.name AS officer_name, o.section_division
               FROM issues i
               JOIN equipment e ON i.equipment_id = e.id
               JOIN officers o ON i.officer_id = o.id
               WHERE i.status = 'Returned'";
$historyParams = [];

if ($search !== '') {
    $historySql .= " AND (e.asset_number LIKE ? OR e.item_type LIKE ? OR o.name LIKE ? OR o.service_number LIKE ?)";
    array_push($historyParams, $like, $like, $like, $like);
}

$historySql .= " ORDER BY i.return_date DESC, i.id DESC";

$stmt2 = $pdo->prepare($historySql);
$stmt2->execute($historyParams);
$historyRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$message = $_GET['msg'] ?? '';
$totalPending = count($pendingRows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns</title>
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

        h1 { color: white; margin-bottom: 20px; text-align: center; }

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

        .btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); white-space: nowrap; }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        .btn-return { background: #f59e0b; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-return:hover { background: #d97706; }

        .message {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid #22c55e;
            color: #bbf7d0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .section-heading h2 { color: white; font-size: 18px; }

        .count-pill {
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .officer-group {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
            overflow: hidden;
        }

        .officer-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            background: rgba(245, 158, 11, 0.12);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .officer-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #f59e0b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 15px;
            flex-shrink: 0;
        }

        .officer-header-text strong { color: white; font-size: 15px; display: block; }
        .officer-header-text span { color: #fcd34d; font-size: 13px; }

        .item-count-pill {
            margin-left: auto;
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
            white-space: nowrap;
        }

        table { width: 100%; border-collapse: collapse; color: white; }

        th, td {
            padding: 11px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 14px;
        }

        th { background: rgba(255, 255, 255, 0.05); font-weight: bold; font-size: 12px; text-transform: uppercase; color: #cbd5e1; }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(255, 255, 255, 0.04); }

        .history-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

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
                width: 110px;
                color: #93c5fd;
            }
        }
    </style>
</head>
<body>

    <div class="container">

        <div class="top-panel">
            <h1>Returns</h1>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="top-bar">
                <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
                <form class="search-form" method="GET" action="">
                    <input type="text" name="search" placeholder="Search by officer, service no, serial no, item type..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <div class="section-heading">
            <h2>Pending Returns</h2>
            <span class="count-pill"><?php echo $totalPending; ?> item(s)</span>
        </div>

        <?php if (count($pendingGrouped) === 0): ?>
            <div class="empty">No equipment is currently out for return.</div>
        <?php else: ?>
            <?php foreach ($pendingGrouped as $officer_id => $g): ?>
            <div class="officer-group">
                <div class="officer-header">
                    <div class="officer-avatar"><?php echo strtoupper(substr($g['officer_name'], 0, 1)); ?></div>
                    <div class="officer-header-text">
                        <strong><?php echo htmlspecialchars($g['rank'] . ' ' . $g['officer_name']); ?></strong>
                        <span><?php echo htmlspecialchars($g['service_number'] . ' • ' . $g['section_division']); ?></span>
                    </div>
                    <div class="item-count-pill"><?php echo count($g['items']); ?> item(s)</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Serial No</th>
                            <th>Issue Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['items'] as $r): ?>
                        <tr>
                            <td data-label="Item"><?php echo htmlspecialchars($r['item_type'] . ' - ' . $r['brand_model']); ?></td>
                            <td data-label="Serial No"><?php echo htmlspecialchars($r['asset_number']); ?></td>
                            <td data-label="Issue Date"><?php echo htmlspecialchars($r['issue_date']); ?></td>
                            <td data-label="Action">
                                <a href="return_equipment.php?id=<?php echo $r['id']; ?>&from=returns.php" class="btn btn-return" onclick="return confirm('Mark this item as returned?');">Return</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="section-heading" style="margin-top: 30px;">
            <h2>Store</h2>
        </div>
        <div class="empty" style="padding: 18px;">
            Returned items go back into <a href="store.php" style="color:#93c5fd; font-weight:bold;">Store</a> and become available to issue again.
        </div>

    </div>

</body>
</html>
