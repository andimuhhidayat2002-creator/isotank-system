<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=isotank_db', 'root', 'Kayanconect2024!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- VACUUM LOGS SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE vacuum_logs");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $cols) . "\n\n";

    echo "--- INSPECTION LOGS SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE inspection_logs");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $cols) . "\n\n";

    echo "--- TEST VACUUM QUERY ---\n";
    try {
        $stmt = $pdo->query("SELECT v.isotank_id, v.vacuum_value_mtorr, v.check_datetime, m.manufacturer FROM vacuum_logs v JOIN master_isotanks m ON v.isotank_id = m.id LIMIT 1");
        print_r($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Vacuum Query Failed: " . $e->getMessage() . "\n";
    }

    echo "\n--- TEST INSPECTOR QUERY ---\n";
    try {
        $stmt = $pdo->query("SELECT u.name as inspector_name, COUNT(*) as count FROM inspection_logs l JOIN users u ON l.inspector_id = u.id GROUP BY u.name LIMIT 1");
        print_r($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Inspector Query Failed: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Connection Failed: " . $e->getMessage();
}
