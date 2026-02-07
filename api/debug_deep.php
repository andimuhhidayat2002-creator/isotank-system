<?php

echo "\n\n--- VACUUM LOGS (MULTI-POINT CHECK) ---\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=isotank_db', 'root', 'Kayanconect2024!');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT isotank_id, COUNT(*) as c FROM vacuum_logs GROUP BY isotank_id HAVING c > 1 LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "NO TANKS HAVE > 1 LOG ENTRY. Rise Rate cannot be calculated.\n";
    } else {
        echo "Found tanks with history:\n";
        print_r($rows);
    }
    
    echo "\n--- INSPECTION LOGS (NULL CHECK) ---\n";
    $stmt = $pdo->query("SELECT id FROM inspection_logs WHERE created_at IS NULL LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        echo "WARNING: FOUND INSPECTION LOGS WITH NULL created_at:\n";
        print_r($rows);
    } else {
        echo "All inspection logs have created_at.\n";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "--- LOG CHECK (LAST 50 LINES) ---\n";
// Read last 5 KB of log file
$logFile = '/var/www/isotank-system/api/storage/logs/laravel.log';
if (file_exists($logFile)) {
    // tail command provided by system
    echo shell_exec("tail -n 50 $logFile");
} else {
    echo "Log file not found.\n";
}
