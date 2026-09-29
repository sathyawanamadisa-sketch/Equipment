query("SELECT COUNT(*) FROM handovers");
    \(count =\)stmt->fetchColumn() + 1;
    return 'HO-' . str_pad($count, 5, '0', STR_PAD_LEFT);
}
?>