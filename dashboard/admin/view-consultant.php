<?php
// Set page title
$page_title = "View Consultant";

// Include header
include('includes/header.php');

// Check if consultant ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to consultants list if no ID provided
    header("Location: consultants.php");
    exit();
}

$consultant_id = intval($_GET['id']);

// Process verification action if submitted
$action_message = '';
$action_error = '';

if (isset($_POST['verify_consultant']) && isset($_POST['consultant_id'])) {
    $verify_id = intval($_POST['consultant_id']);
    
    if ($verify_id > 0) {
        // Check if consultant profile exists
        $check_profile = $conn->prepare("SELECT consultant_id FROM consultant_profiles WHERE consultant_id = ?");
        $check_profile->bind_param("i", $verify_id);
        $check_profile->execute();
        $profile_result = $check_profile->get_result();
        
        if ($profile_result->num_rows > 0) {
            // Update existing profile
            $stmt = $conn->prepare("UPDATE consultant_profiles SET is_verified = 1, verified_at = NOW(), verified_by = ? WHERE consultant_id = ?");
            $stmt->bind_param("ii", $user_id, $verify_id);
        } else {
            // Create new profile entry
            $stmt = $conn->prepare("INSERT INTO consultant_profiles (consultant_id, is_verified, verified_at, verified_by) VALUES (?, 1, NOW(), ?)");
            $stmt->bind_param("ii", $verify_id, $user_id);
        }
        
        if ($stmt->execute()) {
            $action_message = "Consultant has been verified successfully.";
        } else {
            $action_error = "Failed to verify consultant: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch consultant details
$query = "SELECT 
    u.id AS consultant_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status,
    u.created_at,
    u.email_verified,
    u.profile_picture,
    c.company_name,
    c.team_members_count,
    mp.name AS membership_plan,
    mp.max_team_members,
    mp.price,
    mp.billing_cycle,
    COALESCE(cp.is_verified, 0) AS is_verified,
    cp.verified_at,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.certifications,
    cp.languages,
    cp.website,
    cp.social_linkedin,
    cp.social_twitter,
    cp.social_facebook,
    o.name AS organization_name,
    CONCAT(a.first_name, ' ', a.last_name) AS verified_by_name
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
JOIN 
    membership_plans mp ON c.membership_plan_id = mp.id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    organizations o ON u.organization_id = o.id
LEFT JOIN 
    users a ON cp.verified_by = a.id
WHERE 
    u.id = ? 
    AND u.user_type = 'consultant' 
    AND u.deleted_at IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Consultant not found, redirect to list
    header("Location: consultants.php");
    exit();
}

$consultant = $result->fetch_assoc();
$stmt->close();

// Get team members
$team_query = "SELECT 
    tm.id AS team_member_id,
    tm.invitation_status,
    tm.invited_at,
    tm.accepted_at,
    u.id AS user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.user_type,
    u.status
FROM 
    team_members tm
JOIN 
    users u ON tm.member_user_id = u.id
WHERE 
    tm.consultant_id = ?
ORDER BY 
    tm.created_at DESC";

$team_stmt = $conn->prepare($team_query);
$team_stmt->bind_param("i", $consultant_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team_members = [];

if ($team_result && $team_result->num_rows > 0) {
    while ($row = $team_result->fetch_assoc()) {
        $team_members[] = $row;
    }
}
$team_stmt->close();

// Get booking statistics
$bookings_query = "SELECT 
    COUNT(*) AS total_bookings,
    SUM(CASE WHEN status_id IN (SELECT id FROM booking_statuses WHERE name = 'completed') THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN status_id IN (SELECT id FROM booking_statuses WHERE name = 'cancelled_by_consultant') THEN 1 ELSE 0 END) AS cancelled_bookings,
    AVG(CASE WHEN bf.rating IS NOT NULL THEN bf.rating ELSE NULL END) AS average_rating,
    COUNT(bf.id) AS total_reviews
FROM 
    bookings b
LEFT JOIN 
    booking_feedback bf ON b.id = bf.booking_id
WHERE 
    b.consultant_id = ?
    AND b.deleted_at IS NULL";

$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $consultant_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$booking_stats = $bookings_result->fetch_assoc();
$bookings_stmt->close();

// Format the average rating
$average_rating = $booking_stats['average_rating'] ? number_format($booking_stats['average_rating'], 1) : 'N/A';

// Get profile image
$profile_img = '../../assets/images/default-profile.jpg';
if (!empty($consultant['profile_picture'])) {
    if (file_exists('../../uploads/profiles/' . $consultant['profile_picture'])) {
        $profile_img = '../../uploads/profiles/' . $consultant['profile_picture'];
    }
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

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Consultant Profile</h1>
        <div>
            <a href="consultants.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <?php if ($consultant['status'] === 'active'): ?>
            <a href="consultants.php?action=suspend&id=<?php echo $consultant_id; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to suspend this consultant?');">
                <i class="fas fa-user-slash"></i> Suspend
            </a>
            <?php else: ?>
            <a href="consultants.php?action=activate&id=<?php echo $consultant_id; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this consultant?');">
                <i class="fas fa-user-check"></i> Activate
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Consultant Profile Card -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <a class="dropdown-item" href="edit-consultant.php?id=<?php echo $consultant_id; ?>">
                                <i class="fas fa-edit fa-sm fa-fw mr-2 text-gray-400"></i> Edit Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteConsultantModal">
                                <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i> Delete Account
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img class="img-profile rounded-circle" src="<?php echo $profile_img; ?>" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="mt-3"><?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($consultant['company_name'] ?? 'Independent Consultant'); ?></p>
                        
                        <?php if ($consultant['is_verified']): ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle"></i> Not Verified</span>
                        <?php endif; ?>
                        
                        <?php if ($consultant['status'] === 'active'): ?>
                        <span class="badge bg-primary"><i class="fas fa-user-check"></i> Active</span>
                        <?php else: ?>
                        <span class="badge bg-danger"><i class="fas fa-user-slash"></i> Suspended</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Contact Information</h6>
                        <div class="mb-2">
                            <i class="fas fa-envelope fa-fw text-gray-400 mr-2"></i> 
                            <a href="mailto:<?php echo htmlspecialchars($consultant['email']); ?>"><?php echo htmlspecialchars($consultant['email']); ?></a>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-phone fa-fw text-gray-400 mr-2"></i> 
                            <a href="tel:<?php echo htmlspecialchars($consultant['phone']); ?>"><?php echo htmlspecialchars($consultant['phone']); ?></a>
                        </div>
                        <?php if (!empty($consultant['website'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-globe fa-fw text-gray-400 mr-2"></i> 
                            <a href="<?php echo htmlspecialchars($consultant['website']); ?>" target="_blank"><?php echo htmlspecialchars($consultant['website']); ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Social Media</h6>
                        <div class="d-flex">
                            <?php if (!empty($consultant['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_linkedin']); ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['social_twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_twitter']); ?>" class="btn btn-sm btn-outline-info me-2" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_facebook']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Account Information</h6>
                        <div class="mb-2">
                            <span class="text-muted">Member Since:</span> 
                            <?php echo date('M d, Y', strtotime($consultant['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Email Verified:</span> 
                            <?php echo $consultant['email_verified'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Organization:</span> 
                            <?php echo htmlspecialchars($consultant['organization_name'] ?? 'None'); ?>
                        </div>
                    </div>
                    
                    <?php if (!$consultant['is_verified']): ?>
                    <form method="post" action="">
                        <input type="hidden" name="consultant_id" value="<?php echo $consultant_id; ?>">
                        <button type="submit" name="verify_consultant" class="btn btn-success btn-block">
                            <i class="fas fa-check-circle"></i> Verify Consultant
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <small>
                            <strong>Verified on:</strong> <?php echo date('M d, Y', strtotime($consultant['verified_at'])); ?><br>
                            <strong>Verified by:</strong> <?php echo htmlspecialchars($consultant['verified_by_name'] ?? 'System'); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Membership Plan Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Membership Plan</h6>
                </div>
                <div class="card-body">
                    <h5 class="font-weight-bold text-primary"><?php echo htmlspecialchars($consultant['membership_plan']); ?></h5>
                    <div class="mb-2">
                        <span class="text-muted">Price:</span> 
                        $<?php echo number_format($consultant['price'], 2); ?> / <?php echo $consultant['billing_cycle']; ?>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Team Members:</span> 
                        <?php echo $consultant['team_members_count']; ?> / <?php echo $consultant['max_team_members']; ?>
                    </div>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($consultant['team_members_count'] / $consultant['max_team_members']) * 100; ?>%" 
                            aria-valuenow="<?php echo $consultant['team_members_count']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $consultant['max_team_members']; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="text-muted">Total Bookings:</span> 
                        <span class="font-weight-bold"><?php echo $booking_stats['total_bookings']; ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Completed Bookings:</span> 
                        <span class="font-weight-bold"><?php echo $booking_stats['completed_bookings']; ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Cancelled Bookings:</span> 
                        <span class="font-weight-bold"><?php echo $booking_stats['cancelled_bookings']; ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Average Rating:</span> 
                        <span class="font-weight-bold">
                            <?php echo $average_rating; ?>
                            <?php if ($average_rating !== 'N/A'): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted">Total Reviews:</span> 
                        <span class="font-weight-bold"><?php echo $booking_stats['total_reviews']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Consultant Details Column -->
        <div class="col-xl-8 col-lg-7">
            <!-- Bio and Specializations -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Professional Information</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($consultant['bio'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Biography</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['specializations'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Specializations</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['specializations'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php if (!empty($consultant['years_experience'])): ?>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">Years of Experience</h6>
                            <p><?php echo htmlspecialchars($consultant['years_experience']); ?> years</p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['certifications'])): ?>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">Certifications</h6>
                            <p><?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['languages'])): ?>
                        <div class="col-md-6 mb-3">
                            <h6 class="font-weight-bold">Languages</h6>
                            <p><?php echo nl2br(htmlspecialchars($consultant['languages'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Team Members Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Team Members</h6>
                    <span class="badge bg-primary"><?php echo count($team_members); ?> / <?php echo $consultant['max_team_members']; ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($team_members)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                        <p class="mb-0">No team members found</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($team_members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($member['user_type'])); ?></td>
                                    <td>
                                        <?php if ($member['invitation_status'] === 'accepted'): ?>
                                        <span class="badge bg-success">Accepted</span>
                                        <?php elseif ($member['invitation_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($member['invitation_status'] === 'accepted' && !empty($member['accepted_at'])) {
                                            echo date('M d, Y', strtotime($member['accepted_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Bookings Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get recent bookings
                    $recent_bookings_query = "SELECT 
                        b.id,
                        b.reference_number,
                        CONCAT(u.first_name, ' ', u.last_name) AS client_name,
                        v.visa_type,
                        st.service_name,
                        b.booking_datetime,
                        bs.name AS status,
                        bs.color AS status_color,
                        bp.payment_status,
                        IFNULL(bf.rating, 0) AS rating
                    FROM 
                        bookings b
                    JOIN 
                        users u ON b.user_id = u.id
                    JOIN 
                        booking_statuses bs ON b.status_id = bs.id
                    JOIN 
                        visa_services vs ON b.visa_service_id = vs.visa_service_id
                    JOIN 
                        visas v ON vs.visa_id = v.visa_id
                    JOIN 
                        service_types st ON vs.service_type_id = st.service_type_id
                    LEFT JOIN 
                        booking_payments bp ON b.id = bp.booking_id
                    LEFT JOIN 
                        booking_feedback bf ON b.id = bf.booking_id
                    WHERE 
                        b.consultant_id = ?
                        AND b.deleted_at IS NULL
                    ORDER BY 
                        b.booking_datetime DESC
                    LIMIT 5";
                    
                    $recent_bookings_stmt = $conn->prepare($recent_bookings_query);
                    $recent_bookings_stmt->bind_param("i", $consultant_id);
                    $recent_bookings_stmt->execute();
                    $recent_bookings_result = $recent_bookings_stmt->get_result();
                    $recent_bookings = [];
                    
                    if ($recent_bookings_result && $recent_bookings_result->num_rows > 0) {
                        while ($row = $recent_bookings_result->fetch_assoc()) {
                            $recent_bookings[] = $row;
                        }
                    }
                    $recent_bookings_stmt->close();
                    ?>
                    
                    <?php if (empty($recent_bookings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt fa-3x text-gray-300 mb-3"></i>
                        <p class="mb-0">No recent bookings found</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>">
                                            <?php echo htmlspecialchars($booking['reference_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['visa_type']); ?> - 
                                        <?php echo htmlspecialchars($booking['service_name']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($booking['booking_datetime'])); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $booking['status_color']; ?>">
                                            <?php echo htmlspecialchars($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['rating'] > 0): ?>
                                            <?php echo $booking['rating']; ?> <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="consultant-bookings.php?id=<?php echo $consultant_id; ?>" class="btn btn-sm btn-primary">
                            View All Bookings
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Consultant Modal -->
<div class="modal fade" id="deleteConsultantModal" tabindex="-1" aria-labelledby="deleteConsultantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConsultantModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this consultant account? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> This will permanently remove all data associated with this consultant including bookings, team members, and documents.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="delete-consultant.php?id=<?php echo $consultant_id; ?>" class="btn btn-danger">Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('includes/footer.php');
?>