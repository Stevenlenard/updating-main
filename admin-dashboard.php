<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: admin-login.php');
    exit;
}

// Check if user is admin
if (!isAdmin()) {
    header('Location: janitor-dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Trashbin Management</title>
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
            <a class="nav-link" href="logout.php" title="Logout">
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
      <a href="admin-dashboard.php" class="sidebar-item active">
        <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
      </a>
      <a href="bins.php" class="sidebar-item">
        <i class="fa-solid fa-trash-alt"></i><span>Bins</span>
      </a>
      <a href="janitors.php" class="sidebar-item">
        <i class="fa-solid fa-users"></i><span>Maintenance Staff</span>
      </a>
      <a href="reports.php" class="sidebar-item">
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
      <div class="section-header">
        <div>
          <h1 class="page-title">Dashboard</h1>
          <p class="page-subtitle">Welcome back! Here's your system overview.</p>
        </div>
        <div class="btn-group d-none d-md-flex">
          <button class="btn btn-outline-secondary btn-sm active" onclick="filterDashboard('today')">Today</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="filterDashboard('week')">Week</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="filterDashboard('month')">Month</button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fa-solid fa-trash-alt"></i>
            </div>
            <div class="stat-content">
              <h6>Total Bins</h6>
              <h2 id="totalBins">0</h2>
              <small>Active</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon warning">
              <i class="fa-solid fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
              <h6>Full Bins</h6>
              <h2 id="fullBins">0</h2>
              <small>Needs attention</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon success">
              <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-content">
              <h6>Active Janitors</h6>
              <h2 id="activeJanitors">0</h2>
              <small>On duty</small>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fa-solid fa-truck"></i>
            </div>
            <div class="stat-content">
              <h6>Collections</h6>
              <h2 id="collectionsToday">0</h2>
              <small>Today</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Bins Overview -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-trash-can me-2"></i>Bins Overview</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th>Bin ID</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th class="d-none d-md-table-cell">Last Emptied</th>
                  <th class="d-none d-lg-table-cell">Assigned To</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="binsTableBody">
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">No bins found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      loadDashboardData();
      setInterval(loadDashboardData, 30000); // Refresh every 30 seconds
    });
  </script>
</body>
</html>

      <!-- Bins Section -->
      <section id="binsSection" class="content-section" style="display:none;">
        <div class="section-header">
          <div>
            <h1 class="page-title">Bin Management</h1>
            <p class="page-subtitle">Manage all trashbins in the system</p>
          </div>
          <div class="d-flex gap-2">
            <div class="input-group" style="max-width: 300px;">
              <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
              <input type="text" class="form-control border-start-0 ps-0" id="searchBinsInput" placeholder="Search bins...">
            </div>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterBinsDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i>Filter
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterBinsDropdown">
                <li><a class="dropdown-item" href="#" data-filter="all">All Bins</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-filter="full">Full</a></li>
                <li><a class="dropdown-item" href="#" data-filter="empty">Empty</a></li>
                <li><a class="dropdown-item" href="#" data-filter="needs_attention">Needs Attention</a></li>
              </ul>
          </div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBinModal">
            <i class="fas fa-plus me-1"></i> Add New Bin
          </button>
        </div>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
              <thead>
                <tr>
                  <th>Bin ID</th>
                  <th>Location</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Capacity</th>
                    <th>Assigned To</th>
                    <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="allBinsTableBody">
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No bins found</td>
                  </tr>
              </tbody>
            </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Janitors Section -->
      <section id="janitorsSection" class="content-section" style="display:none;">
        <div class="section-header">
          <div>
            <h1 class="page-title">Janitor Management</h1>
            <p class="page-subtitle">Manage janitors and their assignments</p>
          </div>
          <div class="d-flex gap-2">
            <div class="input-group" style="max-width: 300px;">
              <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
              <input type="text" class="form-control border-start-0 ps-0" id="searchJanitorsInput" placeholder="Search janitors...">
            </div>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterJanitorsDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i>Filter
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterJanitorsDropdown">
                <li><a class="dropdown-item" href="#" data-filter="all">All Janitors</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-filter="active">Active</a></li>
                <li><a class="dropdown-item" href="#" data-filter="inactive">Inactive</a></li>
              </ul>
          </div>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJanitorModal">
            <i class="fas fa-plus me-1"></i> Add New Janitor
          </button>
        </div>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Assigned Bins</th>
                  <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="janitorsTableBody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No janitors found</td>
                  </tr>
              </tbody>
            </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Reports Section -->
      <section id="reportsSection" class="content-section" style="display:none;">
        <div class="section-header">
          <div>
            <h1 class="page-title">Reports & Analytics</h1>
            <p class="page-subtitle">View system reports and analytics</p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#createReportModal">
              <i class="fas fa-plus me-1"></i> Create Report
            </button>
            <button class="btn btn-primary" onclick="exportReport()">
            <i class="fas fa-download me-1"></i> Export Report
          </button>
          </div>
        </div>

        <!-- Report Filters -->
        <div class="row g-4 mb-4">
          <div class="col-md-3">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-trash-can"></i>
              </div>
              <div class="stat-content">
                <h6>Total Collections</h6>
                <h2>156</h2>
                <small>This month</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card">
              <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stat-content">
                <h6>Pending Collections</h6>
                <h2>23</h2>
                <small>Needs action</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card">
              <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="stat-content">
                <h6>Completed</h6>
                <h2>133</h2>
                <small>This month</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-calendar"></i>
              </div>
              <div class="stat-content">
                <h6>Reports Generated</h6>
                <h2>24</h2>
                <small>This month</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Report Charts -->
        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Collection Trends</h5>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary active" onclick="filterChart('week', 'trend')">Week</button>
                  <button class="btn btn-outline-secondary" onclick="filterChart('month', 'trend')">Month</button>
                  <button class="btn btn-outline-secondary" onclick="filterChart('year', 'trend')">Year</button>
                </div>
              </div>
              <div class="card-body" style="height: 250px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0f4f8 0%, #f8fafc 50%, #f0f7ff 100%);">
                <div class="text-center text-muted">
                  <i class="fas fa-chart-area" style="font-size: 48px; opacity: 0.3;"></i>
                  <p class="mt-2">Chart visualization will be implemented with Chart.js</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Collections by Type</h5>
              </div>
              <div class="card-body" style="height: 250px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f0f4f8 0%, #f8fafc 50%, #f0f7ff 100%);">
                <div class="text-center text-muted">
                  <i class="fas fa-chart-pie" style="font-size: 48px; opacity: 0.3;"></i>
                  <p class="mt-2">Pie chart visualization</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Reports Table -->
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
                    <th>Type</th>
                    <th>Date Created</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="reportsTableBody">
                  <tr>
                    <td>Monthly Collections Report</td>
                    <td>Collections</td>
                    <td>2024-01-15</td>
                    <td>Admin</td>
                    <td><span class="badge badge-success">Completed</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-link" onclick="viewReport('monthly-collections')"><i class="fas fa-eye me-1"></i>View</button>
                      <button class="btn btn-sm btn-link" onclick="downloadReport('monthly-collections')"><i class="fas fa-download me-1"></i>Download</button>
                    </td>
                  </tr>
                  <tr>
                    <td>Janitor Performance Report</td>
                    <td>Performance</td>
                    <td>2024-01-14</td>
                    <td>Admin</td>
                    <td><span class="badge badge-success">Completed</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-link" onclick="viewReport('janitor-performance')"><i class="fas fa-eye me-1"></i>View</button>
                      <button class="btn btn-sm btn-link" onclick="downloadReport('janitor-performance')"><i class="fas fa-download me-1"></i>Download</button>
                    </td>
                  </tr>
                  <tr>
                    <td>Bin Status Report</td>
                    <td>Status</td>
                    <td>2024-01-13</td>
                    <td>Admin</td>
                    <td><span class="badge badge-warning">Processing</span></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-link" disabled><i class="fas fa-eye me-1"></i>View</button>
                      <button class="btn btn-sm btn-link" disabled><i class="fas fa-download me-1"></i>Download</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- Notifications Section -->
      <section id="notificationsSection" class="content-section" style="display:none;">
        <div class="section-header">
          <div>
            <h1 class="page-title">Notifications & Logs</h1>
            <p class="page-subtitle">System notifications and activity logs</p>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="alertSoundSwitch" checked>
              <label class="form-check-label" for="alertSoundSwitch">Alert Sound</label>
            </div>
            <div class="dropdown">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterNotificationsDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i>Filter
          </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterNotificationsDropdown">
                <li><a class="dropdown-item active" href="#" data-filter="all">All Notifications</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-filter="critical">Critical</a></li>
                <li><a class="dropdown-item" href="#" data-filter="warning">Warning</a></li>
                <li><a class="dropdown-item" href="#" data-filter="info">Info</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Time</th>
                    <th>Bin ID</th>
                    <th>Location</th>
                    <th>Alert Type</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="notificationsTableBody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No notifications found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllReadBtn"><i class="fas fa-check-double me-1"></i>Mark All as Read</button>
              <button type="button" class="btn btn-sm btn-outline-danger" id="clearNotificationsBtn"><i class="fas fa-trash-alt me-1"></i>Clear All</button>
            </div>
          </div>
        </div>
      </section>

      <!-- My Profile Section -->
      <section id="myProfileSection" class="content-section" style="display:none;">
        <div class="section-header">
          <div>
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your personal information and settings.</p>
          </div>
        </div>
        
        <!-- Enhanced profile layout with premium card design and better spacing -->
        <div class="profile-container">
          <!-- Profile Header Card -->
          <div class="profile-header-card">
            <div class="profile-header-content">
              <div class="profile-picture-wrapper">
                <img id="profileImg" src="https://ui-avatars.com/api/?name=Admin+User&background=0D6EFD&color=fff&size=150" 
                     alt="Profile Picture" class="profile-picture">
                <input type="file" id="photoInput" accept=".png,.jpg,.jpeg" style="display: none;">
                <button type="button" class="profile-edit-btn" id="changePhotoBtn" title="Change Photo">
                  <i class="fa-solid fa-camera"></i>
                </button>
              </div>
              <div class="profile-info">
                <h2 class="profile-name" id="profileName">Admin User</h2>
                <p class="profile-role" id="profileRole">System Administrator</p>
                <div id="photoMessage" class="validation-message"></div>
              </div>
            </div>
          </div>

          <!-- Profile Content Grid -->
          <div class="profile-content-grid">
            <!-- Left Column - Quick Stats -->
            <div class="profile-sidebar">
              <div class="profile-stats-card">
                <h6 class="stats-title">Quick Stats</h6>
                <div class="stat-item">
                  <span class="stat-label">Total Bins Managed</span>
                  <span class="stat-value">48</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Active Janitors</span>
                  <span class="stat-value">12</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Member Since</span>
                  <span class="stat-value">2024</span>
                </div>
              </div>

              <div class="profile-menu-card">
                <h6 class="menu-title">Settings</h6>
                <a href="#personal-info" class="profile-menu-item active" onclick="showProfileTab('personal-info'); return false;">
                  <i class="fa-solid fa-user"></i>
                  <span>Personal Information</span>
                </a>
                <a href="#change-password" class="profile-menu-item" onclick="showProfileTab('change-password'); return false;">
                  <i class="fa-solid fa-key"></i>
                  <span>Change Password</span>
                </a>
              </div>
            </div>

            <!-- Right Column - Forms -->
            <div class="profile-main">
              <div class="tab-content">
                <!-- Personal Information Tab -->
                <div class="tab-pane fade show active" id="personal-info">
                  <div class="profile-form-card">
                    <div class="form-card-header">
                      <h5><i class="fa-solid fa-user-circle me-2"></i>Personal Information</h5>
                    </div>
                    <div class="form-card-body">
                      <div id="personalInfoAlert" class="alert alert-message" role="alert"></div>
                      <form id="personalInfoForm">
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" value="Admin" required>
                            <div class="validation-message"></div>
                          </div>
                          <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" value="User" required>
                            <div class="validation-message"></div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Email Address</label>
                          <input type="email" class="form-control" id="email" value="admin@example.com" required>
                          <div class="validation-message"></div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Phone Number</label>
                          <input type="tel" class="form-control" id="phoneNumber" value="+1 (555) 000-0000" placeholder="11 digits">
                          <div class="validation-message"></div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Employee ID</label>
                          <input type="text" class="form-control" value="ADM-001" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                          <i class="fa-solid fa-save me-2"></i>Save Changes
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
                
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="change-password">
                  <div class="profile-form-card">
                    <div class="form-card-header">
                      <h5><i class="fa-solid fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="form-card-body">
                      <div id="passwordAlert" class="alert alert-message" role="alert"></div>
                      <form id="changePasswordForm">
                        <div class="form-group">
                          <label class="form-label">Current Password</label>
                          <div class="password-input-container">
                            <input type="password" class="form-control password-input" id="currentPassword" placeholder="Enter current password" required>
                            <button type="button" class="password-toggle-btn" data-target="#currentPassword">
                              <i class="fa-solid fa-eye"></i>
                            </button>
                          </div>
                          <div class="validation-message"></div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">New Password</label>
                          <div class="password-input-container">
                            <input type="password" class="form-control password-input" id="newPassword" placeholder="Enter new password" required>
                            <button type="button" class="password-toggle-btn" data-target="#newPassword">
                              <i class="fa-solid fa-eye"></i>
                            </button>
                          </div>
                          <div class="validation-message"></div>
                          <div class="password-strength">
                            <small>Password strength:</small>
                            <div class="strength-bar">
                              <div class="strength-fill"></div>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Confirm New Password</label>
                          <div class="password-input-container">
                            <input type="password" class="form-control password-input" id="confirmNewPassword" placeholder="Confirm new password" required>
                            <button type="button" class="password-toggle-btn" data-target="#confirmNewPassword">
                              <i class="fa-solid fa-eye"></i>
                            </button>
                          </div>
                          <div class="validation-message"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                          <i class="fa-solid fa-lock me-1"></i>Update Password
                        </button>
            </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Notifications Dropdown Panel -->
  <div class="modal fade" id="notificationsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Notifications</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <div id="notificationsPanel">
            <div class="text-center py-4 text-muted">
              <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.5;"></i>
              <p class="mt-2">No notifications</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Janitor Modal -->
  <div class="modal fade" id="editJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit Janitor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editJanitorForm">
            <input type="hidden" id="editJanitorId">
            <!-- Split full name into first name and last name fields -->
            <div class="form-row">
              <div class="form-group mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" id="editJanitorFirstName" required>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" id="editJanitorLastName" required>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveJanitorEdit()">
            <i class="fas fa-save me-1"></i>Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Report Modal -->
  <div class="modal fade" id="createReportModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Create New Report</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <form id="createReportForm">
            <div class="form-group mb-3">
              <label class="form-label">Report Name</label>
              <input type="text" class="form-control" id="reportName" placeholder="Enter report name" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Report Type</label>
              <select class="form-control form-select" id="reportType" required>
                <option value="collections">Collections</option>
                <option value="performance">Performance</option>
                <option value="status">Status</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="createReport()">
            <i class="fas fa-file-alt me-1"></i>Create Report
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Janitor Modal -->
  <div class="modal fade" id="addJanitorModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Janitor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addJanitorForm">
            <!-- Split full name into separate first name and last name fields -->
            <div class="form-row">
              <div class="form-group mb-3">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newJanitorFirstName" placeholder="Enter first name" required>
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="newJanitorLastName" placeholder="Enter last name" required>
              </div>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="newJanitorEmail" placeholder="Enter email address" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="newJanitorPhone" placeholder="Enter phone number" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newJanitorStatus" required>
                <option value="">Select status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned Bins</label>
              <input type="number" class="form-control" id="newJanitorBins" placeholder="Number of bins" value="0" min="0">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewJanitor()">
            <i class="fas fa-save me-1"></i>Add Janitor
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add New Bin Modal -->
  <div class="modal fade" id="addBinModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i>Add New Bin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="addBinForm">
            <div class="form-group mb-3">
              <label class="form-label">Bin ID <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newBinId" placeholder="e.g., BIN-001" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Location <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="newBinLocation" placeholder="Enter bin location" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newBinType" required>
                <option value="">Select type</option>
                <option value="General">General</option>
                <option value="Recyclable">Recyclable</option>
                <option value="Organic">Organic</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Capacity (%) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="newBinCapacity" placeholder="0-100" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <select class="form-control form-select" id="newBinStatus" required>
                <option value="">Select status</option>
                <option value="empty">Empty</option>
                <option value="full">Full</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned To</label>
              <input type="text" class="form-control" id="newBinAssignedTo" placeholder="Janitor name (optional)">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveNewBin()">
            <i class="fas fa-save me-1"></i>Add Bin
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Bin Modal -->
  <div class="modal fade" id="editBinModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Bin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editBinForm">
            <input type="hidden" id="editBinId">
            <div class="form-group mb-3">
              <label class="form-label">Bin ID</label>
              <input type="text" class="form-control" id="editBinIdDisplay" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" id="editBinLocation" required>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Type</label>
              <select class="form-control form-select" id="editBinType" required>
                <option value="General">General</option>
                <option value="Recyclable">Recyclable</option>
                <option value="Organic">Organic</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Status</label>
              <select class="form-control form-select" id="editBinStatus" required>
                <option value="empty">Empty</option>
                <option value="full">Full</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Assigned To</label>
              <input type="text" class="form-control" id="editBinAssignedTo" placeholder="Janitor name">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveBinEdit()">
            <i class="fas fa-save me-1"></i>Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Report Detail Modal -->
  <div class="modal fade" id="viewReportDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Report Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="reportDetailContent">
          <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/admin-dashboard.js"></script>

</body>
</html>
