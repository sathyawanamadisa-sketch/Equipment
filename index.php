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
    $stmt = $pdo->prepare("SELECT * FROM officers WHERE service_number LIKE ? OR name LIKE ? OR rank LIKE ? OR section_division LIKE ? ORDER BY seniority_order ASC, id ASC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM officers ORDER BY seniority_order ASC, id ASC");
}
$officers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Up/Down arrows only make sense on the full list (moving a name inside a filtered list would be confusing)
$can_reorder = ($search === '');

// ---- Flash message (after add/edit/delete redirect) ----
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Directory</title>
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

        .btn-view { background: #2563eb; color: white; padding: 6px 12px; font-size: 13px; }
        .btn-view:hover { background: #1d4ed8; }

        .btn-move { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 6px 10px; font-size: 12px; }
        .btn-move:hover { background: rgba(255,255,255,0.3); }
        .btn-move.disabled { opacity: 0.3; cursor: not-allowed; }
        .btn-move.disabled:hover { background: rgba(255,255,255,0.15); }

        .hint { color: #cbd5e1; font-size: 13px; text-align: center; margin-top: 15px; }

        tr:target { background: rgba(37, 99, 235, 0.25); }

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

        .empty {
            color: #cbd5e1;
            text-align: center;
            padding: 30px;
        }

        @media (max-width: 700px) {
            body { padding: 15px 10px; }
            .container { padding: 18px; }
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
        <h1>Officer Directory</h1>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="top-bar">
            <a href="dashboard.php" class="btn btn-back">&larr; Dashboard</a>
            <form class="search-form" method="GET" action="">
                <input type="text" name="search" placeholder="Search by service no, name, rank, section..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <a href="add_officer.php" class="btn btn-add">+ Add Officer</a>
        </div>

        <?php if (count($officers) === 0): ?>
            <div class="empty">No officers found.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service Number</th>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Phone Number</th>
                    <th>Section / Division</th>
                    <?php if ($can_reorder): ?><th>Order</th><?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = count($officers); ?>
                <?php foreach ($officers as $i => $o): ?>
                <tr id="officer-<?php echo $o['id']; ?>">
                    <td data-label="#"><?php echo $i + 1; ?></td>
                    <td data-label="Service Number"><?php echo htmlspecialchars($o['service_number']); ?></td>
                    <td data-label="Rank"><?php echo htmlspecialchars($o['rank']); ?></td>
                    <td data-label="Name"><?php echo htmlspecialchars($o['name']); ?></td>
                    <td data-label="Phone"><?php echo htmlspecialchars($o['phone_number']); ?></td>
                    <td data-label="Section"><?php echo htmlspecialchars($o['section_division']); ?></td>
                    <?php if ($can_reorder): ?>
                    <td data-label="Order">
                        <div class="actions">
                            <?php if ($i > 0): ?>
                                <a href="move_officer.php?id=<?php echo $o['id']; ?>&dir=up" class="btn btn-move" title="Move up">&#9650;</a>
                            <?php else: ?>
                                <span class="btn btn-move disabled">&#9650;</span>
                            <?php endif; ?>
                            <?php if ($i < $total - 1): ?>
                                <a href="move_officer.php?id=<?php echo $o['id']; ?>&dir=down" class="btn btn-move" title="Move down">&#9660;</a>
                            <?php else: ?>
                                <span class="btn btn-move disabled">&#9660;</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                    <td data-label="Actions">
                        <div class="actions">
                            <a href="officer_profile.php?id=<?php echo $o['id']; ?>" class="btn btn-view">View</a>
                            <a href="edit_officer.php?id=<?php echo $o['id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete_officer.php?id=<?php echo $o['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this officer?');">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$can_reorder): ?>
            <p class="hint">Clear the search to change the seniority order.</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>

</body>
</html>
