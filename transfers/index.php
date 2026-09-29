query("SELECT * FROM officers ORDER BY full_name ASC")->fetchAll();

// Fetch currently ISSUED equipment with their current officers
\(issuedEquipment =\)pdo->query("
    SELECT eq.id as equipment_id, eq.serial_number, eq.item_name, o.id as current_officer_id, o.full_name, o.rank, o.service_number
    FROM handover_items hi
    JOIN handovers h ON hi.handover_id = h.id
    JOIN equipment eq ON hi.equipment_id = eq.id
    JOIN officers o ON h.officer_id = o.id
    WHERE eq.status = 'ISSUED'
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    \(eq_id =\)_POST['equipment_id'];
    \(from_officer_id =\)_POST['from_officer_id'];
    \(to_officer_id =\)_POST['to_officer_id'];
    \(notes = sanitize(\)_POST['notes']);

    if (!empty(\(eq_id) && !empty(\)to_officer_id) && (\(from_officer_id !=\)to_officer_id)) {
        $pdo->beginTransaction();
        try {
            // Create a new handover entry for the new officer
            \(handover_no = generateHandoverNo(\)pdo);
            \(stmt =\)pdo->prepare("INSERT INTO handovers (handover_no, officer_id, handover_date, notes, created_by) VALUES (?, ?, ?, ?, ?)");
            \(stmt->execute([\)handover_no, \(to_officer_id, date('Y-m-d'), "Transferred: " .\)notes, $_SESSION['user_id']]);
            \(handover_id =\)pdo->lastInsertId();

            // Link item to new handover
            \(itemStmt =\)pdo->prepare("INSERT INTO handover_items (handover_id, equipment_id) VALUES (?, ?)");
            \(itemStmt->execute([\)handover_id, $eq_id]);

            $pdo->commit();
            $success = "Equipment successfully transferred!";
            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            \(error = "Transfer failed: " .\)e->getMessage();
        }
    }
}
?>