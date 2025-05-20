<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Prepare profile image
$profile_img = '../assets/images/default-profile.jpg';
// Check for profile image
$profile_image = !empty($user['profile_picture']) ? $user['profile_picture'] : '';

if (!empty($profile_image)) {
    // Check if file exists - supports both old and new directory structure
    if (strpos($profile_image, 'users/') === 0) {
        // New structure - user specific directory
        if (file_exists('../uploads/' . $profile_image)) {
            $profile_img = '../uploads/' . $profile_image;
        }
    } else {
        // Legacy structure
        if (file_exists('../uploads/profiles/' . $profile_image)) {
            $profile_img = '../uploads/profiles/' . $profile_image;
        } else if (file_exists('../uploads/profile/' . $profile_image)) {
            $profile_img = '../uploads/profile/' . $profile_image;
        }
    }
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?> - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <?php if (isset($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo $page_specific_css; ?>">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: #042167;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --border-color: #e3e6f0;
            --warning-color: #f6c23e;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fc;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand img {
            height: 35px;
            margin-right: 10px;
        }
        
        .sidebar-toggle {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .sidebar-nav {
            padding: 15px 0;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            height: 0;
            margin: 10px 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-heading {
            padding: 0 15px;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
            padding: 20px;
        }
        
        .main-content.expanded {
            margin-left: 70px;
        }
        
        .topbar {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .page-title {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .user-dropdown-toggle:hover {
            background-color: var(--light-color);
        }
        
        .user-dropdown-toggle img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .user-dropdown-toggle span {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .user-dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }
        
        .user-dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 10px 15px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-color);
        }
        
        .dropdown-divider {
            height: 0;
            margin: 5px 0;
            border-top: 1px solid var(--border-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar.expanded {
                width: 250px;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .main-content.collapsed {
                margin-left: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-brand">
                    <img src="../assets/images/logo-Visafy-light.png" alt="Visafy Logo">
                    <span class="brand-text">Admin</span>
                </a>
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-heading">Eligibility</div>
                
                <a href="eligibility-calculator.php" class="nav-item <?php echo $current_page == 'eligibility-calculator' ? 'active' : ''; ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Eligibility Calculator</span>
                </a>
                
                <a href="manage-questions.php" class="nav-item <?php echo $current_page == 'manage-questions' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage Questions</span>
                </a>
                
                <a href="assessment-results.php" class="nav-item <?php echo $current_page == 'assessment-results' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Assessment Results</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-heading">Consultants</div>
                
                <a href="consultants.php" class="nav-item <?php echo $current_page == 'consultants' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>All Consultants</span>
                </a>
                
                <a href="verify-consultants.php" class="nav-item <?php echo $current_page == 'verify-consultants' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i>
                    <span>Verify Consultants</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-heading">System</div>
                
                <a href="settings.php" class="nav-item <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <div class="topbar">
                <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></h1>
                
                <div class="user-dropdown">
                    <div class="user-dropdown-toggle" id="userDropdown">
                        <img src="<?php echo $profile_img; ?>" alt="Profile">
                        <span><?php echo htmlspecialchars($_SESSION["first_name"] . ' ' . $_SESSION["last_name"]); ?></span>
                    </div>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user fa-sm fa-fw mr-2"></i> Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog fa-sm fa-fw mr-2"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Page content starts here -->
        </main>
    </div>
</body>
</html> 