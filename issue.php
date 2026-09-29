<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

// ---- Search + filter handling ----
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$sql = "SELECT i.*, 
               e.asset_number, e.item_type, e.brand_model,
               o.service_number, o.rank, o.name AS officer_name, o.section_division
        FROM issues i
        JOIN equipment e ON i.equipment_id = e.id
        JOIN officers o ON i.officer_id = o.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (e.asset_number LIKE ? OR e.item_type LIKE ? OR o.name LIKE ? OR o.service_number LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

if ($filter_status !== '' && in_array($filter_status, ['Issued', 'Returned'])) {
    $sql .= " AND i.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY o.name ASC, i.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Group flat rows by officer, so each officer appears once with all their items nested ----
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

$message = $_GET['msg'] ?? '';

// When searching, show matching items right away instead of hiding them behind "More"
$auto_open = ($search !== '');
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
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            padding: 25px 30px;
        }

        .container {
            width: 100%;
            max-width: none;
            margin: 0;
        }

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

        form.search-form {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 250px;
        }

        input[type="text"], select.filter-select {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 14px;
        }

        input[type="text"] { flex: 1; }

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

        .btn-return { background: #f59e0b; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-return:hover { background: #d97706; }

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

        /* ---- Officer group cards ---- */

        .officer-group {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .officer-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px;
            background: rgba(37, 99, 235, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            user-select: none;
        }

        .officer-header:hover {
            background: rgba(37, 99, 235, 0.22);
        }

        .officer-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }

        .officer-header-text strong {
            color: white;
            font-size: 16px;
            display: block;
        }

        .officer-header-text span {
            color: #93c5fd;
            font-size: 13px;
        }

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

        .btn-more {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-more:hover {
            background: rgba(255,255,255,0.28);
        }

        .more-arrow {
            display: inline-block;
            transition: transform 0.2s;
            font-size: 11px;
        }

        .officer-group.open .more-arrow {
            transform: rotate(180deg);
        }

        .officer-items {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.2s ease;
        }

        .officer-items > table {
            overflow: hidden;
            min-height: 0;
        }

        .officer-group.open .officer-items {
            grid-template-rows: 1fr;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }

        th, td {
            padding: 12px 22px;
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

        .status-Issued { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .status-Returned { background: rgba(34, 197, 94, 0.2); color: #86efac; }

        .action-cell { display: flex; gap: 6px; }

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
            tbody tr {
                padding: 10px 12px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
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
            <h1>Issue Equipment</h1>

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="top-bar">
                <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
                <form class="search-form" method="GET" action="">
                    <input type="text" name="search" placeholder="Search by officer, service no, serial no, item type..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Issued" <?php echo ($filter_status === 'Issued') ? 'selected' : ''; ?>>Issued</option>
                        <option value="Returned" <?php echo ($filter_status === 'Returned') ? 'selected' : ''; ?>>Returned</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <a href="issue_equipment.php" class="btn btn-add">+ Issue Equipment</a>
            </div>
        </div>

        <?php if (count($grouped) === 0): ?>
            <div class="empty">No records found.</div>
        <?php else: ?>
            <?php foreach ($grouped as $officer_id => $g): ?>
            <div class="officer-group<?php echo $auto_open ? ' open' : ''; ?>" data-officer="<?php echo (int)$officer_id; ?>">
                <div class="officer-header" role="button" tabindex="0" aria-expanded="<?php echo $auto_open ? 'true' : 'false'; ?>">
                    <div class="officer-avatar"><?php echo strtoupper(substr($g['officer_name'], 0, 1)); ?></div>
                    <div class="officer-header-text">
                        <strong><?php echo htmlspecialchars($g['rank'] . ' ' . $g['officer_name']); ?></strong>
                        <span><?php echo htmlspecialchars($g['service_number'] . ' • ' . $g['section_division']); ?></span>
                    </div>
                    <div class="item-count-pill"><?php echo count($g['items']); ?> item(s)</div>
                    <button type="button" class="btn-more"><span class="more-label">More</span> <span class="more-arrow">&#9662;</span></button>
                </div>

                <div class="officer-items">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Serial No</th>
                            <th>Issue Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['items'] as $r): ?>
                        <tr>
                            <td data-label="Item"><?php echo htmlspecialchars($r['item_type'] . ' - ' . $r['brand_model']); ?></td>
                            <td data-label="Serial No"><?php echo htmlspecialchars($r['asset_number']); ?></td>
                            <td data-label="Issue Date"><?php echo htmlspecialchars($r['issue_date']); ?></td>
                            <td data-label="Return Date"><?php echo htmlspecialchars($r['return_date'] ?? '—'); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo htmlspecialchars($r['status']); ?>">
                                    <?php echo htmlspecialchars($r['status']); ?>
                                </span>
                            </td>
                            <td data-label="Action">
                                <div class="action-cell">
                                    <?php if ($r['status'] === 'Issued'): ?>
                                        <a href="return_equipment.php?id=<?php echo $r['id']; ?>&from=issue.php" class="btn btn-return" onclick="return confirm('Mark this item as returned?');">Return</a>
                                    <?php endif; ?>
                                    <a href="delete_issue.php?id=<?php echo $r['id']; ?>&from=issue.php" class="btn btn-delete" onclick="return confirm('Remove this record completely? This cannot be undone.');">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Expand / collapse an officer's issued items when "More" (or the header) is clicked.
        // Open officers are remembered for this browser tab, so after Return / Delete the page reloads with the same ones open.
        (function () {
            var KEY = 'issueOpenOfficers';
            var groups = document.querySelectorAll('.officer-group');

            function loadOpen() {
                try { return JSON.parse(sessionStorage.getItem(KEY)) || []; } catch (e) { return []; }
            }
            function saveOpen(list) {
                try { sessionStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
            }

            var openList = loadOpen();

            function setOpen(group, open) {
                var header = group.querySelector('.officer-header');
                var label = group.querySelector('.more-label');
                group.classList.toggle('open', open);
                header.setAttribute('aria-expanded', open ? 'true' : 'false');
                label.textContent = open ? 'Less' : 'More';
            }

            groups.forEach(function (group) {
                var id = group.getAttribute('data-officer');
                var header = group.querySelector('.officer-header');

                // Restore previous state (or keep the server-side auto-open for searches)
                setOpen(group, group.classList.contains('open') || openList.indexOf(id) !== -1);

                function toggle() {
                    var nowOpen = !group.classList.contains('open');
                    setOpen(group, nowOpen);
                    var list = loadOpen().filter(function (x) { return x !== id; });
                    if (nowOpen) list.push(id);
                    saveOpen(list);
                }

                header.addEventListener('click', toggle);
                header.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
                });
            });
        })();
    </script>

</body>
</html>
