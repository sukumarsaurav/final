<?php
// Set page title
$page_title = "View Consultant";

// Include header
include('includes/header.php');

// Get consultant ID from URL
$consultant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($consultant_id <= 0) {
    $_SESSION['error_message'] = "Invalid consultant ID.";
    header("Location: consultants.php");
    exit;
}

// Get consultant details
$query = "SELECT 
    u.id AS consultant_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status,
    u.created_at,
    u.profile_picture,
    c.company_name,
    c.registration_number,
    c.membership_plan_id,
    mp.name AS membership_plan,
    mp.max_team_members,
    c.team_members_count,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.certifications,
    cp.languages,
    cp.website,
    cp.social_linkedin,
    cp.social_twitter,
    cp.social_facebook,
    cp.is_verified,
    cp.verified_at,
    CONCAT(v.first_name, ' ', v.last_name) AS verified_by_name,
    o.name AS organization_name
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
JOIN 
    membership_plans mp ON c.membership_plan_id = mp.id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    users v ON cp.verified_by = v.id
LEFT JOIN 
    organizations o ON u.organization_id = o.id
WHERE 
    u.id = ? AND u.user_type = 'consultant'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Consultant not found.";
    header("Location: consultants.php");
    exit;
}

$consultant = $result->fetch_assoc();

// Get verification documents
$docs_query = "SELECT 
    id,
    document_type,
    document_path,
    uploaded_at,
    verified,
    verified_at,
    notes
FROM 
    consultant_verifications 
WHERE 
    consultant_id = ? 
ORDER BY 
    uploaded_at DESC";

$docs_stmt = $conn->prepare($docs_query);
$docs_stmt->bind_param("i", $consultant_id);
$docs_stmt->execute();
$documents = $docs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get team members
$team_query = "SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status,
    tm.member_type,
    tm.invitation_status,
    tm.invited_at,
    tm.accepted_at
FROM 
    team_members tm
JOIN 
    users u ON tm.member_user_id = u.id
WHERE 
    tm.consultant_id = ?
ORDER BY 
    tm.invited_at DESC";

$team_stmt = $conn->prepare($team_query);
$team_stmt->bind_param("i", $consultant_id);
$team_stmt->execute();
$team_members = $team_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get booking statistics
$stats_query = "SELECT 
    COUNT(DISTINCT b.id) AS total_bookings,
    SUM(CASE WHEN bs.name = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN bs.name IN ('cancelled_by_user', 'cancelled_by_admin', 'cancelled_by_consultant') THEN 1 ELSE 0 END) AS cancelled_bookings,
    ROUND(AVG(bf.rating), 1) AS average_rating,
    COUNT(DISTINCT bf.id) AS total_ratings
FROM 
    bookings b
LEFT JOIN 
    booking_statuses bs ON b.status_id = bs.id
LEFT JOIN 
    booking_feedback bf ON b.id = bf.booking_id
WHERE 
    b.consultant_id = ? AND b.deleted_at IS NULL";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $consultant_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Consultant Details</h4>
                            <p class="text-muted mb-0">View detailed information about the consultant</p>
                        </div>
                        <div>
                            <a href="consultants.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($consultant['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($consultant['profile_picture']); ?>" 
                                 alt="Profile Picture" 
                                 class="rounded-circle img-thumbnail" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 150px; height: 150px;">
                                <i class="fas fa-user fa-4x text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h4 class="text-center mb-3">
                        <?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?>
                    </h4>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status:</label>
                        <?php if ($consultant['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Suspended</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Verification Status:</label>
                        <?php if ($consultant['is_verified']): ?>
                            <span class="badge bg-info">Verified</span>
                            <br>
                            <small class="text-muted">
                                Verified by: <?php echo htmlspecialchars($consultant['verified_by_name']); ?><br>
                                On: <?php echo date('M d, Y', strtotime($consultant['verified_at'])); ?>
                            </small>
                        <?php else: ?>
                            <span class="badge bg-warning">Unverified</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Information:</label>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($consultant['email']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($consultant['phone']); ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Organization:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($consultant['organization_name']); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Member Since:</label>
                        <p class="mb-0"><?php echo date('M d, Y', strtotime($consultant['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company & Membership -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Company & Membership Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Company Name:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Registration Number:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($consultant['registration_number'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Membership Plan:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($consultant['membership_plan']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Team Members:</label>
                                <p class="mb-0">
                                    <?php echo $consultant['team_members_count']; ?> / <?php echo $consultant['max_team_members']; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Bio:</label>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($consultant['bio'] ?? 'No bio provided')); ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Specializations:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($consultant['specializations'] ?? 'Not specified'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Years of Experience:</label>
                                <p class="mb-0"><?php echo $consultant['years_experience'] ?? 'Not specified'; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Certifications:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($consultant['certifications'] ?? 'Not specified'); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Languages:</label>
                        <p class="mb-0"><?php echo htmlspecialchars($consultant['languages'] ?? 'Not specified'); ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Social Media & Website:</label>
                        <div class="d-flex gap-3">
                            <?php if ($consultant['website']): ?>
                                <a href="<?php echo htmlspecialchars($consultant['website']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-globe"></i> Website
                                </a>
                            <?php endif; ?>
                            <?php if ($consultant['social_linkedin']): ?>
                                <a href="<?php echo htmlspecialchars($consultant['social_linkedin']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </a>
                            <?php endif; ?>
                            <?php if ($consultant['social_twitter']): ?>
                                <a href="<?php echo htmlspecialchars($consultant['social_twitter']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                            <?php endif; ?>
                            <?php if ($consultant['social_facebook']): ?>
                                <a href="<?php echo htmlspecialchars($consultant['social_facebook']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fab fa-facebook"></i> Facebook
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Documents -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Verification Documents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="alert alert-info">
                            No verification documents have been uploaded.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Uploaded Date</th>
                                        <th>Status</th>
                                        <th>Verified By</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?php echo ucwords(str_replace('_', ' ', $doc['document_type'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <?php if ($doc['verified']): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($doc['verified']): ?>
                                                    <?php echo date('M d, Y', strtotime($doc['verified_at'])); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['notes'] ?? '-'); ?></td>
                                            <td>
                                                <a href="../<?php echo htmlspecialchars($doc['document_path']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Team Members</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($team_members)): ?>
                        <div class="alert alert-info">
                            No team members have been added yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Invited</th>
                                        <th>Accepted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($team_members as $member): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                            <td><?php echo ucfirst($member['member_type']); ?></td>
                                            <td>
                                                <?php if ($member['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Suspended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($member['invited_at'])); ?></td>
                                            <td>
                                                <?php if ($member['accepted_at']): ?>
                                                    <?php echo date('M d, Y', strtotime($member['accepted_at'])); ?>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Statistics -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Booking Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Bookings</h6>
                                    <h2 class="mb-0"><?php echo $stats['total_bookings']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Completed Bookings</h6>
                                    <h2 class="mb-0"><?php echo $stats['completed_bookings']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Cancelled Bookings</h6>
                                    <h2 class="mb-0"><?php echo $stats['cancelled_bookings']; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Average Rating</h6>
                                    <h2 class="mb-0">
                                        <?php echo $stats['average_rating'] ? number_format($stats['average_rating'], 1) : 'N/A'; ?>
                                    </h2>
                                    <small><?php echo $stats['total_ratings']; ?> ratings</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('includes/footer.php');
?> 
