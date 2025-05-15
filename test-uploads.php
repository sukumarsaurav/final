<?php
// Start session
session_start();

// Include required files
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Test profile upload
    if (isset($_FILES['test_profile']) && $_FILES['test_profile']['error'] == 0) {
        $upload_result = handle_user_file_upload(
            $user_id,
            $_FILES['test_profile'],
            'profile',
            [
                'max_size' => 2 * 1024 * 1024, // 2MB
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
                'filename_prefix' => 'test_profile_'
            ]
        );
        
        if ($upload_result['status']) {
            $success_message = "Profile image uploaded successfully! Path: " . $upload_result['file_path'];
            
            // Optional: Update the user's profile picture in the database
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param('si', $upload_result['file_path'], $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Update session
            $_SESSION['profile_picture'] = $upload_result['file_path'];
        } else {
            $error_message = "Profile upload error: " . $upload_result['message'];
        }
    }
    
    // Test document upload
    if (isset($_FILES['test_document']) && $_FILES['test_document']['error'] == 0) {
        $upload_result = handle_user_file_upload(
            $user_id,
            $_FILES['test_document'],
            'documents',
            [
                'max_size' => 5 * 1024 * 1024, // 5MB
                'allowed_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'filename_prefix' => 'test_document_'
            ]
        );
        
        if ($upload_result['status']) {
            $success_message .= "<br>Document uploaded successfully! Path: " . $upload_result['file_path'];
        } else {
            $error_message .= "<br>Document upload error: " . $upload_result['message'];
        }
    }
}

// Get list of existing user uploads
$user_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/users/' . $user_id;
$user_uploads = [];

if (is_dir($user_upload_dir)) {
    $profile_dir = $user_upload_dir . '/profile';
    $documents_dir = $user_upload_dir . '/documents';
    $banners_dir = $user_upload_dir . '/banners';
    
    // Get profile images
    if (is_dir($profile_dir)) {
        $files = scandir($profile_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $user_uploads['profile'][] = [
                    'name' => $file,
                    'path' => 'users/' . $user_id . '/profile/' . $file,
                    'url' => '/uploads/users/' . $user_id . '/profile/' . $file,
                    'size' => formatBytes(filesize($profile_dir . '/' . $file)),
                    'type' => 'Profile Image'
                ];
            }
        }
    }
    
    // Get documents
    if (is_dir($documents_dir)) {
        $files = scandir($documents_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $user_uploads['documents'][] = [
                    'name' => $file,
                    'path' => 'users/' . $user_id . '/documents/' . $file,
                    'url' => '/uploads/users/' . $user_id . '/documents/' . $file,
                    'size' => formatBytes(filesize($documents_dir . '/' . $file)),
                    'type' => 'Document'
                ];
            }
        }
    }
    
    // Get banners
    if (is_dir($banners_dir)) {
        $files = scandir($banners_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $user_uploads['banners'][] = [
                    'name' => $file,
                    'path' => 'users/' . $user_id . '/banners/' . $file,
                    'url' => '/uploads/users/' . $user_id . '/banners/' . $file,
                    'size' => formatBytes(filesize($banners_dir . '/' . $file)),
                    'type' => 'Banner Image'
                ];
            }
        }
    }
}

// Get current profile image
$profile_img = '/assets/images/default-profile.svg';
if (!empty($_SESSION['profile_picture'])) {
    $profile_img = get_user_file_url($_SESSION['profile_picture']);
}

// Page title
$page_title = "Upload Test";

// Include header
include 'includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="row">
        <div class="col-md-12">
            <h1>User Upload Test</h1>
            <p>This page tests the new user-specific upload directory structure.</p>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Current Profile Image</h2>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <img src="<?php echo $profile_img; ?>" alt="Profile" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                        <div>
                            <p><strong>Path:</strong> <?php echo $_SESSION['profile_picture'] ?? 'None'; ?></p>
                            <p><strong>URL:</strong> <?php echo $profile_img; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Test Uploads</h2>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="test_profile" class="form-label">Upload Profile Image</label>
                            <input type="file" class="form-control" id="test_profile" name="test_profile" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text text-muted">Max size: 2MB. Allowed types: JPG, PNG, GIF</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="test_document" class="form-label">Upload Document</label>
                            <input type="file" class="form-control" id="test_document" name="test_document" accept=".pdf, .doc, .docx">
                            <small class="form-text text-muted">Max size: 5MB. Allowed types: PDF, DOC, DOCX</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Upload Files</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Existing User Uploads</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($user_uploads)): ?>
                        <p>No uploads found for this user.</p>
                    <?php else: ?>
                        <div class="accordion" id="uploadsAccordion">
                            <?php if (!empty($user_uploads['profile'])): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="profileHeader">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profileCollapse" aria-expanded="true" aria-controls="profileCollapse">
                                            Profile Images (<?php echo count($user_uploads['profile']); ?>)
                                        </button>
                                    </h2>
                                    <div id="profileCollapse" class="accordion-collapse collapse show" aria-labelledby="profileHeader">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php foreach ($user_uploads['profile'] as $file): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card">
                                                            <img src="<?php echo $file['url']; ?>" class="card-img-top" alt="<?php echo $file['name']; ?>" style="height: 200px; object-fit: cover;">
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?php echo $file['name']; ?></h5>
                                                                <p class="card-text">
                                                                    Size: <?php echo $file['size']; ?><br>
                                                                    Path: <?php echo $file['path']; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($user_uploads['documents'])): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="documentsHeader">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#documentsCollapse" aria-expanded="false" aria-controls="documentsCollapse">
                                            Documents (<?php echo count($user_uploads['documents']); ?>)
                                        </button>
                                    </h2>
                                    <div id="documentsCollapse" class="accordion-collapse collapse" aria-labelledby="documentsHeader">
                                        <div class="accordion-body">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Size</th>
                                                        <th>Path</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($user_uploads['documents'] as $file): ?>
                                                        <tr>
                                                            <td><?php echo $file['name']; ?></td>
                                                            <td><?php echo $file['size']; ?></td>
                                                            <td><?php echo $file['path']; ?></td>
                                                            <td><a href="<?php echo $file['url']; ?>" target="_blank" class="btn btn-sm btn-primary">View</a></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($user_uploads['banners'])): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="bannersHeader">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bannersCollapse" aria-expanded="false" aria-controls="bannersCollapse">
                                            Banner Images (<?php echo count($user_uploads['banners']); ?>)
                                        </button>
                                    </h2>
                                    <div id="bannersCollapse" class="accordion-collapse collapse" aria-labelledby="bannersHeader">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php foreach ($user_uploads['banners'] as $file): ?>
                                                    <div class="col-md-12 mb-3">
                                                        <div class="card">
                                                            <img src="<?php echo $file['url']; ?>" class="card-img-top" alt="<?php echo $file['name']; ?>" style="height: 200px; object-fit: cover;">
                                                            <div class="card-body">
                                                                <h5 class="card-title"><?php echo $file['name']; ?></h5>
                                                                <p class="card-text">
                                                                    Size: <?php echo $file['size']; ?><br>
                                                                    Path: <?php echo $file['path']; ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?> 