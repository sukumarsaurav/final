<?php
// Set page title
$page_title = "My Applications - Applicant";

// Include header
include('includes/header.php');

// Process document upload if form is submitted
$upload_message = '';
$upload_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_document'])) {
    $application_id = $_POST['application_id'];
    $document_type = $_POST['document_type'];
    
    // Check if file was uploaded without errors
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['document_file']['type'];
        $file_size = $_FILES['document_file']['size'];
        
        // Validate file type and size
        if (!in_array($file_type, $allowed_types)) {
            $upload_error = "Error: Only PDF, JPEG, and PNG files are allowed.";
        } elseif ($file_size > $max_size) {
            $upload_error = "Error: File size exceeds the 5MB limit.";
        } else {
            // Create directory if it doesn't exist
            $upload_dir = "../../uploads/applications/{$application_id}/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('doc_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_path)) {
                // Save document info in database
                $relative_path = "applications/{$application_id}/{$new_filename}";
                $original_filename = $_FILES['document_file']['name'];
                
                $stmt = $conn->prepare("INSERT INTO application_documents (application_id, document_type, file_path, original_filename, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("isssi", $application_id, $document_type, $relative_path, $original_filename, $user_id);
                
                if ($stmt->execute()) {
                    $upload_message = "Document uploaded successfully.";
                    
                    // Update application status if needed
                    $stmt = $conn->prepare("UPDATE applications SET status = 'documents_submitted', updated_at = NOW() WHERE id = ? AND status = 'documents_requested'");
                    $stmt->bind_param("i", $application_id);
                    $stmt->execute();
                } else {
                    $upload_error = "Error: Failed to save document information.";
                }
                $stmt->close();
            } else {
                $upload_error = "Error: Failed to upload file.";
            }
        }
    } else {
        $upload_error = "Error: Please select a file to upload.";
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">My Applications</h1>
        <p class="page-subtitle">Manage your visa applications and track their progress</p>
    </div>

    <?php if (!empty($upload_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $upload_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($upload_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $upload_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Applications List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Applications</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="applications-table">
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Visa Type</th>
                            <th>Status</th>
                            <th>Consultant</th>
                            <th>Created Date</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all applications for the user
                        $stmt = $conn->prepare("SELECT a.id, a.visa_type, a.status, a.created_at, a.updated_at,
                                              u.first_name, u.last_name
                                              FROM applications a 
                                              LEFT JOIN users u ON a.consultant_id = u.id
                                              WHERE a.applicant_id = ? 
                                              ORDER BY a.updated_at DESC");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $applications_result = $stmt->get_result();
                        
                        if ($applications_result->num_rows > 0) {
                            while ($app = $applications_result->fetch_assoc()) {
                                $consultant_name = !empty($app['first_name']) ? 
                                    htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) : 
                                    'Not assigned';
                                
                                echo '<tr>';
                                echo '<td>#' . $app['id'] . '</td>';
                                echo '<td>' . htmlspecialchars($app['visa_type']) . '</td>';
                                echo '<td><span class="badge bg-' . getStatusColor($app['status']) . '">' . formatStatus($app['status']) . '</span></td>';
                                echo '<td>' . $consultant_name . '</td>';
                                echo '<td>' . date('M d, Y', strtotime($app['created_at'])) . '</td>';
                                echo '<td>' . date('M d, Y', strtotime($app['updated_at'])) . '</td>';
                                echo '<td>';
                                echo '<a href="application-details.php?id=' . $app['id'] . '" class="btn btn-sm btn-outline-primary me-1">View</a>';
                                
                                // Show upload button if documents are requested
                                if ($app['status'] == 'documents_requested') {
                                    echo '<button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal" data-application-id="' . $app['id'] . '">Upload Documents</button>';
                                }
                                
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">No applications found</td></tr>';
                        }
                        $stmt->close();
                        
                        function getStatusColor($status) {
                            switch ($status) {
                                case 'pending':
                                case 'documents_requested':
                                    return 'warning';
                                case 'in_progress':
                                case 'documents_submitted':
                                    return 'info';
                                case 'approved':
                                    return 'success';
                                case 'rejected':
                                    return 'danger';
                                default:
                                    return 'secondary';
                            }
                        }
                        
                        function formatStatus($status) {
                            return ucwords(str_replace('_', ' ', $status));
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Document Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="application_id" value="">
                        
                        <div class="mb-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type" required>
                                <option value="">Select document type</option>
                                <option value="passport">Passport</option>
                                <option value="id_card">ID Card</option>
                                <option value="photo">Photo</option>
                                <option value="bank_statement">Bank Statement</option>
                                <option value="employment_letter">Employment Letter</option>
                                <option value="education_certificate">Education Certificate</option>
                                <option value="marriage_certificate">Marriage Certificate</option>
                                <option value="medical_report">Medical Report</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="document_file" class="form-label">Select File</label>
                            <input class="form-control" type="file" id="document_file" name="document_file" required>
                            <div class="form-text">Accepted formats: PDF, JPEG, PNG. Maximum size: 5MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_document" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Set application ID in modal when button is clicked
document.addEventListener('DOMContentLoaded', function() {
    const uploadModal = document.getElementById('uploadModal');
    if (uploadModal) {
        uploadModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const applicationId = button.getAttribute('data-application-id');
            document.getElementById('application_id').value = applicationId;
        });
    }
    
    // Initialize DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#applications-table').DataTable({
            "order": [[5, "desc"]], // Sort by last updated by default
            "pageLength": 10,
            "language": {
                "emptyTable": "No applications found"
            }
        });
    }
});
</script>

<?php
// Include footer
include('includes/footer.php');
?> 