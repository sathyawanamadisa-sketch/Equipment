<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'Administrator';

require_once "db.php";

// ---- Dashboard stat counts ----
$total_equipment = (int)$pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$available_equipment = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Available'")->fetchColumn();
$issued_equipment = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Issued'")->fetchColumn();
$total_officers = (int)$pdo->query("SELECT COUNT(*) FROM officers")->fetchColumn();
$workshop_equipment = (int)$pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Workshop'")->fetchColumn();

// ---- Recent activity feed (issue + return events, most recent first) ----
$recent_activity = $pdo->query("
    (SELECT 'Issued' AS action, i.issue_date AS event_date, i.id,
            o.rank, o.name AS officer_name, e.item_type, e.brand_model, e.asset_number
     FROM issues i
     JOIN officers o ON i.officer_id = o.id
     JOIN equipment e ON i.equipment_id = e.id)
    UNION ALL
    (SELECT 'Returned' AS action, i.return_date AS event_date, i.id,
            o.rank, o.name AS officer_name, e.item_type, e.brand_model, e.asset_number
     FROM issues i
     JOIN officers o ON i.officer_id = o.id
     JOIN equipment e ON i.equipment_id = e.id
     WHERE i.status = 'Returned')
    ORDER BY event_date DESC, id DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Equipment Management System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>

        :root {
            --ink: #0b1526;
            --bg-gradient: linear-gradient(135deg, #0f172a, #1e3a8a);
            --ink-soft: #16233a;
            --canvas: #f3f4f7;
            --paper: #ffffff;
            --brass: #b8912f;
            --brass-soft: #f3ecd9;
            --line: #e4e7ec;
            --text: #1a2233;
            --text-soft: #5b6478;

            --c-total: #334155;
            --c-total-bg: #eef1f5;
            --c-available: #0f9d58;
            --c-available-bg: #e6f6ee;
            --c-issued: #d97706;
            --c-issued-bg: #fdf1e2;
            --c-officers: #b8912f;
            --c-officers-bg: #f3ecd9;

            --c-workshop: #dc2626;
            --c-workshop-bg: #fde8e8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        body {
            background: var(--canvas);
            color: var(--text);
        }

        h1, h2, h3, .brand-font {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
        }

        /* =========================
           SIDEBAR
        ========================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;

            background: var(--bg-gradient);
            color: white;

            padding: 25px 15px;

            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 20px;
        }

        .logo-badge {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: var(--brass);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
            font-family: 'Space Grotesk', sans-serif;
        }

        .logo h2 {
            font-size: 17px;
            font-weight: 600;
            line-height: 1.2;
        }

        .logo span {
            color: #93a1bd;
            font-size: 12px;
        }

        .menu-title {
            font-size: 11px;
            color: #6b7690;
            padding: 0 12px;
            margin-bottom: 10px;
            letter-spacing: 0.04em;
        }

        .menu a {
            display: flex;
            align-items: center;

            gap: 12px;

            padding: 11px 12px;

            margin-bottom: 4px;

            color: #b8c0d4;
            text-decoration: none;
            font-size: 14px;

            border-radius: 8px;
            border-left: 3px solid transparent;

            transition: 0.2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }

        .menu a.active {
            background: rgba(184,145,47,0.14);
            border-left-color: var(--brass);
            color: white;
            font-weight: 500;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
            opacity: 0.9;
        }

        /* =========================
           MAIN CONTENT
        ========================== */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* TOPBAR */

        .topbar {
            height: 75px;

            background: var(--paper);

            display: flex;
            justify-content: space-between;
            align-items: center;

            padding: 0 35px;

            border-bottom: 1px solid var(--line);
        }

        .page-title h1 {
            font-size: 22px;
            font-weight: 600;
            color: var(--ink);
        }

        .page-title p {
            font-size: 13px;
            color: var(--text-soft);
            margin-top: 3px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px;
            height: 40px;

            background: var(--bg-gradient);
            color: var(--brass);

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 700;
            font-family: 'Space Grotesk', sans-serif;
            box-shadow: 0 0 0 2px var(--brass-soft);
        }

        .profile-info strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
        }

        .profile-info span {
            font-size: 12px;
            color: var(--text-soft);
        }

        /* =========================
           CONTENT
        ========================== */

        .content {
            padding: 35px;
        }

        .welcome-box {
            position: relative;
            overflow: hidden;

            background: var(--bg-gradient);
            color: white;

            padding: 32px 34px;

            border-radius: 16px;

            margin-bottom: 30px;
        }

        .welcome-box::after {
            content: "";
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(184,145,47,0.35), transparent 70%);
        }

        .welcome-box h2 {
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
        }

        .welcome-box p {
            color: #c3cbdd;
            font-size: 14px;
            position: relative;
        }

        .welcome-date {
            position: relative;
            display: inline-block;
            margin-top: 16px;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(184,145,47,0.16);
            border: 1px solid rgba(184,145,47,0.35);
            color: var(--brass);
            font-size: 12px;
            font-weight: 600;
        }

        /* =========================
           STAT CARDS
        ========================== */

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: var(--paper);
            padding: 22px;
            border-radius: 12px;
            border: 1px solid var(--line);
            border-top: 3px solid var(--stat-color, var(--brass));
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 22px -8px var(--stat-color, var(--brass));
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--stat-bg, var(--brass-soft));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
        }

        .stat-card h3 {
            font-size: 12.5px;
            color: var(--text-soft);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .stat-card .number {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
        }

        .stat-card .subtext {
            font-size: 11.5px;
            color: var(--text-soft);
            margin-top: 6px;
        }

        /* =========================
           QUICK ACTIONS
        ========================== */

        .section-title {
            margin-bottom: 16px;
        }

        .section-title h2 {
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
        }

        .section-title p {
            color: var(--text-soft);
            font-size: 13px;
            margin-top: 3px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }

        .action {
            background: var(--paper);
            padding: 18px;
            border-radius: 12px;
            border: 1px solid var(--line);
            text-decoration: none;
            color: var(--text);
            transition: 0.2s;
        }

        .action:hover {
            border-color: var(--action-color, var(--brass));
            transform: translateY(-2px);
            box-shadow: 0 8px 18px -10px var(--action-color, var(--brass));
        }

        .action-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--action-bg, var(--brass-soft));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 14px;
        }

        .action h3 {
            font-size: 14.5px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .action p {
            font-size: 12px;
            color: var(--text-soft);
        }

        /* =========================
           RECENT ACTIVITY
        ========================== */

        .activity {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 25px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--canvas);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--activity-bg, var(--brass-soft));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .activity-text strong {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
        }

        .activity-text span {
            font-size: 12px;
            color: var(--text-soft);
        }

        .activity-empty {
            padding: 30px 0;
            text-align: center;
        }

        .activity-empty strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .activity-empty span {
            font-size: 12.5px;
            color: var(--text-soft);
        }

        .activity-empty a {
            color: var(--brass);
            font-weight: 600;
            text-decoration: none;
        }

        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 768px) {

            .sidebar {
                width: 70px;
            }

            .logo h2,
            .logo span,
            .menu-title,
            .menu a span {
                display: none;
            }

            .menu a {
                justify-content: center;
            }

            .main {
                margin-left: 70px;
            }

            .topbar {
                padding: 0 20px;
            }

            .content {
                padding: 20px;
            }

            .stats,
            .quick-actions {
                grid-template-columns: 1fr;
            }

            .profile-info {
                display: none;
            }

        }

    </style>

</head>

<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        <div class="logo-badge">EQ</div>

        <div>
            <h2>Equipment</h2>
            <span>Management System</span>
        </div>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <nav class="menu">

        <a href="dashboard.php" class="active">

            <span class="menu-icon">📊</span>

            <span>Dashboard</span>

        </a>


        <a href="index.php">

            <span class="menu-icon">🪪</span>

            <span>Officer Directory</span>

        </a>


        <a href="equipment.php">

            <span class="menu-icon">💻</span>

            <span>Equipment</span>

        </a>


        <a href="issue.php">

            <span class="menu-icon">📤</span>

            <span>Issue Equipment</span>

        </a>


        <a href="returns.php">

            <span class="menu-icon">📥</span>

            <span>Returns</span>

        </a>


        <a href="store.php">

            <span class="menu-icon">🏬</span>

            <span>Store</span>

        </a>


        <a href="workshop.php">

            <span class="menu-icon">🛠️</span>

            <span>Workshop (G7)</span>

        </a>


        <a href="reports.php">

            <span class="menu-icon">📋</span>

            <span>Reports</span>

        </a>

    </nav>


    <div class="menu-title"
         style="margin-top:30px;">

        Account

    </div>


    <nav class="menu">

        <a href="logout.php">

            <span class="menu-icon">🚪</span>

            <span>Logout</span>

        </a>

    </nav>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <!-- TOP BAR -->

    <header class="topbar">

        <div class="page-title">

            <h1>Dashboard</h1>

            <p>
                Equipment Management Overview
            </p>

        </div>


        <div class="profile">

            <div class="avatar">

                <?php
                echo strtoupper(
                    substr($full_name, 0, 1)
                );
                ?>

            </div>


            <div class="profile-info">

                <strong>
                    <?php
                    echo htmlspecialchars($full_name);
                    ?>
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- WELCOME -->

        <div class="welcome-box">

            <h2>
                Welcome back,
                <?php
                echo htmlspecialchars($full_name);
                ?> 👋
            </h2>

            <p>
                Manage your office equipment,
                officers and equipment assignments
                from one place.
            </p>

            <div class="welcome-date">
                <?php echo date('l, j F Y'); ?>
            </div>

        </div>


        <!-- STATISTICS -->

        <div class="stats">


            <div class="stat-card" style="--stat-color: var(--c-total); --stat-bg: var(--c-total-bg);">

                <div class="stat-top">

                    <div>
                        <h3>Total Equipment</h3>

                        <div class="number">
                            <?php echo $total_equipment; ?>
                        </div>
                    </div>

                    <div class="stat-icon">
                        💻
                    </div>

                </div>

                <div class="subtext">All registered items</div>

            </div>


            <div class="stat-card" style="--stat-color: var(--c-available); --stat-bg: var(--c-available-bg);">

                <div class="stat-top">

                    <div>

                        <h3>Available Equipment</h3>

                        <div class="number">
                            <?php echo $available_equipment; ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        📦
                    </div>

                </div>

                <div class="subtext">Currently in store, ready to issue</div>

            </div>


            <div class="stat-card" style="--stat-color: var(--c-issued); --stat-bg: var(--c-issued-bg);">

                <div class="stat-top">

                    <div>

                        <h3>Issued Equipment</h3>

                        <div class="number">
                            <?php echo $issued_equipment; ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        📤
                    </div>

                </div>

                <div class="subtext">Currently with officers</div>

            </div>


            <div class="stat-card" style="--stat-color: var(--c-officers); --stat-bg: var(--c-officers-bg);">

                <div class="stat-top">

                    <div>

                        <h3>Total Officers</h3>

                        <div class="number">
                            <?php echo $total_officers; ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        👨‍💼
                    </div>

                </div>

                <div class="subtext">In the directory</div>

            </div>


            <div class="stat-card" style="--stat-color: var(--c-workshop); --stat-bg: var(--c-workshop-bg);">

                <div class="stat-top">

                    <div>

                        <h3>In Workshop</h3>

                        <div class="number">
                            <?php echo $workshop_equipment; ?>
                        </div>

                    </div>

                    <div class="stat-icon">
                        🛠️
                    </div>

                </div>

                <div class="subtext">Currently under repair (G7)</div>

            </div>


        </div>


        <!-- QUICK ACTIONS -->

        <div class="section-title">

            <h2>
                Quick Actions
            </h2>

            <p>
                Quickly access frequently used functions.
            </p>

        </div>


        <div class="quick-actions">


            <a href="equipment.php"
               class="action" style="--action-color: var(--c-total); --action-bg: var(--c-total-bg);">

                <div class="action-icon">
                    💻
                </div>

                <h3>
                    Add Equipment
                </h3>

                <p>
                    Register a new computer item.
                </p>

            </a>


            <a href="add_officer.php"
               class="action" style="--action-color: var(--c-officers); --action-bg: var(--c-officers-bg);">

                <div class="action-icon">
                    🪪
                </div>

                <h3>
                    Add Officer
                </h3>

                <p>
                    Register a new officer in the directory.
                </p>

            </a>


            <a href="issue_equipment.php"
               class="action" style="--action-color: var(--c-issued); --action-bg: var(--c-issued-bg);">

                <div class="action-icon">
                    📤
                </div>

                <h3>
                    Issue Equipment
                </h3>

                <p>
                    Assign equipment to an officer.
                </p>

            </a>


            <a href="returns.php"
               class="action" style="--action-color: var(--c-available); --action-bg: var(--c-available-bg);">

                <div class="action-icon">
                    📥
                </div>

                <h3>
                    Return Equipment
                </h3>

                <p>
                    Record returned equipment.
                </p>

            </a>


            <a href="add_workshop.php"
               class="action" style="--action-color: var(--c-workshop); --action-bg: var(--c-workshop-bg);">

                <div class="action-icon">
                    🛠️
                </div>

                <h3>
                    Workshop (G7)
                </h3>

                <p>
                    Send faulty equipment for repair.
                </p>

            </a>


        </div>


        <!-- RECENT ACTIVITY -->

        <div class="section-title">

            <h2>
                Recent Activity
            </h2>

            <p>
                Latest equipment movements.
            </p>

        </div>


        <div class="activity">

            <?php if (count($recent_activity) === 0): ?>

            <div class="activity-empty">
                <strong>No activity yet</strong>
                <span>Once you <a href="issue_equipment.php">issue equipment</a> to an officer, it will show up here.</span>
            </div>

            <?php else: ?>

                <?php foreach ($recent_activity as $a): ?>
                <div class="activity-item">

                    <div class="activity-icon" style="--activity-bg: <?php echo $a['action'] === 'Issued' ? 'var(--c-issued-bg)' : 'var(--c-available-bg)'; ?>;">
                        <?php echo $a['action'] === 'Issued' ? '📤' : '📥'; ?>
                    </div>

                    <div class="activity-text">

                        <strong>
                            <?php echo htmlspecialchars($a['rank'] . ' ' . $a['officer_name']); ?>
                            <?php echo $a['action'] === 'Issued' ? 'was issued' : 'returned'; ?>
                            <?php echo htmlspecialchars($a['item_type'] . ' - ' . $a['brand_model']); ?>
                        </strong>

                        <span>
                            <?php echo htmlspecialchars($a['asset_number']); ?> • <?php echo htmlspecialchars($a['event_date']); ?>
                        </span>

                    </div>

                </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>


    </section>

</main>


</body>

</html>
