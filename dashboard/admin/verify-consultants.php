<?php
// Set page title
$page_title = "Admin Dashboard";

// Include header
include('includes/header.php');

$page_title = "Verify Consultants";
require_once '../includes/admin-header.php';

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $consultant_id = filter_input(INPUT_POST, 'consultant_id', FILTER_VALIDATE_INT);
    $verification_notes = filter_input(INPUT_POST, 'verification_notes', FILTER_SANITIZE_STRING);
    
    if ($consultant_id) {
        // Update consultant profile to mark as verified
        $verify_query = "UPDATE consultant_profiles 
                        SET is_verified = 1, 
                            verified_by = ?, 
                            verified_at = NOW() 
                        WHERE consultant_id = ?";
        
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param('ii', $_SESSION['user_id'], $consultant_id);
        
        if ($stmt->execute()) {
            // Update verification documents if they exist
            if (isset($_POST['document_ids']) && is_array($_POST['document_ids'])) {
                $doc_ids = array_map('intval', $_POST['document_ids']);
                
                $docs_query = "UPDATE consultant_verifications 
                              SET verified = 1, 
                                  verified_by = ?, 
                                  verified_at = NOW(), 
                                  notes = ? 
                              WHERE id IN (" . implode(',', $doc_ids) . ")";
                
                $docs_stmt = $conn->prepare($docs_query);
                $docs_stmt->bind_param('is', $_SESSION['user_id'], $verification_notes);
                $docs_stmt->execute();
            }
            
            // Create activity log
            $activity_query = "INSERT INTO activity_logs 
                              (user_id, activity_type, entity_type, entity_id, description) 
                              VALUES (?, 'verify', 'consultant', ?, 'Verified consultant profile')";
            
            $activity_stmt = $conn->prepare($activity_query);
            $activity_stmt->bind_param('ii', $_SESSION['user_id'], $consultant_id);
            $activity_stmt->execute();
            
            $_SESSION['success_message'] = "Consultant has been successfully verified.";
        } else {
            $_SESSION['error_message'] = "Error verifying consultant: " . $conn->error;
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: verify-consultants.php");
    exit;
}

// Get pending consultants for verification
$query = "SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    u.email,
    u.phone,
    c.company_name,
    c.registration_number,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.certifications,
    cp.languages,
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
    u.status = 'active' 
    AND u.deleted_at IS NULL
    AND u.user_type = 'consultant'
    AND (cp.is_verified IS NULL OR cp.is_verified = 0)
GROUP BY 
    u.id
ORDER BY 
    document_count DESC, u.created_at ASC";

$result = $conn->query($query);
$consultants = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Consultant Verification</h5>
                    <p class="text-muted">Review and verify consultant profiles after checking their documents</p>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($consultants)): ?>
                        <div class="alert alert-info">
                            No consultants are pending verification at this time.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Consultant</th>
                                        <th>Company</th>
                                        <th>Contact</th>
                                        <th>Specializations</th>
                                        <th>Documents</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consultants as $consultant): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($consultant['consultant_name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($consultant['company_name']); ?><br>
                                                <small>Reg #: <?php echo htmlspecialchars($consultant['registration_number'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($consultant['email']); ?><br>
                                                <?php echo htmlspecialchars($consultant['phone']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($consultant['specializations'] ?? 'Not specified'); ?>
                                            </td>
                                            <td>
                                                <?php if ($consultant['document_count'] > 0): ?>
                                                    <span class="badge bg-success"><?php echo $consultant['document_count']; ?> documents</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">No documents</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view-consultant.php?id=<?php echo $consultant['consultant_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#verifyModal<?php echo $consultant['consultant_id']; ?>">
                                                    <i class="fas fa-check-circle"></i> Verify
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Verification Modal -->
                                        <div class="modal fade" id="verifyModal<?php echo $consultant['consultant_id']; ?>" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="verify-consultants.php" method="post">
                                                        <input type="hidden" name="action" value="verify">
                                                        <input type="hidden" name="consultant_id" value="<?php echo $consultant['consultant_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="verifyModalLabel">Verify Consultant</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>You are about to verify <strong><?php echo htmlspecialchars($consultant['consultant_name']); ?></strong> from <strong><?php echo htmlspecialchars($consultant['company_name']); ?></strong>.</p>
                                                            
                                                            <p>Please confirm that you have reviewed all necessary documents and information.</p>
                                                            
                                                            <?php 
                                                            // Get consultant documents
                                                            $docs_query = "SELECT id, document_type, document_path, uploaded_at 
                                                                          FROM consultant_verifications 
                                                                          WHERE consultant_id = ? AND verified = 0";
                                                            $docs_stmt = $conn->prepare($docs_query);
                                                            $docs_stmt->bind_param('i', $consultant['consultant_id']);
                                                            $docs_stmt->execute();
                                                            $docs_result = $docs_stmt->get_result();
                                                            
                                                            if ($docs_result->num_rows > 0): 
                                                            ?>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Documents Reviewed:</label>
                                                                    <div class="list-group">
                                                                        <?php while ($doc = $docs_result->fetch_assoc()): ?>
                                                                            <div class="list-group-item">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" name="document_ids[]" value="<?php echo $doc['id']; ?>" id="doc<?php echo $doc['id']; ?>" checked>
                                                                                    <label class="form-check-label" for="doc<?php echo $doc['id']; ?>">
                                                                                        <?php echo ucwords(str_replace('_', ' ', $doc['document_type'])); ?>
                                                                                        <a href="../<?php echo htmlspecialchars($doc['document_path']); ?>" target="_blank" class="ms-2">
                                                                                            <i class="fas fa-external-link-alt"></i> View
                                                                                        </a>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        <?php endwhile; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-warning">
                                                                    This consultant has no documents uploaded. Verification should be based on other criteria.
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="mb-3">
                                                                <label for="verification_notes" class="form-label">Verification Notes:</label>
                                                                <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3" placeholder="Add notes about the verification (optional)"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Confirm Verification</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin-footer.php'; ?> 