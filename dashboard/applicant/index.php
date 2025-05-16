<?php
// Set page title
$page_title = "Dashboard - Applicant";

// Include header
include('includes/header.php');

// Check if user has any active applications
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE applicant_id = ? AND status != 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_applications = $result->fetch_assoc()['count'];
$stmt->close();

// Check if user has any upcoming meetings
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND start_time > NOW()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_meetings = $result->fetch_assoc()['count'];
$stmt->close();

// Check if user has any unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_messages = $result->fetch_assoc()['count'];
$stmt->close();
?>

<div class="container-fluid">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Welcome, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</h1>
        <p class="dashboard-subtitle">Here's an overview of your visa application journey</p>
    </div>

    <!-- Dashboard Overview Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h5 class="card-title">Applications</h5>
                    <h2 class="card-value"><?php echo $active_applications; ?></h2>
                    <p class="card-text">Active applications</p>
                    <a href="applications.php" class="card-link">Manage applications <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="card-icon bg-success">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5 class="card-title">Meetings</h5>
                    <h2 class="card-value"><?php echo $upcoming_meetings; ?></h2>
                    <p class="card-text">Upcoming meetings</p>
                    <a href="meetings.php" class="card-link">View schedule <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="card-icon bg-info">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5 class="card-title">Messages</h5>
                    <h2 class="card-value"><?php echo $unread_messages; ?></h2>
                    <p class="card-text">Unread messages</p>
                    <a href="messages.php" class="card-link">Check messages <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Applications</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Visa Type</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent applications
                                $stmt = $conn->prepare("SELECT a.id, a.visa_type, a.status, a.updated_at 
                                                      FROM applications a 
                                                      WHERE a.applicant_id = ? 
                                                      ORDER BY a.updated_at DESC LIMIT 5");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $applications_result = $stmt->get_result();
                                
                                if ($applications_result->num_rows > 0) {
                                    while ($app = $applications_result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>#' . $app['id'] . '</td>';
                                        echo '<td>' . htmlspecialchars($app['visa_type']) . '</td>';
                                        echo '<td><span class="badge bg-' . getStatusColor($app['status']) . '">' . ucfirst($app['status']) . '</span></td>';
                                        echo '<td>' . date('M d, Y', strtotime($app['updated_at'])) . '</td>';
                                        echo '<td><a href="application-details.php?id=' . $app['id'] . '" class="btn btn-sm btn-outline-primary">View</a></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">No applications found</td></tr>';
                                }
                                $stmt->close();
                                
                                function getStatusColor($status) {
                                    switch ($status) {
                                        case 'pending':
                                            return 'warning';
                                        case 'in_progress':
                                            return 'info';
                                        case 'approved':
                                            return 'success';
                                        case 'rejected':
                                            return 'danger';
                                        default:
                                            return 'secondary';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Meetings -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Meetings</h5>
                    <a href="meetings.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php
                    // Get upcoming meetings
                    $stmt = $conn->prepare("SELECT b.id, b.title, b.start_time, b.end_time, b.meeting_link, 
                                          u.first_name, u.last_name
                                          FROM bookings b 
                                          JOIN users u ON b.consultant_id = u.id
                                          WHERE b.user_id = ? AND b.start_time > NOW()
                                          ORDER BY b.start_time ASC LIMIT 3");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $meetings_result = $stmt->get_result();
                    
                    if ($meetings_result->num_rows > 0) {
                        while ($meeting = $meetings_result->fetch_assoc()) {
                            $start_time = new DateTime($meeting['start_time']);
                            $end_time = new DateTime($meeting['end_time']);
                            ?>
                            <div class="meeting-item">
                                <div class="meeting-time">
                                    <div class="date"><?php echo $start_time->format('M d'); ?></div>
                                    <div class="time"><?php echo $start_time->format('h:i A'); ?> - <?php echo $end_time->format('h:i A'); ?></div>
                                </div>
                                <div class="meeting-details">
                                    <h5><?php echo htmlspecialchars($meeting['title']); ?></h5>
                                    <p>With <?php echo htmlspecialchars($meeting['first_name'] . ' ' . $meeting['last_name']); ?></p>
                                </div>
                                <div class="meeting-actions">
                                    <?php if (!empty($meeting['meeting_link'])): ?>
                                    <a href="<?php echo $meeting['meeting_link']; ?>" target="_blank" class="btn btn-sm btn-success">Join</a>
                                    <?php endif; ?>
                                    <a href="meeting-details.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-center">No upcoming meetings scheduled</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('includes/footer.php');
?>
