<?php
// Set page title
$page_title = "Manage Consultants";

// Include header
include('includes/header.php');

// Process any actions
$action_message = '';
$action_error = '';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $consultant_id = intval($_GET['id']);
    
    if ($action === 'suspend' && $consultant_id > 0) {
        // Suspend consultant
        $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND user_type = 'consultant'");
        $stmt->bind_param("i", $consultant_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $action_message = "Consultant has been suspended successfully.";
        } else {
            $action_error = "Failed to suspend consultant.";
        }
        $stmt->close();
    } elseif ($action === 'activate' && $consultant_id > 0) {
        // Activate consultant
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND user_type = 'consultant'");
        $stmt->bind_param("i", $consultant_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $action_message = "Consultant has been activated successfully.";
        } else {
            $action_error = "Failed to activate consultant.";
        }
        $stmt->close();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$verification_filter = isset($_GET['verification']) ? $_GET['verification'] : 'all';

// Build the query based on filters
$query = "SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    u.email,
    u.phone,
    u.status,
    u.created_at,
    c.company_name,
    COALESCE(cp.is_verified, 0) AS is_verified,
    cp.verified_at,
    CONCAT(a.first_name, ' ', a.last_name) AS verified_by_name
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    users a ON cp.verified_by = a.id
WHERE 
    u.user_type = 'consultant' 
    AND u.deleted_at IS NULL";

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND u.status = '$status_filter'";
}

// Add verification filter
if ($verification_filter === 'verified') {
    $query .= " AND cp.is_verified = 1";
} elseif ($verification_filter === 'unverified') {
    $query .= " AND (cp.is_verified IS NULL OR cp.is_verified = 0)";
}

$query .= " ORDER BY u.created_at DESC";

$result = $conn->query($query);
$consultants = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
}

// Get stats
$total_consultants = count($consultants);
$active_consultants = 0;
$suspended_consultants = 0;
$verified_consultants = 0;

foreach ($consultants as $consultant) {
    if ($consultant['status'] === 'active') $active_consultants++;
    if ($consultant['status'] === 'suspended') $suspended_consultants++;
    if ($consultant['is_verified'] === 1) $verified_consultants++;
}
?>

<div class="container-fluid">
    <?php if (!empty($action_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $action_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($action_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $action_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users fa-3x text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">Consultants Management</h4>
                            <p class="text-muted mb-0">View and manage all consultants on the platform</p>
                        </div>
                    </div>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_consultants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Active Consultants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_consultants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                Suspended Consultants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $suspended_consultants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-slash fa-2x text-gray-300"></i>
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
                                Verified Consultants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $verified_consultants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-certificate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Consultants List</h6>
                    <div class="filter-controls">
                        <form action="" method="get" class="d-flex">
                            <div class="me-2">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="me-2">
                                <select name="verification" class="form-select form-select-sm">
                                    <option value="all" <?php echo $verification_filter === 'all' ? 'selected' : ''; ?>>All Verification</option>
                                    <option value="verified" <?php echo $verification_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                    <option value="unverified" <?php echo $verification_filter === 'unverified' ? 'selected' : ''; ?>>Unverified</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered datatable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Company</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($consultants)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No consultants found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consultants as $consultant): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($consultant['consultant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($consultant['company_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($consultant['email']); ?><br>
                                                <small><?php echo htmlspecialchars($consultant['phone']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($consultant['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Suspended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($consultant['is_verified'] == 1): ?>
                                                    <span class="badge bg-info">Verified</span>
                                                    <br>
                                                    <small>by <?php echo htmlspecialchars($consultant['verified_by_name'] ?? 'System'); ?></small>
                                                    <br>
                                                    <small><?php echo date('M d, Y', strtotime($consultant['verified_at'])); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unverified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($consultant['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-consultant.php?id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($consultant['status'] === 'active'): ?>
                                                        <a href="consultants.php?action=suspend&id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to suspend this consultant?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="consultants.php?action=activate&id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this consultant?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($consultant['is_verified'] == 0): ?>
                                                        <a href="verify-consultants.php?id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-user-check"></i>
                                                        </a>
                                                    <?php endif; ?>
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
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "order": [[5, "desc"]], // Sort by joined date by default
            "pageLength": 25,
            "language": {
                "emptyTable": "No consultants found"
            }
        });
    }
});
</script>

<?php
// Include footer
include('includes/footer.php');
?> 