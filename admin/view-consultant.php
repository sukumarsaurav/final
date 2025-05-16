<?php
// Set page title
$page_title = "View Consultant";

// Include header
include('includes/header.php');

// Check if consultant ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Invalid consultant ID.</div></div>';
    include('includes/footer.php');
    exit;
}

$consultant_id = intval($_GET['id']);

// Fetch consultant details
$query = "SELECT 
    u.id AS consultant_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status,
    u.created_at,
    c.company_name,
    c.company_website,
    c.company_address,
    c.specialty_areas,
    c.years_of_experience,
    cp.bio,
    cp.is_verified,
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
    u.id = ? 
    AND u.user_type = 'consultant'
    AND u.deleted_at IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="container-fluid"><div class="alert alert-danger">Consultant not found.</div></div>';
    include('includes/footer.php');
    exit;
}

$consultant = $result->fetch_assoc();
$stmt->close();

// Fetch consultant documents
$docs_query = "SELECT 
    id, 
    document_type, 
    file_path, 
    uploaded_at,
    is_verified
FROM 
    consultant_documents 
WHERE 
    consultant_id = ?
ORDER BY 
    uploaded_at DESC";

$docs_stmt = $conn->prepare($docs_query);
$docs_stmt->bind_param("i", $consultant_id);
$docs_stmt->execute();
$docs_result = $docs_stmt->get_result();
$documents = [];

if ($docs_result->num_rows > 0) {
    while ($doc = $docs_result->fetch_assoc()) {
        $documents[] = $doc;
    }
}
$docs_stmt->close();

// Fetch consultant clients/applications
$clients_query = "SELECT 
    a.id AS application_id,
    a.status AS application_status,
    a.created_at AS application_date,
    CONCAT(u.first_name, ' ', u.last_name) AS applicant_name,
    u.email AS applicant_email
FROM 
    applications a
JOIN 
    users u ON a.applicant_id = u.id
WHERE 
    a.consultant_id = ?
ORDER BY 
    a.created_at DESC
LIMIT 10";

$clients_stmt = $conn->prepare($clients_query);
$clients_stmt->bind_param("i", $consultant_id);
$clients_stmt->execute();
$clients_result = $clients_stmt->get_result();
$clients = [];

if ($clients_result->num_rows > 0) {
    while ($client = $clients_result->fetch_assoc()) {
        $clients[] = $client;
    }
}
$clients_stmt->close();

// Process verification action if submitted
$action_message = '';
$action_error = '';

if (isset($_POST['action']) && $_POST['action'] === 'verify' && isset($_POST['consultant_id'])) {
    $verify_id = intval($_POST['consultant_id']);
    
    if ($verify_id === $consultant_id) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update consultant profile verification status
            $verify_query = "UPDATE consultant_profiles SET 
                is_verified = 1, 
                verified_at = NOW(), 
                verified_by = ? 
            WHERE consultant_id = ?";
            
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ii", $user_id, $consultant_id);
            $verify_stmt->execute();
            $verify_stmt->close();
            
            // Update documents verification status
            $docs_verify_query = "UPDATE consultant_documents SET 
                is_verified = 1 
            WHERE consultant_id = ?";
            
            $docs_verify_stmt = $conn->prepare($docs_verify_query);
            $docs_verify_stmt->bind_param("i", $consultant_id);
            $docs_verify_stmt->execute();
            $docs_verify_stmt->close();
            
            // Log the verification action
            $log_query = "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, created_at) 
            VALUES (?, 'verify', 'consultant', ?, 'Verified consultant account', NOW())";
            
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("ii", $user_id, $consultant_id);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $action_message = "Consultant has been verified successfully.";
            
            // Update the consultant data in our current view
            $consultant['is_verified'] = 1;
            $consultant['verified_at'] = date('Y-m-d H:i:s');
            $consultant['verified_by_name'] = $user_first_name . ' ' . $user_last_name;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $action_error = "Failed to verify consultant: " . $e->getMessage();
        }
    } else {
        $action_error = "Invalid consultant verification request.";
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
                <i class="fas fa-arrow-left"></i> Back to Consultants
            </a>
            <?php if ($consultant['status'] === 'active'): ?>
                <a href="consultants.php?action=suspend&id=<?php echo $consultant_id; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to suspend this consultant?')">
                    <i class="fas fa-ban"></i> Suspend Consultant
                </a>
            <?php else: ?>
                <a href="consultants.php?action=activate&id=<?php echo $consultant_id; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this consultant?')">
                    <i class="fas fa-check"></i> Activate Consultant
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-xl-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Consultant Information</h6>
                    <div>
                        <?php if ($consultant['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Suspended</span>
                        <?php endif; ?>
                        
                        <?php if ($consultant['is_verified'] == 1): ?>
                            <span class="badge bg-info">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Unverified</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="../assets/img/profile-placeholder.jpg" class="img-profile rounded-circle" style="width: 100px; height: 100px;">
                        <h4 class="mt-3"><?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?></h4>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                        <p class="small text-muted">Member since <?php echo date('M d, Y', strtotime($consultant['created_at'])); ?></p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Contact Information</h6>
                        <p>
                            <i class="fas fa-envelope text-primary mr-2"></i> 
                            <?php echo htmlspecialchars($consultant['email']); ?>
                        </p>
                        <p>
                            <i class="fas fa-phone text-primary mr-2"></i> 
                            <?php echo htmlspecialchars($consultant['phone']); ?>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Company Details</h6>
                        <p>
                            <i class="fas fa-building text-primary mr-2"></i> 
                            <?php echo htmlspecialchars($consultant['company_name']); ?>
                        </p>
                        <?php if (!empty($consultant['company_website'])): ?>
                        <p>
                            <i class="fas fa-globe text-primary mr-2"></i> 
                            <a href="<?php echo htmlspecialchars($consultant['company_website']); ?>" target="_blank">
                                <?php echo htmlspecialchars($consultant['company_website']); ?>
                            </a>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($consultant['company_address'])): ?>
                        <p>
                            <i class="fas fa-map-marker-alt text-primary mr-2"></i> 
                            <?php echo htmlspecialchars($consultant['company_address']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Professional Information</h6>
                        <p>
                            <i class="fas fa-briefcase text-primary mr-2"></i> 
                            <?php echo htmlspecialchars($consultant['years_of_experience']); ?> years of experience
                        </p>
                        <?php if (!empty($consultant['specialty_areas'])): ?>
                        <p>
                            <i class="fas fa-star text-primary mr-2"></i> 
                            Specialties: <?php echo htmlspecialchars($consultant['specialty_areas']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($consultant['is_verified'] == 1): ?>
                    <hr>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Verification Details</h6>
                        <p>
                            <i class="fas fa-user-check text-primary mr-2"></i> 
                            Verified by: <?php echo htmlspecialchars($consultant['verified_by_name']); ?>
                        </p>
                        <p>
                            <i class="fas fa-calendar-check text-primary mr-2"></i> 
                            Verified on: <?php echo date('M d, Y', strtotime($consultant['verified_at'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bio and Documents -->
        <div class="col-xl-8">
            <!-- Bio -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Consultant Bio</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($consultant['bio'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($consultant['bio'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No bio information provided.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Documents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Verification Documents</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-muted">No documents uploaded.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
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
                                            <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <?php if ($doc['is_verified'] == 1): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
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
            
            <!-- Clients/Applications -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Clients (Applications)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($clients)): ?>
                        <p class="text-muted">No clients/applications found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['applicant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($client['applicant_email']); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                switch($client['application_status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-info';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($client['application_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($client['application_date'])); ?></td>
                                            <td>
                                                <a href="view-application.php?id=<?php echo $client['application_id']; ?>" class="btn btn-sm btn-primary">
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
    
    <?php if ($consultant['is_verified'] == 0): ?>
    <!-- Verification Action -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Verification Action</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" name="consultant_id" value="<?php echo $consultant_id; ?>">
                        
                        <div class="alert alert-info">
                            <p>Please review all consultant information and documents before verification. Once verified, the consultant will be able to provide services to applicants.</p>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmVerification" required>
                            <label class="form-check-label" for="confirmVerification">
                                I confirm that I have reviewed all consultant information and documents, and they meet our verification requirements.
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to verify this consultant?')">
                            <i class="fas fa-user-check"></i> Verify Consultant
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include('includes/footer.php');
?> 