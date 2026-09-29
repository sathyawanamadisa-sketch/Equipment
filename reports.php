<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$selected_officer = (int)($_GET['officer_id'] ?? 0);
$all_officers = $pdo->query("SELECT * FROM officers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT i.*, 
               e.asset_number, e.item_type, e.brand_model,
               o.service_number, o.rank, o.name AS officer_name, o.section_division
        FROM issues i
        JOIN equipment e ON i.equipment_id = e.id
        JOIN officers o ON i.officer_id = o.id
        WHERE i.status = 'Issued'";
$params = [];

if ($selected_officer > 0) {
    $sql .= " AND i.officer_id = ?";
    $params[] = $selected_officer;
}

$sql .= " ORDER BY o.name ASC, i.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by officer
$grouped = [];
foreach ($rows as $r) {
    $oid = $r['officer_id'];
    if (!isset($grouped[$oid])) {
        $grouped[$oid] = [
            'officer_name'     => $r['officer_name'],
            'rank'             => $r['rank'],
            'service_number'   => $r['service_number'],
            'section_division' => $r['section_division'],
            'items'            => [],
        ];
    }
    $grouped[$oid]['items'][] = $r;
}

$report_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Issue Report</title>
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

        .filter-form { display: flex; gap: 8px; flex: 1; min-width: 250px; }

        select {
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

        .btn-print { background: #16a34a; color: white; white-space: nowrap; }
        .btn-print:hover { background: #15803d; }

        /* ---- Printable report sheet ---- */

        .report-sheet {
            background: white;
            color: #0f172a;
            border-radius: 14px;
            padding: 35px;
            margin-bottom: 25px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0f172a;
        }

        .report-header h2 { font-size: 20px; margin-bottom: 4px; }
        .report-header p { font-size: 12px; color: #475569; }

        .officer-block {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }

        .officer-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px 14px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 13px;
        }

        .officer-info strong { color: #0f172a; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-size: 13px;
        }

        th { background: #e2e8f0; font-weight: bold; }

        .signature-row {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .signature-box {
            flex: 1;
            min-width: 220px;
        }

        .signature-line {
            border-bottom: 1px solid #0f172a;
            height: 40px;
        }

        .signature-label {
            font-size: 12px;
            color: #475569;
            margin-top: 6px;
        }

        .empty {
            color: #475569;
            text-align: center;
            padding: 30px;
        }

        @media print {
            body { background: white; padding: 0; }
            .top-panel { display: none; }
            .report-sheet { border-radius: 0; padding: 0; }
            .officer-block { page-break-after: always; }
            .officer-block:last-child { page-break-after: auto; }
        }
            @media (max-width: 700px) {
            body { padding: 15px 10px; }
        }
    </style>
</head>
<body>

    <div class="container">

        <div class="top-panel">
            <h1>Equipment Issue Report</h1>

            <div class="top-bar">
                <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
                <form class="filter-form" method="GET" action="">
                    <select name="officer_id" onchange="this.form.submit()">
                        <option value="0">All Officers (currently issued)</option>
                        <?php foreach ($all_officers as $o): ?>
                            <option value="<?php echo $o['id']; ?>" <?php echo ($selected_officer == $o['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($o['rank'] . ' ' . $o['name'] . ' (' . $o['service_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
                <button onclick="window.print()" class="btn btn-print">🖨 Print</button>
            </div>
        </div>

        <?php if (count($grouped) === 0): ?>
            <div class="report-sheet">
                <div class="empty">No issued equipment found for this selection.</div>
            </div>
        <?php else: ?>

        <div class="report-sheet">
            <div class="report-header">
                <h2>Equipment Issue Report</h2>
                <p>Generated on <?php echo htmlspecialchars($report_date); ?></p>
            </div>

            <?php foreach ($grouped as $officer_id => $g): ?>
            <div class="officer-block">

                <div class="officer-info">
                    <span><strong>Officer:</strong> <?php echo htmlspecialchars($g['rank'] . ' ' . $g['officer_name']); ?></span>
                    <span><strong>Service No:</strong> <?php echo htmlspecialchars($g['service_number']); ?></span>
                    <span><strong>Section:</strong> <?php echo htmlspecialchars($g['section_division']); ?></span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Type</th>
                            <th>Brand / Model</th>
                            <th>Serial No</th>
                            <th>Issue Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['items'] as $idx => $r): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($r['item_type']); ?></td>
                            <td><?php echo htmlspecialchars($r['brand_model']); ?></td>
                            <td><?php echo htmlspecialchars($r['asset_number']); ?></td>
                            <td><?php echo htmlspecialchars($r['issue_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="signature-row">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Received By — <?php echo htmlspecialchars($g['officer_name']); ?> (Officer's Signature &amp; Date)</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-label">Issued By (Signature &amp; Date)</div>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
        <?php endif; ?>

    </div>

</body>
</html>
