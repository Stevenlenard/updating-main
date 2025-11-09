<?php
require_once 'includes/config.php';

// allow both admin and janitor; redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit;
}

$userid = getCurrentUserId();
$role = 'janitor';
if (isAdmin()) $role = 'admin';

// DEV mode to expose more info in responses (set false in production)
if (!defined('DEV_MODE')) define('DEV_MODE', false);

// helper: escape output
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Load user record from correct table
$user = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'created_at' => '',
];
try {
    if ($role === 'admin') {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ? LIMIT 1");
            $stmt->execute([$userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;
        } else {
            $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ? LIMIT 1");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc() ?: $user;
            $stmt->close();
        }
    } else {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("SELECT * FROM janitors WHERE janitor_id = ? LIMIT 1");
            $stmt->execute([$userid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;
        } else {
            $stmt = $conn->prepare("SELECT * FROM janitors WHERE janitor_id = ? LIMIT 1");
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc() ?: $user;
            $stmt->close();
        }
    }
} catch (Exception $e) {
    error_log("[profile] failed to load user: " . $e->getMessage());
}

// Handle AJAX POST actions: update_profile, change_password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'update_profile') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name  = trim($_POST['last_name'] ?? '');
            $email      = trim($_POST['email'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');

            if ($first_name === '' || $last_name === '' || $email === '') {
                throw new Exception('First name, last name and email are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address.');
            }

            // check uniqueness within table
            if ($role === 'admin') {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE email = ? AND admin_id != ? LIMIT 1");
                    $stmt->execute([$email, $userid]);
                    if ($stmt->fetch()) throw new Exception('Email already in use.');
                } else {
                    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE email = ? AND admin_id != ? LIMIT 1");
                    $stmt->bind_param("si", $email, $userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) { $stmt->close(); throw new Exception('Email already in use.'); }
                    $stmt->close();
                }
            } else {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("SELECT janitor_id FROM janitors WHERE email = ? AND janitor_id != ? LIMIT 1");
                    $stmt->execute([$email, $userid]);
                    if ($stmt->fetch()) throw new Exception('Email already in use.');
                } else {
                    $stmt = $conn->prepare("SELECT janitor_id FROM janitors WHERE email = ? AND janitor_id != ? LIMIT 1");
                    $stmt->bind_param("si", $email, $userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) { $stmt->close(); throw new Exception('Email already in use.'); }
                    $stmt->close();
                }
            }

            // perform update
            if ($role === 'admin') {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE admins SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, updated_at = NOW() WHERE admin_id = :id");
                    $stmt->execute([':fn'=>$first_name, ':ln'=>$last_name, ':email'=>$email, ':phone'=>$phone, ':id'=>$userid]);
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE admin_id = ?");
                    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $userid);
                    $stmt->execute();
                    if ($stmt->errno) {
                        throw new Exception('DB error: ' . $stmt->error);
                    }
                    $stmt->close();
                }
            } else {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE janitors SET first_name = :fn, last_name = :ln, email = :email, phone = :phone, updated_at = NOW() WHERE janitor_id = :id");
                    $stmt->execute([':fn'=>$first_name, ':ln'=>$last_name, ':email'=>$email, ':phone'=>$phone, ':id'=>$userid]);
                } else {
                    $stmt = $conn->prepare("UPDATE janitors SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE janitor_id = ?");
                    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $userid);
                    $stmt->execute();
                    if ($stmt->errno) {
                        throw new Exception('DB error: ' . $stmt->error);
                    }
                    $stmt->close();
                }
            }

            // update session display name if present
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['name'] = trim($first_name . ' ' . $last_name);

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            exit;
        }

        if ($action === 'change_password') {
            $current_password = trim($_POST['current_password'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                throw new Exception('All password fields are required.');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('New password and confirmation do not match.');
            }
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters.');
            }

            // fetch stored password
            if ($role === 'admin') {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("SELECT password FROM admins WHERE admin_id = ? LIMIT 1");
                    $stmt->execute([$userid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ? LIMIT 1");
                    $stmt->bind_param("i", $userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                }
            } else {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("SELECT password FROM janitors WHERE janitor_id = ? LIMIT 1");
                    $stmt->execute([$userid]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $stmt = $conn->prepare("SELECT password FROM janitors WHERE janitor_id = ? LIMIT 1");
                    $stmt->bind_param("i", $userid);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $row = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                }
            }

            $stored = $row['password'] ?? '';
            if ($stored === '') {
                throw new Exception('Stored password not found for your account.');
            }

            $verified = false;
            // verify password (password_hash)
            if (password_verify($current_password, $stored)) {
                $verified = true;
            } else {
                // legacy MD5 fallback
                if (strlen($stored) === 32 && md5($current_password) === $stored) {
                    $verified = true;
                }
            }

            if (!$verified) {
                throw new Exception('Current password is incorrect.');
            }

            // hash new password
            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            if ($newHash === false) {
                throw new Exception('Failed to hash password.');
            }

            // update DB with new hash and check affected rows
            if ($role === 'admin') {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE admins SET password = :h, updated_at = NOW() WHERE admin_id = :id");
                    $stmt->execute([':h' => $newHash, ':id' => $userid]);
                    $affected = $stmt->rowCount();
                    // rowCount may be 0 if same hash, but unlikely since password changed
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?");
                    $stmt->bind_param("si", $newHash, $userid);
                    $stmt->execute();
                    if ($stmt->errno) {
                        throw new Exception('DB error: ' . $stmt->error);
                    }
                    $affected = $conn->affected_rows;
                    $stmt->close();
                }
            } else {
                if (isset($pdo) && $pdo instanceof PDO) {
                    $stmt = $pdo->prepare("UPDATE janitors SET password = :h, updated_at = NOW() WHERE janitor_id = :id");
                    $stmt->execute([':h' => $newHash, ':id' => $userid]);
                    $affected = $stmt->rowCount();
                } else {
                    $stmt = $conn->prepare("UPDATE janitors SET password = ?, updated_at = NOW() WHERE janitor_id = ?");
                    $stmt->bind_param("si", $newHash, $userid);
                    $stmt->execute();
                    if ($stmt->errno) {
                        throw new Exception('DB error: ' . $stmt->error);
                    }
                    $affected = $conn->affected_rows;
                    $stmt->close();
                }
            }

            if (empty($affected) && !$role) {
                // shouldn't happen, but handle gracefully
                throw new Exception('Password update did not affect any rows.');
            }

            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            exit;
        }

        throw new Exception('Unknown action');
    } catch (Exception $e) {
        // log detailed error for debugging
        error_log('[profile.php] action=' . ($action ?? '') . ' error: ' . $e->getMessage());
        $msg = $e->getMessage();
        if (!DEV_MODE) {
            // hide technical details in production
            if ($action === 'change_password') $msg = 'Unable to update password. Please try again.';
            elseif ($action === 'update_profile') $msg = 'Unable to update profile. Please try again.';
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
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
      <a class="navbar-brand" href="<?php echo $role === 'admin' ? 'admin-dashboard.php' : 'janitor-dashboard.php'; ?>">
        <span class="brand-circle me-2"><i class="fa-solid fa-trash-can"></i></span>
        <span class="d-none d-sm-inline">Trashbin Profile</span>
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
              <?php
                $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                if ($displayName === '') $displayName = ($user['email'] ?? 'User');
                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=0D6EFD&color=fff&size=150';
              ?>
              <img id="profileImg" src="<?php echo e($avatarUrl); ?>" 
                   alt="Profile Picture" class="profile-picture">
              <input type="file" id="photoInput" accept=".png,.jpg,.jpeg" style="display: none;">
              <button type="button" class="profile-edit-btn" id="changePhotoBtn" title="Change Photo">
                <i class="fa-solid fa-camera"></i>
              </button>
            </div>
            <div class="profile-info">
              <h2 class="profile-name" id="profileName"><?php echo e($displayName); ?></h2>
              <p class="profile-role" id="profileRole"><?php echo $role === 'admin' ? 'System Administrator' : 'Maintenance Staff'; ?></p>
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
                <span class="stat-value"><?php echo e(date('Y', strtotime($user['created_at'] ?? date('Y-m-d')))); ?></span>
              </div>
            </div>

            <div class="profile-menu-card">
              <h6 class="menu-title">Settings</h6>
              <a href="#personal-info" class="profile-menu-item active" onclick="showProfileTab('personal-info', this); return false;">
                <i class="fa-solid fa-user"></i>
                <span>Personal Info</span>
              </a>
              <a href="#change-password" class="profile-menu-item" onclick="showProfileTab('change-password', this); return false;">
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
                    <div id="personalInfoAlert" class="alert alert-message" style="display:none"></div>
                    <form id="personalInfoForm">
                      <input type="hidden" name="action" value="update_profile">
                      <div class="form-row">
                        <div class="form-group">
                          <label class="form-label">First Name</label>
                          <input type="text" class="form-control" id="firstName" name="first_name" value="<?php echo e($user['first_name'] ?? ''); ?>" required>
                          <div class="validation-message"></div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">Last Name</label>
                          <input type="text" class="form-control" id="lastName" name="last_name" value="<?php echo e($user['last_name'] ?? ''); ?>" required>
                          <div class="validation-message"></div>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" required>
                        <div class="validation-message"></div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phoneNumber" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>">
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg w-100" id="saveProfileBtn">
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
                    <div id="passwordAlert" class="alert alert-message" style="display:none"></div>
                    <form id="changePasswordForm">
                      <input type="hidden" name="action" value="change_password">
                      <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="currentPassword" name="current_password" placeholder="Enter current password" required>
                          <button type="button" class="password-toggle-btn" data-target="#currentPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                        <div class="validation-message"></div>
                      </div>
                      <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="password-input-container">
                          <input type="password" class="form-control password-input" id="newPassword" name="new_password" placeholder="Enter new password" required>
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
                          <input type="password" class="form-control password-input" id="confirmNewPassword" name="confirm_password" placeholder="Confirm new password" required>
                          <button type="button" class="password-toggle-btn" data-target="#confirmNewPassword">
                            <i class="fa-solid fa-eye"></i>
                          </button>
                        </div>
                        <div class="validation-message"></div>
                      </div>
                      <button type="submit" class="btn btn-primary btn-lg w-100" id="changePasswordBtn">
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
  <script src="js/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // set displayed name/role already rendered server-side; no extra load needed

      // Change photo button
      document.getElementById('changePhotoBtn').addEventListener('click', function() {
        document.getElementById('photoInput').click();
      });

      // Toggle password visibility
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

      // Personal info update
      $('#personalInfoForm').on('submit', function(e) {
        e.preventDefault();
        $('#saveProfileBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...');
        const data = $(this).serialize();
        $.post('profile.php', data, function(resp) {
          if (resp && resp.success) {
            $('#personalInfoAlert').removeClass().addClass('alert alert-success').text(resp.message).show();
            // update displayed name
            const newName = $('#firstName').val().trim() + ' ' + $('#lastName').val().trim();
            $('#profileName').text(newName);
          } else {
            $('#personalInfoAlert').removeClass().addClass('alert alert-danger').text(resp.message || 'Update failed').show();
          }
        }, 'json').fail(function(xhr){
          let msg = 'Server error';
          try { msg = xhr.responseJSON.message || msg; } catch(e){}
          $('#personalInfoAlert').removeClass().addClass('alert alert-danger').text(msg).show();
        }).always(function(){
          $('#saveProfileBtn').prop('disabled', false).html('<i class="fa-solid fa-save me-2"></i>Save Changes');
        });
      });

      // Change password
      $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        $('#changePasswordBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...');
        const data = $(this).serialize();
        $.post('profile.php', data, function(resp) {
          if (resp && resp.success) {
            $('#passwordAlert').removeClass().addClass('alert alert-success').text(resp.message).show();
            $('#changePasswordForm')[0].reset();
          } else {
            $('#passwordAlert').removeClass().addClass('alert alert-danger').text(resp.message || 'Password change failed').show();
          }
        }, 'json').fail(function(xhr){
          let msg = 'Server error';
          try { msg = xhr.responseJSON.message || msg; } catch(e){}
          $('#passwordAlert').removeClass().addClass('alert alert-danger').text(msg).show();
        }).always(function(){
          $('#changePasswordBtn').prop('disabled', false).html('<i class="fa-solid fa-lock me-2"></i>Update Password');
        });
      });
    });

    // show profile tab and set active menu item
    function showProfileTab(tabName, el) {
      document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.classList.remove('show', 'active');
      });
      const tab = document.getElementById(tabName);
      if (tab) tab.classList.add('show', 'active');

      document.querySelectorAll('.profile-menu-item').forEach(item => item.classList.remove('active'));
      if (el) el.classList.add('active');
    }
  </script>
</body>
</html>