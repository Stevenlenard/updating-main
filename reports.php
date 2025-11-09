<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}

// ---------------------------
// Real-time stats from DB (server-side initial values)
// ---------------------------
$collectionsThisMonth = 0;
$pendingCount = 0;
$completedThisMonth = 0;
$reportsCount = 0;

try {
    // Collections this month
    $stmt = $pdo->query("
        SELECT COUNT(*) AS cnt
        FROM collections
        WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
          AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $row = $stmt->fetch();
    if ($row && isset($row['cnt'])) {
        $collectionsThisMonth = (int)$row['cnt'];
    }
} catch (Exception $e) {
    error_log("[reports.php] collections this month query failed: " . $e->getMessage());
    $collectionsThisMonth = 0;
}

try {
    // Pending (total pending that need action)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM collections WHERE status = :status");
    $stmt->execute([':status' => 'pending']);
    $pendingCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log("[reports.php] pending count query failed: " . $e->getMessage());
    $pendingCount = 0;
}

try {
    // Completed this month
    $stmt = $pdo->query("
        SELECT COUNT(*) AS cnt
        FROM collections
        WHERE status = 'completed'
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
          AND MONTH(created_at) = MONTH(CURRENT_DATE())
    ");
    $row = $stmt->fetch();
    if ($row && isset($row['cnt'])) {
        $completedThisMonth = (int)$row['cnt'];
    }
} catch (Exception $e) {
    error_log("[reports.php] completed this month query failed: " . $e->getMessage());
    $completedThisMonth = 0;
}

try {
    // Reports total (simple count)
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM reports");
    $row = $stmt->fetch();
    if ($row && isset($row['cnt'])) {
        $reportsCount = (int)$row['cnt'];
    }
} catch (Exception $e) {
    error_log("[reports.php] reports count query failed: " . $e->getMessage());
    $reportsCount = 0;
}

// Fetch recent reports (server-side rendering fallback)
$reports = [];
try {
    $stmt = $pdo->query("
        SELECT
            report_id,
            name,
            type,
            created_at,
            status
        FROM reports
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $reports = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("[reports.php] Failed to load reports: " . $e->getMessage());
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Analytics - Trashbin Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin-dashboard.php">
        <span class="brand-circle me-2"><i class="fa-solid fa-trash-can"></i></span>
        <span class="d-none d-sm-inline">Trashbin Admin</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item me-2">
            <a class="nav-link position-relative" href="notifications.php" title="Notifications">
              <i class="fa-solid fa-bell"></i>
              <span class="badge rounded-pill bg-danger position-absolute translate-middle" id="notificationCount" style="top:8px; left:18px; display:none;">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="profile.php" title="My Profile">
              <i class="fa-solid fa-user me-1"></i><span class="d-none d-sm-inline">Profile</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="fa-solid fa-right-from-bracket me-1"></i><span class="d-none d-sm-inline">Logout</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header d-none d-md-block">
        <h6 class="sidebar-title">Menu</h6>
      </div>
      <a href="admin-dashboard.php" class="sidebar-item">
        <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="bins.php" class="sidebar-item">
        <i class="fa-solid fa-trash-alt"></i><span>Bins</span>
      </a>
      <a href="janitors.php" class="sidebar-item">
        <i class="fa-solid fa-users"></i><span>Maintenance Staff</span>
      </a>
      <a href="reports.php" class="sidebar-item active">
        <i class="fa-solid fa-chart-line"></i><span>Reports</span>
      </a>
      <a href="notifications.php" class="sidebar-item">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
      </a>
      <a href="profile.php" class="sidebar-item">
        <i class="fa-solid fa-user"></i><span>My Profile</span>
      </a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="section-header flex-column flex-md-row">
        <div>
          <h1 class="page-title">Reports & Analytics</h1>
          <p class="page-subtitle">View system reports and analytics</p>
        </div>
        <div class="d-flex gap-2 flex-column flex-md-row mt-3 mt-md-0">
          <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#createReportModal">
            <i class="fas fa-plus me-1"></i>Create Report
          </button>
          <button id="btnExport" class="btn btn-primary" onclick="exportReport()">
            <i class="fas fa-download me-1"></i>Export
          </button>
        </div>
      </div>

      <!-- Report Stats -->
      <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-trash-can"></i>
            </div>
            <div class="stat-content">
              <h6>Collections</h6>
              <h2 id="stat-collections"><?php echo htmlspecialchars($collectionsThisMonth, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>This month</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
              <h6>Pending</h6>
              <h2 id="stat-pending"><?php echo htmlspecialchars($pendingCount, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>Need action</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
              <h6>Completed</h6>
              <h2 id="stat-completed"><?php echo htmlspecialchars($completedThisMonth, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>This month</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
              <h6>Reports</h6>
              <h2 id="stat-reports"><?php echo htmlspecialchars($reportsCount, ENT_QUOTES, 'UTF-8'); ?></h2>
              <small>Generated</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Reports -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Reports</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Report Name</th>
                  <th class="d-none d-md-table-cell">Type</th>
                  <th class="d-none d-lg-table-cell">Date Created</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="reportsTableBody">
                <?php if (empty($reports)): ?>
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No reports found</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($reports as $r): ?>
                    <?php
                      $id = (int)($r['report_id'] ?? 0);
                      $name = htmlspecialchars($r['name'] ?? 'Unnamed Report', ENT_QUOTES, 'UTF-8');
                      $type = htmlspecialchars($r['type'] ?? '', ENT_QUOTES, 'UTF-8');
                      $created = $r['created_at'] ?? null;
                      $dateFormatted = $created ? date('M d, Y H:i', strtotime($created)) : '-';
                      $status = htmlspecialchars($r['status'] ?? 'unknown', ENT_QUOTES, 'UTF-8');

                      // simple label classes
                      $statusClass = 'badge bg-secondary';
                      if (strtolower($status) === 'completed') $statusClass = 'badge bg-success';
                      if (strtolower($status) === 'pending') $statusClass = 'badge bg-warning text-dark';
                      if (strtolower($status) === 'failed') $statusClass = 'badge bg-danger';
                    ?>
                    <tr>
                      <td><?php echo $name; ?></td>
                      <td class="d-none d-md-table-cell"><?php echo ucfirst($type); ?></td>
                      <td class="d-none d-lg-table-cell"><?php echo $dateFormatted; ?></td>
                      <td><span class="<?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span></td>
                      <td class="text-end">
                        <div class="btn-group" role="group" aria-label="Actions">
                          <a href="view-report.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">View</a>
                          <a href="download-report.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">Download</a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Create Report Modal -->
  <div class="modal fade" id="createReportModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Report</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="createReportForm">
            <div class="mb-3">
              <label class="form-label">Report Name</label>
              <input type="text" class="form-control" id="reportName" placeholder="Enter report name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Report Type</label>
              <select class="form-select" id="reportType" required>
                <option value="">Select type</option>
                <option value="collections">Collections Report</option>
                <option value="performance">Janitor Performance</option>
                <option value="bins">Bin Status Report</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">From Date</label>
              <input type="date" class="form-control" id="reportFromDate">
            </div>
            <div class="mb-3">
              <label class="form-label">To Date</label>
              <input type="date" class="form-control" id="reportToDate">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="generateReport()">Generate Report</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>

  <!-- Reports logic (AJAX) - expects js/reports.js and api endpoints in api/ -->
  <script src="js/reports.js"></script>

  <script>
    // ensure initial load if js/reports.js is not yet loaded or defines loadReports later
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof loadReports === 'function') {
        loadReports();
      } else {
        // fallback: try again shortly (gives js/reports.js time to load)
        setTimeout(function() {
          if (typeof loadReports === 'function') loadReports();
        }, 200);
      }
    });

    function generateReport() {
      const name = document.getElementById('reportName').value;
      const type = document.getElementById('reportType').value;
      const fromDate = document.getElementById('reportFromDate').value;
      const toDate = document.getElementById('reportToDate').value;
      
      console.log('Generate report:', {name, type, fromDate, toDate});
      alert('Report generation started!');
      bootstrap.Modal.getInstance(document.getElementById('createReportModal')).hide();
    }

    // Export implementation: POSTs a small form to export_reports.php which returns a download.
    // This includes optional filters (report type, from/to dates) taken from the modal inputs.
    function exportReport() {
      // Gather filter values from modal inputs (if user set them)
      const type = document.getElementById('reportType') ? document.getElementById('reportType').value : '';
      const fromDate = document.getElementById('reportFromDate') ? document.getElementById('reportFromDate').value : '';
      const toDate = document.getElementById('reportToDate') ? document.getElementById('reportToDate').value : '';

      // Build and submit a POST form to trigger the download
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'export_reports.php';
      form.style.display = 'none';

      if (type) {
        const inputType = document.createElement('input');
        inputType.type = 'hidden';
        inputType.name = 'type';
        inputType.value = type;
        form.appendChild(inputType);
      }

      if (fromDate) {
        const inputFrom = document.createElement('input');
        inputFrom.type = 'hidden';
        inputFrom.name = 'from_date';
        inputFrom.value = fromDate;
        form.appendChild(inputFrom);
      }

      if (toDate) {
        const inputTo = document.createElement('input');
        inputTo.type = 'hidden';
        inputTo.name = 'to_date';
        inputTo.value = toDate;
        form.appendChild(inputTo);
      }

      document.body.appendChild(form);

      // Optionally show a small UX change: disable export button briefly
      const btn = document.getElementById('btnExport');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Preparing...';
      }

      // Submit the form to trigger the browser download
      form.submit();

      // Clean up after a short delay (download is handled by browser)
      setTimeout(function() {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-download me-1"></i>Export';
        }
        form.remove();
      }, 1500);
    }
  </script>
</body>
</html>