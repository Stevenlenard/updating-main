<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - Trashbin Admin</title>
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
      <a href="reports.php" class="sidebar-item">
        <i class="fa-solid fa-chart-line"></i><span>Reports</span>
      </a>
      <a href="notifications.php" class="sidebar-item">
        <i class="fa-solid fa-bell"></i><span>Notifications</span>
      </a>
      <a href="profile.php" class="sidebar-item active">
        <i class="fa-solid fa-user"></i><span>My Profile</span>
      </a>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <div class="section-header">
        <div>
          <h1 class="page-title">My Profile</h1>
          <p class="page-subtitle">Manage your personal information and settings</p>
        </div>
      </div>

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
          <!-- Left Column -->
          <div class="profile-sidebar">
            <div class="profile-stats-card">
              <h6 class="stats-title">Quick Stats</h6>
              <div class="stat-item">
                <span class="stat-label">Total Bins</span>
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
                <span>Personal Info</span>
              </a>
              <a href="#change-password" class="profile-menu-item" onclick="showProfileTab('change-password'); return false;">
                <i class="fa-solid fa-key"></i>
                <span>Change Password</span>
              </a>
            </div>
          </div>

          <!-- Right Column -->
          <div class="profile-main">
            <div class="tab-content">
              <!-- Personal Information Tab -->
              <div class="tab-pane fade show active" id="personal-info">
                <div class="profile-form-card">
                  <div class="form-card-header">
                    <h5><i class="fa-solid fa-user-circle me-2"></i>Personal Information</h5>
                  </div>
                  <div class="form-card-body">
                    <div id="personalInfoAlert" class="alert alert-message"></div>
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
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="admin@example.com" required>
                        <div class="validation-message"></div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phoneNumber" value="+1 (555) 000-0000" required>
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg w-100">
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
                    <div id="passwordAlert" class="alert alert-message"></div>
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
                        <label class="form-label">Confirm Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="confirmNewPassword" placeholder="Confirm new password" required>
                          <button type="button" class="password-toggle-btn" data-target="#confirmNewPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fa-solid fa-lock me-2"></i>Update Password
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/database.js"></script>
  <script src="js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      loadProfile();
      
      document.getElementById('personalInfoForm').addEventListener('submit', updateProfile);
      
      document.getElementById('changePhotoBtn').addEventListener('click', function() {
        document.getElementById('photoInput').click();
      });

      document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const input = document.querySelector(this.getAttribute('data-target'));
          const icon = this.querySelector('i');
          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          }
        });
      });

      document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Change password');
        alert('Password change feature coming soon');
      });
    });

    function showProfileTab(tabName) {
      document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.classList.remove('show', 'active');
      });
      document.getElementById(tabName).classList.add('show', 'active');
      
      document.querySelectorAll('.profile-menu-item').forEach(item => {
        item.classList.remove('active');
      });
      event.target.closest('.profile-menu-item').classList.add('active');
    }
  </script>
</body>
</html>
