<?php
// Set page title
$page_title = "Verify Consultants";

// Include header
include('includes/header.php');

// Get all consultants who have uploaded verification documents but are not verified yet
$query = "SELECT 
    u.id AS consultant_id,
    u.first_name,
    u.last_name,
    u.email,
    u.profile_picture,
    c.company_name,
    COALESCE(cp.is_verified, 0) AS is_verified,
    COUNT(cv.id) AS document_count
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN 
    consultant_verifications cv ON u.id = cv.consultant_id
WHERE 
    u.user_type = 'consultant' 
    AND u.deleted_at IS NULL
    AND u.status = 'active'
GROUP BY 
    u.id
HAVING 
    document_count > 0 AND (is_verified = 0 OR is_verified IS NULL)
ORDER BY 
    document_count DESC";

$result = $conn->query($query);
$consultants = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
}

// Process verification action if submitted
$action_message = '';
$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_consultant'])) {
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
        <h1 class="h3 mb-0 text-gray-800">Verify Consultants</h1>
    </div>

    <?php if (empty($consultants)): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <p class="text-center">No consultants pending verification at this time.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Consultants Pending Verification</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Consultant</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Documents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultants as $consultant): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $profile_img = '../../assets/images/default-profile.jpg';
                                    if (!empty($consultant['profile_picture'])) {
                                        if (file_exists('../../uploads/users/' . $consultant['consultant_id'] . '/profile/' . $consultant['profile_picture'])) {
                                            $profile_img = '../../uploads/users/' . $consultant['consultant_id'] . '/profile/' . $consultant['profile_picture'];
                                        }
                                    }
                                    ?>
                                    
                                    <img src="<?php echo $profile_img; ?>" class="rounded-circle mr-2" width="40" height="40" alt="Profile">
                                    <div>
                                        <?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($consultant['email']); ?></td>
                            <td><?php echo htmlspecialchars($consultant['company_name']); ?></td>
                            <td>
                                <span class="badge bg-info text-white"><?php echo $consultant['document_count']; ?> documents</span>
                            </td>
                            <td>
                                <a href="view-consultant.php?id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#verifyModal<?php echo $consultant['consultant_id']; ?>">
                                    <i class="fas fa-check"></i> Verify
                                </button>
                                
                                <!-- Verify Modal -->
                                <div class="modal fade" id="verifyModal<?php echo $consultant['consultant_id']; ?>" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="verifyModalLabel">Confirm Verification</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to verify <strong><?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?></strong>?</p>
                                                <p>This will mark their profile as verified and display a verification badge on their profile.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="post">
                                                    <input type="hidden" name="consultant_id" value="<?php echo $consultant['consultant_id']; ?>">
                                                    <button type="submit" name="verify_consultant" class="btn btn-success">Verify Consultant</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include('includes/footer.php');
?>