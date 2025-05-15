<?php
// Start session only if one isn't already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a consultant or team member
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["user_type"]) || ($_SESSION["user_type"] != 'consultant' && $_SESSION["user_type"] != 'member' && $_SESSION["user_type"] != 'admin')) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION["id"];
$user_type = $_SESSION["user_type"];

// Fetch user data
$stmt = $conn->prepare("SELECT u.*, o.name as organization_name FROM users u 
                        LEFT JOIN organizations o ON u.organization_id = o.id 
                        WHERE u.id = ? AND u.deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../../login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Get organization and membership data for consultants
$membership_plan = '';
$company_name = '';
$team_members_count = 0;
$max_team_members = 0;

if ($user_type == 'consultant') {
    $stmt = $conn->prepare("SELECT c.*, mp.name as plan_name, mp.max_team_members 
                           FROM consultants c 
                           JOIN membership_plans mp ON c.membership_plan_id = mp.id 
                           WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $consultant_result = $stmt->get_result();
    
    if ($consultant_result->num_rows > 0) {
        $consultant_data = $consultant_result->fetch_assoc();
        $membership_plan = $consultant_data['plan_name'];
        $company_name = $consultant_data['company_name'];
        $team_members_count = $consultant_data['team_members_count'];
        $max_team_members = $consultant_data['max_team_members'];
    }
    $stmt->close();
}

// If user is a team member, get the consultant they belong to
$belongs_to_consultant = null;
if ($user_type == 'member') {
    $stmt = $conn->prepare("SELECT tm.consultant_id, u.first_name, u.last_name 
                           FROM team_members tm 
                           JOIN users u ON tm.consultant_id = u.id 
                           WHERE tm.member_user_id = ? AND tm.invitation_status = 'accepted'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $team_result = $stmt->get_result();
    
    if ($team_result->num_rows > 0) {
        $belongs_to_consultant = $team_result->fetch_assoc();
    }
    $stmt->close();
}

// Check for unread notifications
// $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $notif_result = $stmt->get_result();
// $notification_count = $notif_result->fetch_assoc()['count'];
// $stmt->close();

// Get recent notifications (limit to 5)
// $stmt = $conn->prepare("SELECT id, title, content, is_read, created_at FROM notifications 
//                        WHERE user_id = ? AND is_read = 0 
//                        ORDER BY created_at DESC LIMIT 5");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $notifications = $stmt->get_result();
// $notifications_list = [];
// while ($notification = $notifications->fetch_assoc()) {
//     $notifications_list[] = $notification;
// }
// $stmt->close();

// // Debug: If there are no notifications but we have a count, something's wrong
// if (empty($notifications_list) && $notification_count > 0) {
//     error_log("Warning: Notifications count is $notification_count but no notifications were fetched.");
// }

// Determine if sidebar should be collapsed based on user preference or default
$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$sidebar_class = $sidebar_collapsed ? 'collapsed' : '';
$main_content_class = $sidebar_collapsed ? 'expanded' : '';

// Prepare profile image
$profile_img = '../../assets/images/default-profile.jpg';
// Check for profile image
$profile_image = !empty($user['profile_picture']) ? $user['profile_picture'] : '';

if (!empty($profile_image)) {
    // Check if file exists
    if (file_exists('../../uploads/profiles/' . $profile_image)) {
        $profile_img = '../../uploads/profiles/' . $profile_image;
    } else if (file_exists('../../uploads/profile/' . $profile_image)) {
        $profile_img = '../../uploads/profile/' . $profile_image;
    }
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Set dashboard title based on user type
$dashboard_title = 'Dashboard';
if ($user_type == 'consultant') {
    $dashboard_title = 'Consultant Dashboard';
} elseif ($user_type == 'member') {
    $dashboard_title = 'Team Member Dashboard';
} elseif ($user_type == 'admin') {
    $dashboard_title = 'Admin Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : $dashboard_title; ?> - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/visa.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <?php if (isset($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo $page_specific_css; ?>">
    <?php endif; ?>
</head>

<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="index.php" class="header-logo">
                    <img src="../../assets/images/logo-Visafy-light.png" alt="Visafy Logo" class="desktop-logo">
                </a>
                <?php if (!empty($user['organization_name'])): ?>
                <span class="organization-name"><?php echo htmlspecialchars($user['organization_name']); ?></span>
                <?php endif; ?>
            </div>
            <div class="header-right">
                <?php if ($user_type == 'consultant' && $membership_plan): ?>
                <div class="membership-info">
                    <span class="plan-badge"><?php echo htmlspecialchars($membership_plan); ?> Plan</span>
                    <span class="team-count"><?php echo $team_members_count; ?>/<?php echo $max_team_members; ?> Team Members</span>
                </div>
                <?php endif; ?>
                <!-- <div class="notification-dropdown">
                    <div class="notification-icon" id="notification-toggle">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-menu" id="notification-menu">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <?php if ($notification_count > 0): ?>
                            <a href="notifications.php" class="mark-all-read">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($notifications_list)): ?>
                            <div class="notification-item">
                                <p>No new notifications</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($notifications_list as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                <div class="notification-icon-small">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="notification-details">
                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($notification['content']); ?></p>
                                    <span
                                        class="notification-time"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">View all notifications</a>
                        </div>
                    </div>
                </div> -->
                <div class="user-dropdown">
                    <span
                        class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . ' ' . $_SESSION["last_name"]); ?></span>
                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img-header"
                        style="width: 32px; height: 32px;">
                    <div class="user-dropdown-menu">
                        <a href="../../index.php" class="dropdown-item">
                            <i class="fas fa-globe"></i>
                            Back to Website
                        </a>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <?php if ($user_type == 'consultant'): ?>
                        <a href="subscription.php" class="dropdown-item">
                            <i class="fas fa-credit-card"></i> Subscription
                        </a>
                        <?php endif; ?>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar <?php echo $sidebar_class; ?>">

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-item-text">Dashboard</span>
                </a>

                <!-- Visafy AI Section -->
                <div class="sidebar-divider"></div>
                <a href="ai-chat.php" class="nav-item <?php echo $current_page == 'ai-chat' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span class="nav-item-text">Visafy AI</span>
                </a>
                <a href="visa.php" class="nav-item <?php echo $current_page == 'visa' ? 'active' : ''; ?>">
                    <i class="fas fa-passport"></i>
                    <span class="nav-item-text">Visa</span>
                </a>

                <div class="sidebar-divider"></div>
                <!-- End Visafy AI Section -->
                
                <?php if ($user_type == 'consultant' || $user_type == 'admin'): ?>
                <a href="services.php" class="nav-item <?php echo $current_page == 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i>
                    <span class="nav-item-text">Services</span>
                </a>
                <?php endif; ?>
                
                <a href="bookings.php" class="nav-item <?php echo $current_page == 'bookings' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-item-text">Bookings</span>
                </a>
            
                <div class="sidebar-divider"></div>
                
               
                <a href="clients.php" class="nav-item <?php echo $current_page == 'clients' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-check"></i>
                    <span class="nav-item-text">Clients</span>
                </a>
                <a href="applications.php"
                    class="nav-item <?php echo $current_page == 'applications' ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i>
                    <span class="nav-item-text">Applications</span>
                </a>
                <a href="documents.php" class="nav-item <?php echo $current_page == 'documents' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-item-text">Documents</span>
                </a>

                <?php if ($user_type == 'consultant' || $user_type == 'admin'): ?>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title">Team Management</div>
                <a href="team.php" class="nav-item <?php echo $current_page == 'team' ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i>
                    <span class="nav-item-text">Team Members</span>
                </a>
                <?php endif; ?>
                
                <a href="tasks.php" class="nav-item <?php echo $current_page == 'tasks' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span class="nav-item-text">Tasks</span>
                </a>

                <div class="sidebar-divider"></div>
                <a href="messages.php" class="nav-item <?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span class="nav-item-text">Messages</span>
                </a>

                <?php if ($user_type == 'consultant' || $user_type == 'admin'): ?>
                <div class="sidebar-divider"></div>
                <a href="email-management.php" class="nav-item <?php echo $current_page == 'email-management' ? 'active' : ''; ?>">
                    <i class="fas fa-mail-bulk"></i>
                    <span class="nav-item-text">Email Management</span>
                </a>
                
                <?php if ($user_type == 'consultant'): ?>
                <a href="organization.php" class="nav-item <?php echo $current_page == 'organization' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span class="nav-item-text">Organization</span>
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <div class="sidebar-divider"></div>
                <a href="../../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-item-text">Logout</span>
                </a>
            </nav>
            
            <div class="user-profile sidebar-footer">
                <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h3 class="profile-name">
                        <?php echo htmlspecialchars($_SESSION["first_name"] . ' ' . $_SESSION["last_name"]); ?></h3>
                    <span class="role-badge">
                        <?php 
                        if ($user_type == 'consultant') {
                            echo 'Consultant';
                        } elseif ($user_type == 'member') {
                            echo 'Team Member';
                        } elseif ($user_type == 'admin') {
                            echo 'Admin';
                        }
                        ?>
                    </span>
                    <?php if ($user_type == 'member' && $belongs_to_consultant): ?>
                    <small class="team-of">Team of <?php echo htmlspecialchars($belongs_to_consultant['first_name'] . ' ' . $belongs_to_consultant['last_name']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content <?php echo $main_content_class; ?>">
            <div class="content-wrapper">
                <!-- Page content will be inserted here -->