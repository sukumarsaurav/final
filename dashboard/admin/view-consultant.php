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
            // Also mark all documents as verified
            $update_docs = $conn->prepare("UPDATE consultant_verifications SET verified = 1, verified_at = NOW() WHERE consultant_id = ?");
            $update_docs->bind_param("i", $verify_id);
            $update_docs->execute();
            
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

// Get verification documents
$docs_query = "SELECT id, document_type, document_path, uploaded_at, verified, verified_at 
              FROM consultant_verifications 
              WHERE consultant_id = ? 
              ORDER BY uploaded_at DESC";

$docs_stmt = $conn->prepare($docs_query);
$docs_stmt->bind_param('i', $consultant_id);
$docs_stmt->execute();
$docs_result = $docs_stmt->get_result();
$documents = [];

if ($docs_result->num_rows > 0) {
    while ($row = $docs_result->fetch_assoc()) {
        $documents[] = $row;
    }
}
$docs_stmt->close();
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
            
            <?php if (!$consultant['is_verified']): ?>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#verifyModal">
                <i class="fas fa-check-circle"></i> Verify Consultant
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Verification Modal -->
    <?php if (!$consultant['is_verified']): ?>
    <div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyModalLabel">Confirm Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to verify this consultant?</p>
                    <p>This will mark their profile as verified and display a verification badge on their profile.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post">
                        <input type="hidden" name="consultant_id" value="<?php echo $consultant_id; ?>">
                        <button type="submit" name="verify_consultant" class="btn btn-success">Verify Consultant</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Consultant Profile Card -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                    <div class="dropdown no-arrow">
                        <?php if ($consultant['is_verified']): ?>
                        <span class="badge bg-success text-white">Verified</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark">Not Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img class="img-fluid rounded-circle mb-2" style="width: 150px; height: 150px; object-fit: cover;" 
                             src="<?php echo $profile_img; ?>" alt="Profile Image">
                        <h5 class="mb-0"><?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                        
                        <?php if ($consultant['is_verified']): ?>
                        <div class="mb-2">
                            <span class="badge bg-success text-white p-2">
                                <i class="fas fa-check-circle me-1"></i> Verified Consultant
                            </span>
                        </div>
                        <small class="text-muted">
                            Verified on <?php echo date('M d, Y', strtotime($consultant['verified_at'])); ?>
                            <?php if (!empty($consultant['verified_by_name'])): ?>
                            by <?php echo htmlspecialchars($consultant['verified_by_name']); ?>
                            <?php endif; ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Contact Information</h6>
                        <div class="mb-2">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <span><?php echo htmlspecialchars($consultant['email']); ?></span>
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <span><?php echo htmlspecialchars($consultant['phone']); ?></span>
                        </div>
                        <?php if (!empty($consultant['website'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-globe text-primary me-2"></i>
                            <a href="<?php echo htmlspecialchars($consultant['website']); ?>" target="_blank">
                                <?php echo htmlspecialchars($consultant['website']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Social Media</h6>
                        <div class="d-flex">
                            <?php if (!empty($consultant['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_linkedin']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['social_twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_twitter']); ?>" target="_blank" class="btn btn-sm btn-outline-info me-2">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_facebook']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h6 class="font-weight-bold">Account Information</h6>
                        <div class="mb-2">
                            <span class="text-muted">Member Since:</span>
                            <span><?php echo date('M d, Y', strtotime($consultant['created_at'])); ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="badge <?php echo $consultant['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?> text-white">
                                <?php echo ucfirst($consultant['status']); ?>
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Email Verified:</span>
                            <span class="badge <?php echo $consultant['email_verified'] ? 'bg-success' : 'bg-warning'; ?> text-white">
                                <?php echo $consultant['email_verified'] ? 'Yes' : 'No'; ?>
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Membership Plan:</span>
                            <span><?php echo htmlspecialchars($consultant['membership_plan']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Column -->
        <div class="col-xl-8 col-lg-7">
            <!-- Verification Documents Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Verification Documents</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-center">No verification documents uploaded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Uploaded</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $doc_type = str_replace('_', ' ', $doc['document_type']);
                                            echo ucwords($doc_type);
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                        <td>
                                            <?php if ($doc['verified']): ?>
                                            <span class="badge bg-success text-white">Verified</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../../<?php echo $doc['document_path']; ?>" target="_blank" class="btn btn-primary btn-sm">
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

            <!-- Bio Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Professional Information</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($consultant['bio'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Bio</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['specializations'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Specializations</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['specializations'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['years_experience'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Years of Experience</h6>
                        <p><?php echo $consultant['years_experience']; ?> years</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['certifications'])): ?>
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Certifications</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['languages'])): ?>
                    <div>
                        <h6 class="font-weight-bold">Languages</h6>
                        <p><?php echo nl2br(htmlspecialchars($consultant['languages'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Team Members Card -->
            <!-- ... existing code ... -->
            
            <!-- Booking Statistics Card -->
            <!-- ... existing code ... -->
        </div>
    </div>
</div>

<?php
// Include footer
include('includes/footer.php');
?>