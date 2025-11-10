<?php
// includes/get-dashboard-data.php
// Returns dashboard counts and latest bins for the admin dashboard
// Expects includes/config.php to create $conn (mysqli) or $pdo (PDO). Optional isLoggedIn/isAdmin helpers allowed.

require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Helper sendJSON if not provided
if (!function_exists('sendJSON')) {
    function sendJSON($payload) {
        echo json_encode($payload);
        exit;
    }
}

// Basic auth guard if helper exists
if (function_exists('isLoggedIn') && function_exists('isAdmin')) {
    if (!isLoggedIn() || !isAdmin()) {
        sendJSON(['success' => false, 'message' => 'Unauthorized']);
    }
}

try {
    $out = [
        'success' => true,
        'totalBins' => 0,
        'fullBins' => 0,
        'activeJanitors' => 0,
        'totalCollections' => 0,
        'collectionsToday' => 0,
        'bins' => [],
    ];

    // mysqli path
    if (isset($conn) && $conn instanceof mysqli) {
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

        // bins list (join janitors for assigned name, get last emptied time)
        $binsQuery = "
            SELECT 
                b.bin_id,
                b.bin_code,
                b.location,
                b.type,
                b.status,
                b.capacity,
                b.assigned_to,
                CONCAT(j.first_name, ' ', j.last_name) AS assigned_to_name,
                MAX(c.collected_at) AS last_emptied
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            LEFT JOIN collections c ON b.bin_id = c.bin_id
            GROUP BY b.bin_id
            ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
            LIMIT 100
        ";
        $res = $conn->query($binsQuery);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $out['bins'][] = $row;
            }
            $res->free();
        }
    }
    // PDO path
    elseif (isset($pdo) && $pdo instanceof PDO) {
        $out['totalBins'] = (int)$pdo->query("SELECT COUNT(*) FROM bins")->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bins WHERE status = :s"); $stmt->execute([':s' => 'full']);
        $out['fullBins'] = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM janitors WHERE status = :s"); $stmt->execute([':s' => 'active']);
        $out['activeJanitors'] = (int)$stmt->fetchColumn();
        $out['totalCollections'] = (int)$pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn();
        $out['collectionsToday'] = (int)$pdo->query("SELECT COUNT(*) FROM collections WHERE DATE(collected_at) = CURDATE()")->fetchColumn();

        $binsQuery = "
            SELECT 
                b.bin_id,
                b.bin_code,
                b.location,
                b.type,
                b.status,
                b.capacity,
                b.assigned_to,
                CONCAT(j.first_name, ' ', j.last_name) AS assigned_to_name,
                MAX(c.collected_at) AS last_emptied
            FROM bins b
            LEFT JOIN janitors j ON b.assigned_to = j.janitor_id
            LEFT JOIN collections c ON b.bin_id = c.bin_id
            GROUP BY b.bin_id
            ORDER BY FIELD(b.status, 'full','needs_attention','in_progress','empty','out_of_service'), b.capacity DESC
            LIMIT 100
        ";
        $stmt = $pdo->query($binsQuery);
        if ($stmt) {
            $out['bins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'No database connection available']);
    }

    sendJSON($out);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}