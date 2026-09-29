<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$id = (int)($_GET['id'] ?? 0);
$dir = $_GET['dir'] ?? '';

if ($id > 0 && in_array($dir, ['up', 'down'], true)) {

    $pdo->beginTransaction();
    try {
        // Same ordering the Officer Directory list uses, so "up/down" matches what is on screen
        $all = $pdo->query("SELECT id, seniority_order FROM officers ORDER BY seniority_order ASC, id ASC FOR UPDATE")->fetchAll(PDO::FETCH_ASSOC);

        $pos = null;
        foreach ($all as $i => $row) {
            if ((int)$row['id'] === $id) {
                $pos = $i;
                break;
            }
        }

        if ($pos !== null) {
            $neighbour = $dir === 'up' ? $pos - 1 : $pos + 1;

            if (isset($all[$neighbour])) {
                // Swap the two positions in the list, then re-number the whole list 1..N.
                // Re-numbering keeps orders unique even if earlier deletes left gaps or duplicates.
                $tmp = $all[$pos];
                $all[$pos] = $all[$neighbour];
                $all[$neighbour] = $tmp;

                $update = $pdo->prepare("UPDATE officers SET seniority_order = ? WHERE id = ?");
                foreach ($all as $i => $row) {
                    $update->execute([$i + 1, $row['id']]);
                }
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

// Jump back to the row that was moved
header("Location: index.php#officer-" . $id);
exit();
