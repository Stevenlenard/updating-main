<?php
// api/dashboard-stats.php (debug-friendly)
// Replace existing file with this while debugging.
// NOTE: remove verbose debug output when fixed.

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function jsonResp($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// quick info for debugging (no passwords)
$debugInfo = [
    'pdo_set' => isset($pdo),
    'mysqli_conn_set' => isset($conn),
    'db_host_present' => getenv('DB_HOST') !== false,
    'db_name' => getenv('DB_NAME') ?: null,
];

try {
    if (!isset($pdo) && !isset($conn)) {
        jsonResp(['success' => false, 'error' => 'No DB connection ($pdo or $conn) in includes/config.php', 'debug' => $debugInfo]);
    }

    $out = [
        'success' => true,
        'totalBins' => 0,
        'fullBins' => 0,
        'activeJanitors' => 0,
        'collectionsToday' => 0,
        'totalCollections' => 0,
        'debug' => $debugInfo,
    ];

    if (isset($pdo)) {
        // PDO path
        $out['totalBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bins WHERE status = :s"); $stmt->execute([':s' => 'full']);
        $out['fullBins'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM janitors WHERE status = :s"); $stmt->execute([':s' => 'active']);
        $out['activeJanitors'] = (int)$stmt->fetchColumn();
        $out['totalCollections'] = (int)$pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn();
        $out['collectionsToday'] = (int)$pdo->query("SELECT COUNT(*) FROM collections WHERE DATE(collected_at) = CURDATE()")->fetchColumn();
        jsonResp($out);
    }

    // mysqli path
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM bins");
    $out['totalBins'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM bins WHERE status = 'full'");
    $out['fullBins'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM janitors WHERE status = 'active'");
    $out['activeJanitors'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM collections");
    $out['totalCollections'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

    $r = $conn->query("SELECT COUNT(*) AS cnt FROM collections WHERE DATE(collected_at) = CURDATE()");
    $out['collectionsToday'] = $r ? (int)$r->fetch_assoc()['cnt'] : 0; if ($r) $r->free();

    jsonResp($out);

} catch (Throwable $e) {
    $msg = $e->getMessage();
    // include debug to help you, but remove after fixing
    jsonResp(['success' => false, 'error' => $msg, 'debug' => $debugInfo]);
}