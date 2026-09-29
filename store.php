<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM equipment WHERE status = 'Available'";
$params = [];

if ($search !== '') {
    $sql .= " AND (asset_number LIKE ? OR item_type LIKE ? OR brand_model LIKE ? OR section_division LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

$sql .= " ORDER BY item_type, asset_number";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store</title>
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

        .btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); white-space: nowrap; }
        .btn-back:hover { background: rgba(255,255,255,0.25); }

        .btn-issue { background: #16a34a; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-issue:hover { background: #15803d; }

        .count-pill {
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: 8px;
        }

        .store-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
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
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .empty {
            color: #cbd5e1;
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            <h1>Store</h1>
            <p class="subtitle">Equipment currently in store — not yet issued to any officer</p>

            <div class="top-bar">
                <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
                <form class="search-form" method="GET" action="">
                    <input type="text" name="search" placeholder="Search by serial no, type, brand, section..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <span class="count-pill"><?php echo count($stock); ?> item(s) in store</span>
            </div>
        </div>

        <?php if (count($stock) === 0): ?>
            <div class="empty">No equipment currently in store — everything is issued out.</div>
        <?php else: ?>
        <div class="store-box">
            <table>
                <thead>
                    <tr>
                        <th>Item Type</th>
                        <th>Brand / Model</th>
                        <th>Serial No</th>
                        <th>Section / Division</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock as $s): ?>
                    <tr>
                        <td data-label="Item Type"><?php echo htmlspecialchars($s['item_type']); ?></td>
                        <td data-label="Brand / Model"><?php echo htmlspecialchars($s['brand_model']); ?></td>
                        <td data-label="Serial No"><?php echo htmlspecialchars($s['asset_number']); ?></td>
                        <td data-label="Section"><?php echo htmlspecialchars($s['section_division']); ?></td>
                        <td data-label="Status"><span class="status-badge">In Store</span></td>
                        <td data-label="Action">
                            <a href="issue_equipment.php" class="btn btn-issue">Issue</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

</body>
</html>
