<?php
require_once '../../includes/config.php';

// Only janitors should hit this endpoint (this file is under api/janitor)
if (!isJanitor()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized']);
}

$janitor_id = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$bin_id = isset($input['bin_id']) ? (int)$input['bin_id'] : null;
$status = isset($input['status']) ? trim($input['status']) : null;
$action_type = isset($input['action_type']) ? trim($input['action_type']) : null;
$notes = isset($input['notes']) ? trim($input['notes']) : null;

if (!$bin_id || !$status) {
    sendJSON(['success' => false, 'message' => 'Missing required fields']);
}

// Security rule: janitors are only allowed to set bin status to 'empty' via this endpoint.
// If a janitor attempts other status values, reject it.
if ($status !== 'empty') {
    sendJSON(['success' => false, 'message' => 'Janitors may only mark bins as empty']);
}

try {
    // Begin transaction (supports PDO or mysqli)
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->beginTransaction();
    } elseif (isset($conn)) {
        $conn->begin_transaction();
    }

    // NOTE: We intentionally do NOT block updates to bins that are not assigned to the janitor,
    // because UI shows all bins in Assigned view per request, and janitors should be able to mark any as empty.
    // If you want to enforce assignment checks in the future, re-introduce the SELECT check here.

    // Update bin status
    if (isset($pdo) && $pdo instanceof PDO) {
        $update = $pdo->prepare("UPDATE bins SET status = ?, updated_at = NOW() WHERE bin_id = ?");
        $update->execute([$status, $bin_id]);
    } else {
        $stmtU = $conn->prepare("UPDATE bins SET status = ?, updated_at = NOW() WHERE bin_id = ?");
        $stmtU->bind_param("si", $status, $bin_id);
        $stmtU->execute();
        $stmtU->close();
    }

    // Insert into bin_history if table exists (non-fatal)
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $hist = $pdo->prepare("INSERT INTO bin_history (bin_id, janitor_id, action_type, notes, created_at) VALUES (?, ?, ?, ?, NOW())");
            $hist->execute([$bin_id, $janitor_id, $action_type, $notes]);
        } else {
            if ($conn->query("SHOW TABLES LIKE 'bin_history'")->num_rows > 0) {
                $hist = $conn->prepare("INSERT INTO bin_history (bin_id, janitor_id, action_type, notes, created_at) VALUES (?, ?, ?, ?, NOW())");
                $hist->bind_param("iiss", $bin_id, $janitor_id, $action_type, $notes);
                $hist->execute();
                $hist->close();
            }
        }
    } catch (Exception $e) {
        // Non-fatal: log and continue
        error_log("[api/janitor/update-bin-stats] bin_history insert failed: " . $e->getMessage());
    }

    // Insert admin notification so admins get notified when janitor marks a bin empty
    try {
        // Fetch bin info for context (bin_code, location)
        $binCode = $binLocation = null;
        if (isset($pdo) && $pdo instanceof PDO) {
            $s = $pdo->prepare("SELECT bin_code, location FROM bins WHERE bin_id = ?");
            $s->execute([$bin_id]);
            $binInfo = $s->fetch(PDO::FETCH_ASSOC);
            if ($binInfo) {
                $binCode = $binInfo['bin_code'] ?? null;
                $binLocation = $binInfo['location'] ?? null;
            }
        } else {
            $s = $conn->prepare("SELECT bin_code, location FROM bins WHERE bin_id = ?");
            $s->bind_param("i", $bin_id);
            $s->execute();
            $res = $s->get_result();
            $binInfo = $res->fetch_assoc();
            if ($binInfo) {
                $binCode = $binInfo['bin_code'] ?? null;
                $binLocation = $binInfo['location'] ?? null;
            }
            $s->close();
        }

        $displayCode = $binCode ? $binCode : $bin_id;
        $locationText = $binLocation ? " ({$binLocation})" : "";

        $title = "Bin marked empty by janitor";
        $message = "Janitor #{$janitor_id} marked bin {$displayCode}{$locationText} as empty";

        // Insert notification (is_read = 0)
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmtN = $pdo->prepare("
                INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at)
                VALUES (:admin_id, :janitor_id, :bin_id, :type, :title, :message, 0, NOW())
            ");
            $stmtN->execute([
                ':admin_id' => null,
                ':janitor_id' => $janitor_id,
                ':bin_id' => $bin_id,
                ':type' => 'bin_update',
                ':title' => $title,
                ':message' => $message
            ]);
        } else {
            $res = $conn->query("SHOW TABLES LIKE 'notifications'");
            if ($res && $res->num_rows > 0) {
                $stmtN = $conn->prepare("
                    INSERT INTO notifications (admin_id, janitor_id, bin_id, notification_type, title, message, is_read, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
                ");
                if ($stmtN) {
                    $adminParam = null;
                    $typeParam = 'bin_update';
                    $titleParam = $title;
                    $messageParam = $message;
                    $stmtN->bind_param("iiisss", $adminParam, $janitor_id, $bin_id, $typeParam, $titleParam, $messageParam);
                    $stmtN->execute();
                    $stmtN->close();
                }
            }
        }
    } catch (Exception $e) {
        error_log("[api/janitor/update-bin-stats] notification insert failed: " . $e->getMessage());
    }

    // Commit transaction
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->commit();
    } elseif (isset($conn)) {
        $conn->commit();
    }

    sendJSON(['success' => true, 'message' => 'Bin status updated']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    } elseif (isset($conn)) {
        $conn->rollback();
    }
    error_log("[api/janitor/update-bin-stats] Exception: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'An error occurred while updating bin status']);
}