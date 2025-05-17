<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

$page_title = "Service Details";
$page_specific_css = "assets/css/services.css";
require_once 'includes/header.php';

// Get service ID from URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$service_id) {
    die("Service ID not provided");
}

// Get consultant ID and organization ID from session
$consultant_id = isset($_SESSION['id']) ? $_SESSION['id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
$organization_id = isset($user['organization_id']) ? $user['organization_id'] : null;

// Verify organization ID is set
if (!$organization_id) {
    die("Organization ID not set. Please log in again.");
}

// Get service details with related information
$query = "SELECT vs.*, v.visa_type, c.country_name, st.service_name, 
          GROUP_CONCAT(DISTINCT cm.mode_name SEPARATOR ', ') as available_modes,
          GROUP_CONCAT(DISTINCT CONCAT(cm.mode_name, ' ($', scm.additional_fee, ', ', scm.duration_minutes, ' min)') SEPARATOR '|') as mode_details
          FROM visa_services vs
          JOIN visas v ON vs.visa_id = v.visa_id
          JOIN countries c ON v.country_id = c.country_id
          JOIN service_types st ON vs.service_type_id = st.service_type_id
          LEFT JOIN service_consultation_modes scm ON vs.visa_service_id = scm.visa_service_id
          LEFT JOIN consultation_modes cm ON scm.consultation_mode_id = cm.consultation_mode_id
          WHERE vs.visa_service_id = ? AND vs.organization_id = ?
          GROUP BY vs.visa_service_id";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $service_id, $organization_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$service) {
    die("Service not found or you don't have permission to view it");
}

// Get booking settings if service is bookable
$booking_settings = null;
if ($service['is_bookable']) {
    $query = "SELECT * FROM service_booking_settings WHERE visa_service_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $booking_settings = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get required documents
$query = "SELECT * FROM service_documents WHERE visa_service_id = ? ORDER BY document_name";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $service_id);
$stmt->execute();
$documents_result = $stmt->get_result();
$documents = [];

if ($documents_result && $documents_result->num_rows > 0) {
    while ($row = $documents_result->fetch_assoc()) {
        $documents[] = $row;
    }
}
$stmt->close();

// Get recent bookings
$query = "SELECT b.*, u.first_name, u.last_name, bs.name as status_name, bs.color as status_color
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN booking_statuses bs ON b.status_id = bs.id
          WHERE b.visa_service_id = ? AND b.deleted_at IS NULL
          ORDER BY b.booking_datetime DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $service_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$recent_bookings = [];

if ($bookings_result && $bookings_result->num_rows > 0) {
    while ($row = $bookings_result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}
$stmt->close();

// Get service reviews
$query = "SELECT sr.*, u.first_name, u.last_name
          FROM service_reviews sr
          JOIN users u ON sr.user_id = u.id
          WHERE sr.visa_service_id = ? AND sr.is_public = 1
          ORDER BY sr.created_at DESC
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $service_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];

if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}
$stmt->close();
?>

<div class="content">
    <div class="header-container">
        <div>
            <h1>Service Details</h1>
            <p>View and manage service information</p>
        </div>
        <div class="header-actions">
            <a href="edit_service.php?id=<?php echo $service_id; ?>" class="btn primary-btn">
                <i class="fas fa-edit"></i> Edit Service
            </a>
            <?php if ($service['is_bookable']): ?>
            <a href="service_availability.php?id=<?php echo $service_id; ?>" class="btn secondary-btn">
                <i class="fas fa-calendar-alt"></i> Manage Availability
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="service-details-container">
        <!-- Basic Information -->
        <div class="info-section">
            <h2>Basic Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Country</label>
                    <span><?php echo htmlspecialchars($service['country_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Visa Type</label>
                    <span><?php echo htmlspecialchars($service['visa_type']); ?></span>
                </div>
                <div class="info-item">
                    <label>Service Type</label>
                    <span><?php echo htmlspecialchars($service['service_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Base Price</label>
                    <span>$<?php echo number_format($service['base_price'], 2); ?></span>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <span class="status-badge <?php echo $service['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Bookable</label>
                    <span class="status-badge <?php echo $service['is_bookable'] ? 'bookable' : 'not-bookable'; ?>">
                        <i class="fas fa-<?php echo $service['is_bookable'] ? 'check' : 'times'; ?>-circle"></i>
                        <?php echo $service['is_bookable'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="info-section">
            <h2>Description</h2>
            <div class="description-box">
                <?php echo nl2br(htmlspecialchars($service['description'])); ?>
            </div>
        </div>

        <!-- Consultation Modes -->
        <div class="info-section">
            <h2>Consultation Modes</h2>
            <?php if (!empty($service['mode_details'])): ?>
            <div class="modes-grid">
                <?php foreach (explode('|', $service['mode_details']) as $mode): ?>
                <div class="mode-card">
                    <i class="fas fa-comments"></i>
                    <div class="mode-info">
                        <h4><?php echo htmlspecialchars(explode(' ($', $mode)[0]); ?></h4>
                        <p><?php echo htmlspecialchars(substr($mode, strpos($mode, '$'))); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No consultation modes configured</p>
            <?php endif; ?>
        </div>

        <?php if ($service['is_bookable'] && $booking_settings): ?>
        <!-- Booking Settings -->
        <div class="info-section">
            <h2>Booking Settings</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Minimum Notice</label>
                    <span><?php echo $booking_settings['min_notice_hours']; ?> hours</span>
                </div>
                <div class="info-item">
                    <label>Maximum Advance Booking</label>
                    <span><?php echo $booking_settings['max_advance_days']; ?> days</span>
                </div>
                <div class="info-item">
                    <label>Buffer Before</label>
                    <span><?php echo $booking_settings['buffer_before_minutes']; ?> minutes</span>
                </div>
                <div class="info-item">
                    <label>Buffer After</label>
                    <span><?php echo $booking_settings['buffer_after_minutes']; ?> minutes</span>
                </div>
                <div class="info-item">
                    <label>Payment Required</label>
                    <span class="status-badge <?php echo $booking_settings['payment_required'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $booking_settings['payment_required'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
            </div>
            <?php if ($booking_settings['payment_required']): ?>
            <div class="payment-info">
                <h4>Payment Details</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Deposit Amount</label>
                        <span>$<?php echo number_format($booking_settings['deposit_amount'], 2); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Deposit Percentage</label>
                        <span><?php echo $booking_settings['deposit_percentage']; ?>%</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Required Documents -->
        <div class="info-section">
            <h2>Required Documents</h2>
            <?php if (!empty($documents)): ?>
            <div class="documents-list">
                <?php foreach ($documents as $doc): ?>
                <div class="document-item">
                    <i class="fas fa-file-alt"></i>
                    <div class="document-info">
                        <h4><?php echo htmlspecialchars($doc['document_name']); ?></h4>
                        <?php if ($doc['description']): ?>
                        <p><?php echo htmlspecialchars($doc['description']); ?></p>
                        <?php endif; ?>
                        <span class="required-badge <?php echo $doc['is_required'] ? 'required' : 'optional'; ?>">
                            <?php echo $doc['is_required'] ? 'Required' : 'Optional'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No documents required</p>
            <?php endif; ?>
        </div>

        <!-- Recent Bookings -->
        <div class="info-section">
            <h2>Recent Bookings</h2>
            <?php if (!empty($recent_bookings)): ?>
            <div class="bookings-list">
                <?php foreach ($recent_bookings as $booking): ?>
                <div class="booking-item">
                    <div class="booking-header">
                        <span class="booking-ref">#<?php echo htmlspecialchars($booking['reference_number']); ?></span>
                        <span class="status-badge" style="background-color: <?php echo $booking['status_color']; ?>">
                            <?php echo htmlspecialchars($booking['status_name']); ?>
                        </span>
                    </div>
                    <div class="booking-details">
                        <p><strong>Client:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_datetime'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['booking_datetime'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No recent bookings</p>
            <?php endif; ?>
        </div>

        <!-- Reviews -->
        <div class="info-section">
            <h2>Recent Reviews</h2>
            <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                        </div>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="review-content">
                        <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                    </div>
                    <?php if ($review['consultant_response']): ?>
                    <div class="consultant-response">
                        <h5>Consultant Response:</h5>
                        <p><?php echo nl2br(htmlspecialchars($review['consultant_response'])); ?></p>
                        <small>Responded on <?php echo date('M d, Y', strtotime($review['responded_at'])); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No reviews yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.service-details-container {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.info-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--border-color);
}

.info-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.info-section h2 {
    color: var(--primary-color);
    font-size: 1.4rem;
    margin-bottom: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-item label {
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.info-item span {
    color: var(--dark-color);
    font-weight: 500;
}

.description-box {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 4px;
    color: var(--dark-color);
    line-height: 1.6;
}

.modes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.mode-card {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 4px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.mode-card i {
    color: var(--primary-color);
    font-size: 24px;
}

.mode-info h4 {
    margin: 0 0 5px;
    color: var(--primary-color);
}

.mode-info p {
    margin: 0;
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.payment-info {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.payment-info h4 {
    color: var(--primary-color);
    margin-bottom: 15px;
}

.documents-list {
    display: grid;
    gap: 15px;
}

.document-item {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 4px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.document-item i {
    color: var(--primary-color);
    font-size: 24px;
}

.document-info h4 {
    margin: 0 0 5px;
    color: var(--primary-color);
}

.document-info p {
    margin: 0 0 10px;
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.required-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.required-badge.required {
    background-color: rgba(231, 74, 59, 0.1);
    color: var(--danger-color);
}

.required-badge.optional {
    background-color: rgba(133, 135, 150, 0.1);
    color: var(--secondary-color);
}

.bookings-list {
    display: grid;
    gap: 15px;
}

.booking-item {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 4px;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.booking-ref {
    font-weight: 500;
    color: var(--primary-color);
}

.booking-details p {
    margin: 5px 0;
    color: var(--dark-color);
}

.reviews-list {
    display: grid;
    gap: 20px;
}

.review-item {
    background-color: var(--light-color);
    padding: 20px;
    border-radius: 4px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--primary-color);
}

.reviewer-info i {
    font-size: 24px;
}

.rating {
    display: flex;
    gap: 2px;
}

.rating i {
    color: #ddd;
}

.rating i.active {
    color: #ffc107;
}

.review-content {
    color: var(--dark-color);
    line-height: 1.6;
    margin-bottom: 15px;
}

.consultant-response {
    background-color: white;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.consultant-response h5 {
    color: var(--primary-color);
    margin: 0 0 10px;
}

.consultant-response p {
    color: var(--dark-color);
    margin: 0 0 10px;
}

.consultant-response small {
    color: var(--secondary-color);
    font-size: 0.8rem;
}

.text-muted {
    color: var(--secondary-color);
    font-style: italic;
}

.header-actions {
    display: flex;
    gap: 10px;
}
</style>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>
<?php require_once 'includes/footer.php'; ?>
