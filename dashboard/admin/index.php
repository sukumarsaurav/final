<?php
// Set page title
$page_title = "Admin Dashboard";

// Include header
include('includes/header.php');

// Fetch basic stats
// Consultants count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'consultant' AND deleted_at IS NULL");
$stmt->execute();
$consultants_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Pending verification count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users u 
                       JOIN consultants c ON u.id = c.user_id 
                       LEFT JOIN consultant_profiles cp ON u.id = cp.consultant_id 
                       WHERE u.user_type = 'consultant' 
                       AND u.deleted_at IS NULL 
                       AND (cp.is_verified IS NULL OR cp.is_verified = 0)");
$stmt->execute();
$pending_verification = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Eligibility questions count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM decision_tree_questions");
$stmt->execute();
$questions_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// User assessments count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_assessments");
$stmt->execute();
$assessments_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="welcome-card mb-4">
                <div class="welcome-content">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!</h2>
                    <p>Here's what's happening with your platform today.</p>
                </div>
                <div class="welcome-date">
                    <div class="date"><?php echo date('l, F j, Y'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Stats Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Consultants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $consultants_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Verification</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_verification; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Eligibility Questions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $questions_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                User Assessments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $assessments_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="eligibility-calculator.php" class="btn btn-primary btn-block action-btn">
                                <i class="fas fa-calculator"></i> Manage Eligibility Calculator
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="verify-consultants.php" class="btn btn-warning btn-block action-btn">
                                <i class="fas fa-user-check"></i> Verify Consultants
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="manage-questions.php" class="btn btn-info btn-block action-btn">
                                <i class="fas fa-question-circle"></i> Manage Questions
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="assessment-results.php" class="btn btn-success btn-block action-btn">
                                <i class="fas fa-clipboard-check"></i> View Assessment Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Verifications -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Verifications</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Consultant</th>
                                    <th>Verified By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent verifications
                                $stmt = $conn->prepare("SELECT cp.consultant_id, cp.verified_at, 
                                                     CONCAT(c.first_name, ' ', c.last_name) AS consultant_name,
                                                     CONCAT(a.first_name, ' ', a.last_name) AS admin_name
                                                     FROM consultant_profiles cp
                                                     JOIN users c ON cp.consultant_id = c.id
                                                     JOIN users a ON cp.verified_by = a.id
                                                     WHERE cp.is_verified = 1
                                                     ORDER BY cp.verified_at DESC LIMIT 5");
                                $stmt->execute();
                                $verifications = $stmt->get_result();
                                
                                if ($verifications->num_rows > 0) {
                                    while ($row = $verifications->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['consultant_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['admin_name']) . '</td>';
                                        echo '<td>' . date('M d, Y', strtotime($row['verified_at'])) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center">No recent verifications</td></tr>';
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Assessments -->
        <div class="col-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Eligibility Assessments</h6>
                    <a href="assessment-results.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Result</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent assessments
                                $stmt = $conn->prepare("SELECT ua.id, ua.start_time, ua.end_time, ua.is_complete, 
                                                     ua.result_eligible, CONCAT(u.first_name, ' ', u.last_name) AS user_name
                                                     FROM user_assessments ua
                                                     JOIN users u ON ua.user_id = u.id
                                                     ORDER BY ua.start_time DESC LIMIT 10");
                                $stmt->execute();
                                $assessments = $stmt->get_result();
                                
                                if ($assessments->num_rows > 0) {
                                    while ($row = $assessments->fetch_assoc()) {
                                        $status = $row['is_complete'] ? 'Completed' : 'In Progress';
                                        $status_class = $row['is_complete'] ? 'success' : 'warning';
                                        
                                        $result = 'N/A';
                                        $result_class = 'secondary';
                                        if ($row['is_complete']) {
                                            if ($row['result_eligible'] === 1) {
                                                $result = 'Eligible';
                                                $result_class = 'success';
                                            } elseif ($row['result_eligible'] === 0) {
                                                $result = 'Not Eligible';
                                                $result_class = 'danger';
                                            }
                                        }
                                        
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['user_name']) . '</td>';
                                        echo '<td>' . date('M d, Y H:i', strtotime($row['start_time'])) . '</td>';
                                        echo '<td><span class="badge bg-' . $result_class . '">' . $result . '</span></td>';
                                        echo '<td><span class="badge bg-' . $status_class . '">' . $status . '</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No recent assessments</td></tr>';
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #042167;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --danger-color: #e74a3b;
    --warning-color: #f6c23e;
    --info-color: #36b9cc;
    --light-color: #f8f9fc;
    --dark-color: #5a5c69;
    --border-color: #e3e6f0;
    --message-color: #4e73df;
    --notification-color: #f6c23e;
}

/* Ensure all cards, buttons, badges, alerts, and tables use the same style as the rest of the dashboard */

.card, .stats-card, .shadow {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 20px;
    margin-bottom: 20px;
}

.stats-card {
    border-left: 4px solid;
}

.border-left-primary { border-left-color: var(--primary-color); }
.border-left-success { border-left-color: var(--success-color); }
.border-left-info { border-left-color: var(--info-color); }
.border-left-warning { border-left-color: var(--warning-color); }

.btn, .btn-primary, .btn-warning, .btn-info, .btn-success, .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary { background-color: var(--primary-color); color: white; }
.btn-primary:hover { background-color: #031c56; }
.btn-warning { background-color: var(--warning-color); color: white; }
.btn-warning:hover { background-color: #e0b137; }
.btn-info { background-color: var(--info-color); color: white; }
.btn-info:hover { background-color: #2fa9bd; }
.btn-success { background-color: var(--success-color); color: white; }
.btn-success:hover { background-color: #17a673; }
.btn-secondary { background-color: var(--secondary-color); color: white; }
.btn-secondary:hover { background-color: #757382; }

.badge, .bg-success, .bg-danger, .bg-warning, .bg-info, .bg-secondary {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    color: white;
}
.bg-success { background-color: var(--success-color) !important; }
.bg-danger { background-color: var(--danger-color) !important; }
.bg-warning { background-color: var(--warning-color) !important; color: #212529 !important; }
.bg-info { background-color: var(--info-color) !important; }
.bg-secondary { background-color: var(--secondary-color) !important; }

.alert, .alert-success, .alert-danger {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
.alert-success { background-color: rgba(28, 200, 138, 0.1); color: var(--success-color); border: 1px solid rgba(28, 200, 138, 0.2); }
.alert-danger { background-color: rgba(231, 74, 59, 0.1); color: var(--danger-color); border: 1px solid rgba(231, 74, 59, 0.2); }

.table, .table-bordered {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
}
.table th, .table td {
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    color: var(--dark-color);
    font-size: 0.95rem;
}
.table th {
    background-color: var(--light-color);
    color: var(--primary-color);
    font-weight: 600;
}
.table-bordered th, .table-bordered td {
    border: 1px solid var(--border-color);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-charts {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 576px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Include footer
include('includes/footer.php');
?> 