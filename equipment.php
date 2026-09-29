<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

// ---- Search handling ----
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE item_name LIKE ? OR asset_number LIKE ? OR item_type LIKE ? OR brand_model LIKE ? OR section_division LIKE ? OR status LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM equipment ORDER BY id DESC");
}
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Flash message (after add/edit/delete redirect) ----
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment</title>
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        form.search-form {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 250px;
        }

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

        .btn-edit { background: #f59e0b; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-edit:hover { background: #d97706; }

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

        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
        }

        th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: bold;
        }

        tr:hover { background: rgba(255, 255, 255, 0.05); }

        .actions { display: flex; gap: 6px; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-Available { background: rgba(34, 197, 94, 0.2); color: #86efac; }
        .status-Issued { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .status-Workshop { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

        .empty {
            color: #cbd5e1;
            text-align: center;
            padding: 30px;
        }

        @media (max-width: 700px) {
            body { padding: 15px 10px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            tr {
                margin-bottom: 15px;
                background: rgba(255,255,255,0.05);
                border-radius: 10px;
                padding: 10px;
            }
            td {
                border: none;
                padding: 6px 10px;
            }
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
        <h1>Equipment</h1>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="top-bar">
            <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
            <form class="search-form" method="GET" action="">
                <input type="text" name="search" placeholder="Search by serial no, type, brand, status, section..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <a href="add_equipment.php" class="btn btn-add">+ Add Equipment</a>
        </div>

        <?php if (count($equipment) === 0): ?>
            <div class="empty">No equipment found.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Serial Number</th>
                    <th>Item Type</th>
                    <th>Brand / Model</th>
                    <th>Status</th>
                    <th>Section / Division</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipment as $e): ?>
                <tr>
                    <td data-label="Item Name"><?php echo htmlspecialchars($e['item_name'] ?? ''); ?></td>
                    <td data-label="Serial Number"><?php echo htmlspecialchars($e['asset_number']); ?></td>
                    <td data-label="Item Type"><?php echo htmlspecialchars($e['item_type']); ?></td>
                    <td data-label="Brand / Model"><?php echo htmlspecialchars($e['brand_model']); ?></td>
                    <td data-label="Status">
                        <span class="status-badge status-<?php echo htmlspecialchars($e['status']); ?>">
                            <?php echo htmlspecialchars($e['status']); ?>
                        </span>
                    </td>
                    <td data-label="Section"><?php echo htmlspecialchars($e['section_division']); ?></td>
                    <td data-label="Date Added"><?php echo htmlspecialchars($e['date_added']); ?></td>
                    <td data-label="Actions">
                        <div class="actions">
                            <a href="edit_equipment.php?id=<?php echo $e['id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete_equipment.php?id=<?php echo $e['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this equipment item?');">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</body>
</html>
