<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db_connect.php';

// Check if consultant_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: book-service.php");
    exit;
}

$consultant_id = intval($_GET['id']);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Get consultant information including profile details
$query = "SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    u.email,
    u.phone,
    u.profile_picture,
    c.company_name,
    cp.bio,
    cp.specializations,
    cp.years_experience,
    cp.education,
    cp.certifications,
    cp.languages,
    cp.website,
    cp.social_linkedin,
    cp.social_twitter,
    cp.social_facebook,
    cp.banner_image,
    cp.is_verified,
    cp.verified_at,
    o.id AS organization_id,
    o.name AS organization_name
FROM 
    users u
JOIN 
    consultants c ON u.id = c.user_id
JOIN 
    organizations o ON u.organization_id = o.id
LEFT JOIN 
    consultant_profiles cp ON u.id = cp.consultant_id
WHERE 
    u.id = ? AND u.status = 'active' AND u.deleted_at IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: book-service.php");
    exit;
}

$consultant = $result->fetch_assoc();
$stmt->close();

// Get profile picture URL
$profile_img = '/assets/images/default-profile.jpg';
if (!empty($consultant['profile_picture'])) {
    // Fix profile picture path - add 'uploads/' if not present
    $profile_picture = $consultant['profile_picture'];
    if (strpos($profile_picture, 'users/') === 0) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $profile_picture)) {
            $profile_img = '/uploads/' . $profile_picture;
        }
    }
}

// Get banner image
$banner_img = '/assets/images/default-banner.jpg';
if (!empty($consultant['banner_image'])) {
    if (strpos($consultant['banner_image'], 'users/') === 0) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $consultant['banner_image'])) {
            $banner_img = '/uploads/' . $consultant['banner_image'];
        }
    } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/banners/' . $consultant['banner_image'])) {
        $banner_img = '/uploads/banners/' . $consultant['banner_image'];
    }
}

// Get services offered by the consultant
$services_query = "SELECT 
    vs.visa_service_id,
    vs.base_price,
    vs.description,
    v.visa_type,
    st.service_name,
    c.country_name
FROM 
    visa_services vs
JOIN 
    visas v ON vs.visa_id = v.visa_id
JOIN 
    service_types st ON vs.service_type_id = st.service_type_id
JOIN 
    countries c ON v.country_id = c.country_id
WHERE 
    vs.consultant_id = ? AND vs.is_active = 1
ORDER BY 
    v.visa_type, st.service_name";

$stmt = $conn->prepare($services_query);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$services_result = $stmt->get_result();
$services = [];

if ($services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}
$stmt->close();

// Get client reviews and ratings
$client_reviews_query = "SELECT 
    bf.id,
    bf.booking_id,
    bf.rating,
    bf.feedback,
    bf.created_at,
    CONCAT(u.first_name, ' ', u.last_name) AS client_name,
    u.profile_picture AS client_picture
FROM 
    booking_feedback bf
JOIN 
    bookings b ON bf.booking_id = b.id
JOIN 
    users u ON b.user_id = u.id
WHERE 
    b.consultant_id = ? AND bf.rating > 0
ORDER BY 
    bf.created_at DESC";

$stmt = $conn->prepare($client_reviews_query);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$client_reviews_result = $stmt->get_result();
$client_reviews = [];
$avg_rating = 0;
$reviews_count = 0;

if ($client_reviews_result->num_rows > 0) {
    $total_rating = 0;
    while ($row = $client_reviews_result->fetch_assoc()) {
        $client_reviews[] = $row;
        $total_rating += $row['rating'];
    }
    $reviews_count = count($client_reviews);
    $avg_rating = $reviews_count > 0 ? round($total_rating / $reviews_count, 1) : 0;
}
$stmt->close();

// Get recent bookings
$recent_bookings_query = "SELECT 
    b.id,
    b.booking_datetime,
    b.duration_minutes,
    bs.name AS status,
    CONCAT(u.first_name, ' ', u.last_name) AS client_name
FROM 
    bookings b
JOIN 
    booking_statuses bs ON b.status_id = bs.id
JOIN 
    users u ON b.user_id = u.id
WHERE 
    b.consultant_id = ? AND b.deleted_at IS NULL
ORDER BY 
    b.booking_datetime DESC
LIMIT 10";

$stmt = $conn->prepare($recent_bookings_query);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$recent_bookings_result = $stmt->get_result();
$recent_bookings = [];

if ($recent_bookings_result->num_rows > 0) {
    while ($row = $recent_bookings_result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}
$stmt->close();

$page_title = $consultant['consultant_name'] . " - Consultant Profile";
require_once 'includes/header.php';
?>

<!-- CSS Styles -->
<style>
:root {
    --primary-color: #eaaa34;
    --primary-light: rgba(234, 170, 52, 0.1);
    --primary-medium: rgba(234, 170, 52, 0.2);
    --secondary-color: #042167;
    --text-color: #333;
    --text-light: #666;
    --background-light: #f8f9fa;
    --white: #fff;
    --border-color: #e5e7eb;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --border-radius: 0.5rem;
    --border-radius-lg: 1rem;
    --transition: all 0.3s ease;
}

/* Hero Banner */
.consultant-banner {
    height: 500px;
    background-size: cover;
    background-position: center;
    position: relative;
    margin-bottom: 0;
}

.banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
    display: flex;
    align-items: center;
}

.hero-flex {
    display: flex;
    align-items: center;
}

.hero-profile-image {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    overflow: hidden;
    border: 5px solid var(--white);
    box-shadow: var(--shadow);
    margin-right: 30px;
    flex-shrink: 0;
}

.hero-profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-content {
    color: var(--white);
    max-width: 800px;
}

.hero-content h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--white);
}

.hero-content .company-name {
    font-size: 1.5rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
    color: var(--white);
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 25px;
}

.hero-meta .meta-item {
    display: flex;
    align-items: center;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
}

.hero-meta .meta-item i {
    margin-right: 8px;
}

.verified-badge-large {
    background-color: #28a745;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 16px;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.social-links {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.social-link:hover {
    background-color: var(--primary-color);
    transform: translateY(-3px);
}

.hero-actions {
    display: flex;
    gap: 15px;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 40px;
}

.content-section {
    margin-bottom: 40px;
}

.content-section h2 {
    color: var(--secondary-color);
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.content-section h3 {
    color: var(--secondary-color);
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 15px;
}

/* Cards */
.card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 20px;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    background-color: var(--primary-light);
}

.card-body {
    padding: 20px;
}

.card-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    background-color: var(--background-light);
}

/* Services Grid */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.service-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
    border: 1px solid var(--border-color);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.service-header {
    padding: 15px 20px;
    background-color: var(--primary-light);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.service-header h4 {
    margin: 0;
    color: var(--secondary-color);
    font-size: 1.1rem;
}

.service-price {
    font-weight: 700;
    color: var(--primary-color);
}

.service-body {
    padding: 20px;
    flex-grow: 1;
}

.visa-type {
    color: var(--text-light);
    margin-bottom: 10px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}

.visa-type i {
    margin-right: 8px;
    color: var(--secondary-color);
}

.service-description {
    color: var(--text-color);
    margin-bottom: 0;
    line-height: 1.5;
}

.service-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    background-color: var(--background-light);
    text-align: right;
}

/* Reviews */
.reviews-summary {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.rating-large {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.rating-number {
    font-size: 3rem;
    font-weight: 700;
    color: var(--secondary-color);
    line-height: 1;
}

.stars-large {
    color: #f8bb00;
    font-size: 1.5rem;
    margin: 10px 0;
}

.reviews-count {
    color: var(--text-light);
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    padding: 20px;
    border: 1px solid var(--border-color);
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.reviewer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    background-color: var(--background-light);
    flex-shrink: 0;
}

.reviewer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.default-avatar {
    width: 100%;
    height: 100%;
    background-color: var(--secondary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
}

.reviewer-details h5 {
    margin: 0 0 5px;
    color: var(--text-color);
    font-size: 1rem;
    font-weight: 600;
}

.review-date {
    color: var(--text-light);
    font-size: 0.85rem;
}

.review-rating {
    color: #f8bb00;
}

.review-body {
    color: var(--text-color);
    line-height: 1.6;
}

/* Bookings */
.booking-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

.booking-item:last-child {
    border-bottom: none;
}

.booking-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    flex-shrink: 0;
}

.booking-content {
    flex: 1;
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.booking-client {
    font-weight: 600;
    margin: 0;
}

.booking-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: var(--text-light);
}

.booking-meta span {
    display: flex;
    align-items: center;
}

.booking-meta i {
    margin-right: 5px;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-completed {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-confirmed {
    background-color: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.status-pending {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.status-cancelled_by_user, .status-cancelled_by_admin, .status-cancelled_by_consultant {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
    border: none;
}

.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 0.9rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: transparent;
    color: var(--white);
    border: 1px solid var(--white);
}

.btn-secondary:hover {
    background-color: var(--white);
    color: var(--secondary-color);
    transform: translateY(-2px);
}

.btn-outline {
    background-color: var(--white);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background-color: var(--background-light);
    transform: translateY(-2px);
}

/* Grid Layout */
.grid-2-cols {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

/* Tabs for Mobile */
.profile-tabs {
    padding: 0 30px 30px;
    display: none;
}

.nav-tabs {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    border-bottom: 1px solid var(--border-color);
}

.nav-item {
    margin-bottom: -1px;
}

.nav-link {
    display: block;
    padding: 15px 20px;
    color: var(--text-color);
    text-decoration: none;
    font-weight: 500;
    border: 1px solid transparent;
    border-top-left-radius: var(--border-radius);
    border-top-right-radius: var(--border-radius);
    transition: var(--transition);
}

.nav-link:hover {
    color: var(--primary-color);
}

.nav-link.active {
    color: var(--secondary-color);
    background-color: var(--white);
    border-color: var(--border-color);
    border-bottom-color: var(--white);
    font-weight: 600;
}

.tab-content {
    padding-top: 30px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.tab-pane.show {
    opacity: 1;
}

.tab-section {
    margin-bottom: 30px;
}

.tab-section h3 {
    color: var(--secondary-color);
    margin-bottom: 20px;
    font-size: 1.5rem;
    font-weight: 600;
}

.tab-section h4 {
    color: var(--text-color);
    margin: 25px 0 10px;
    font-size: 1.2rem;
    font-weight: 600;
}

.bio, .specializations, .education, .certifications {
    margin-bottom: 20px;
    line-height: 1.6;
    color: var(--text-color);
}

/* Fade animation for tab transitions */
.fade {
    transition: opacity 0.15s linear;
}

.fade:not(.show) {
    opacity: 0;
}

/* Responsive styles */
@media (max-width: 991px) {
    .hero-flex {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-profile-image {
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    .hero-meta {
        justify-content: center;
    }
    
    .hero-actions {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .content-grid {
        display: none;
    }
    
    .profile-tabs {
        display: block;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-item {
        flex: 1 1 auto;
        text-align: center;
        min-width: 120px;
    }
}

@media (max-width: 767px) {
    .consultant-banner {
        height: 400px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content .company-name {
        font-size: 1.2rem;
    }
    
    .hero-profile-image {
        width: 120px;
        height: 120px;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .grid-2-cols {
        grid-template-columns: 1fr;
    }
    
    .nav-item {
        min-width: 100px;
    }
    
    .nav-link {
        padding: 10px 15px;
        font-size: 0.9rem;
    }
}
</style>

<!-- Hero Banner Section -->
<div class="consultant-banner" style="background-image: url('<?php echo $banner_img; ?>');">
    <div class="banner-overlay">
        <div class="container">
            <div class="hero-flex">
                <div class="hero-profile-image">
                    <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($consultant['consultant_name']); ?>">
                </div>
                
                <div class="hero-content">
                    <h1><?php echo htmlspecialchars($consultant['consultant_name']); ?></h1>
                    <p class="company-name"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                    
                    <div class="hero-meta">
                        <?php if (!empty($consultant['years_experience'])): ?>
                            <span class="meta-item"><i class="fas fa-briefcase"></i> <?php echo $consultant['years_experience']; ?> years experience</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['languages'])): ?>
                            <span class="meta-item"><i class="fas fa-globe"></i> <?php echo htmlspecialchars($consultant['languages']); ?></span>
                        <?php endif; ?>
                        
                        <span class="meta-item"><i class="fas fa-star"></i> <?php echo $avg_rating; ?> (<?php echo $reviews_count; ?> reviews)</span>
                        
                        <?php if (!empty($consultant['is_verified'])): ?>
                            <span class="verified-badge-large">
                                <i class="fas fa-check-circle"></i> Verified by Visafy
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="social-links">
                        <?php if (!empty($consultant['website'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['website']); ?>" target="_blank" class="social-link">
                                <i class="fas fa-globe"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_linkedin']); ?>" target="_blank" class="social-link">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['social_twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_twitter']); ?>" target="_blank" class="social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($consultant['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($consultant['social_facebook']); ?>" target="_blank" class="social-link">
                                <i class="fab fa-facebook"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hero-actions">
                        <a href="book-consultation.php?consultant_id=<?php echo $consultant_id; ?>" class="btn btn-primary btn-lg">Book Consultation</a>
                        <a href="contact-consultant.php?id=<?php echo $consultant_id; ?>" class="btn btn-secondary btn-lg">Contact</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Desktop Layout -->
    <div class="content-grid">
        <!-- Main Content -->
        <div class="main-content">
            <!-- About Section -->
            <div class="content-section">
                <h2>About <?php echo htmlspecialchars($consultant['consultant_name']); ?></h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($consultant['bio'])): ?>
                            <div class="bio">
                                <?php echo nl2br(htmlspecialchars($consultant['bio'])); ?>
                            </div>
                        <?php else: ?>
                            <p>No bio information available.</p>
                        <?php endif; ?>
                        
                        <div class="grid-2-cols">
                            <?php if (!empty($consultant['specializations'])): ?>
                                <div class="specializations">
                                    <h3>Specializations</h3>
                                    <p><?php echo nl2br(htmlspecialchars($consultant['specializations'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['education'])): ?>
                                <div class="education">
                                    <h3>Education</h3>
                                    <p><?php echo nl2br(htmlspecialchars($consultant['education'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($consultant['certifications'])): ?>
                                <div class="certifications">
                                    <h3>Certifications</h3>
                                    <p><?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Section -->
            <div class="content-section">
                <h2>Services Offered</h2>
                <?php if (empty($services)): ?>
                    <div class="card">
                        <div class="card-body">
                            <p>No services are currently listed for this consultant.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="services-grid">
                        <?php foreach ($services as $service): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                                    <span class="service-price">$<?php echo number_format($service['base_price'], 2); ?></span>
                                </div>
                                <div class="service-body">
                                    <p class="visa-type">
                                        <i class="fas fa-passport"></i> 
                                        <?php echo htmlspecialchars($service['visa_type']); ?> - 
                                        <?php echo htmlspecialchars($service['country_name']); ?>
                                    </p>
                                    <?php if (!empty($service['description'])): ?>
                                        <p class="service-description"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="service-footer">
                                    <a href="book-consultation.php?consultant_id=<?php echo $consultant_id; ?>&service_id=<?php echo $service['visa_service_id']; ?>" class="btn btn-primary btn-sm">Book This Service</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Reviews Section -->
            <div class="content-section">
                <h2>Client Reviews</h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($client_reviews)): ?>
                            <p>No reviews yet. Be the first to leave a review after your consultation.</p>
                        <?php else: ?>
                            <div class="reviews-summary">
                                <div class="rating-large">
                                    <span class="rating-number"><?php echo $avg_rating; ?></span>
                                    <div class="stars-large">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= floor($avg_rating)): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="reviews-count"><?php echo $reviews_count; ?> reviews</span>
                                </div>
                            </div>
                            
                            <div class="reviews-list">
                                <?php foreach (array_slice($client_reviews, 0, 2) as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="reviewer-info">
                                                <div class="reviewer-avatar">
                                                    <?php if (!empty($review['client_picture'])): ?>
                                                        <?php 
                                                        $client_img = '/assets/images/default-profile.jpg';
                                                        if (strpos($review['client_picture'], 'users/') === 0) {
                                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $review['client_picture'])) {
                                                                $client_img = '/uploads/' . $review['client_picture'];
                                                            }
                                                        } else if (strpos($review['client_picture'], 'user/') === 0) {
                                                            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $review['client_picture'])) {
                                                                $client_img = '/uploads/' . $review['client_picture'];
                                                            }
                                                        }
                                                        ?>
                                                        <img src="<?php echo $client_img; ?>" alt="<?php echo htmlspecialchars($review['client_name']); ?>">
                                                    <?php else: ?>
                                                        <div class="default-avatar">
                                                            <?php echo strtoupper(substr($review['client_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="reviewer-details">
                                                    <h5><?php echo htmlspecialchars($review['client_name']); ?></h5>
                                                    <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="review-body">
                                            <p><?php echo nl2br(htmlspecialchars($review['feedback'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($client_reviews) > 2): ?>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="#" class="btn btn-outline btn-sm" onclick="document.getElementById('reviews-tab').click(); return false;">View all <?php echo $reviews_count; ?> reviews</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings Section -->
            <div class="content-section">
                <h2>Recent Bookings</h2>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <p>No recent bookings found.</p>
                        <?php else: ?>
                            <div class="recent-bookings-list">
                                <?php foreach (array_slice($recent_bookings, 0, 3) as $booking): ?>
                                    <div class="booking-item">
                                        <div class="booking-icon">
                                            <i class="fas fa-calendar"></i>
                                        </div>
                                        <div class="booking-content">
                                            <div class="booking-header">
                                                <p class="booking-client"><?php echo htmlspecialchars($booking['client_name']); ?></p>
                                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                    <?php echo ucfirst(explode('_', $booking['status'])[0]); ?>
                                                </span>
                                            </div>
                                            <div class="booking-meta">
                                                <span><i class="fas fa-calendar-day"></i><?php echo date('M d', strtotime($booking['booking_datetime'])); ?></span>
                                                <span><i class="fas fa-clock"></i><?php echo date('h:i A', strtotime($booking['booking_datetime'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($recent_bookings) > 3): ?>
                                <div style="text-align: center; margin-top: 15px;">
                                    <a href="#" class="btn btn-outline btn-sm" onclick="document.getElementById('bookings-tab').click(); return false;">View all bookings</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Tabs Layout -->
    <div class="profile-tabs">
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="about-tab" data-toggle="tab" href="#about" role="tab" aria-controls="about" aria-selected="true">About</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="services-tab" data-toggle="tab" href="#services" role="tab" aria-controls="services" aria-selected="false">Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="reviews-tab" data-toggle="tab" href="#reviews" role="tab" aria-controls="reviews" aria-selected="false">Reviews</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="bookings-tab" data-toggle="tab" href="#bookings" role="tab" aria-controls="bookings" aria-selected="false">Bookings</a>
            </li>
        </ul>
        
        <div class="tab-content" id="profileTabsContent">
            <!-- About Tab -->
            <div class="tab-pane fade show active" id="about" role="tabpanel" aria-labelledby="about-tab">
                <div class="tab-section">
                    <h3>About <?php echo htmlspecialchars($consultant['consultant_name']); ?></h3>
                    <?php if (!empty($consultant['bio'])): ?>
                        <div class="bio">
                            <?php echo nl2br(htmlspecialchars($consultant['bio'])); ?>
                        </div>
                    <?php else: ?>
                        <p>No bio information available.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['specializations'])): ?>
                        <div class="specializations">
                            <h4>Specializations</h4>
                            <p><?php echo nl2br(htmlspecialchars($consultant['specializations'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['education'])): ?>
                        <div class="education">
                            <h4>Education</h4>
                            <p><?php echo nl2br(htmlspecialchars($consultant['education'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($consultant['certifications'])): ?>
                        <div class="certifications">
                            <h4>Certifications</h4>
                            <p><?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Services Tab -->
            <div class="tab-pane fade" id="services" role="tabpanel" aria-labelledby="services-tab">
                <div class="tab-section">
                    <h3>Services Offered</h3>
                    <?php if (empty($services)): ?>
                        <p>No services are currently listed for this consultant.</p>
                    <?php else: ?>
                        <div class="services-grid">
                            <?php foreach ($services as $service): ?>
                                <div class="service-card">
                                    <div class="service-header">
                                        <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                                        <span class="service-price">$<?php echo number_format($service['base_price'], 2); ?></span>
                                    </div>
                                    <div class="service-body">
                                        <p class="visa-type">
                                            <i class="fas fa-passport"></i> 
                                            <?php echo htmlspecialchars($service['visa_type']); ?> - 
                                            <?php echo htmlspecialchars($service['country_name']); ?>
                                        </p>
                                        <?php if (!empty($service['description'])): ?>
                                            <p class="service-description"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-footer">
                                        <a href="book-consultation.php?consultant_id=<?php echo $consultant_id; ?>&service_id=<?php echo $service['visa_service_id']; ?>" class="btn btn-primary btn-sm">Book This Service</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Client Reviews Tab -->
            <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                <div class="tab-section">
                    <h3>Client Reviews</h3>
                    <?php if (empty($client_reviews)): ?>
                        <p>No reviews yet. Be the first to leave a review after your consultation.</p>
                    <?php else: ?>
                        <div class="reviews-summary">
                            <div class="rating-large">
                                <span class="rating-number"><?php echo $avg_rating; ?></span>
                                <div class="stars-large">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($avg_rating)): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $avg_rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="reviews-count"><?php echo $reviews_count; ?> reviews</span>
                            </div>
                        </div>
                        
                        <div class="reviews-list">
                            <?php foreach ($client_reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <?php if (!empty($review['client_picture'])): ?>
                                                    <?php 
                                                    $client_img = '/assets/images/default-profile.jpg';
                                                    if (strpos($review['client_picture'], 'users/') === 0) {
                                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $review['client_picture'])) {
                                                            $client_img = '/uploads/' . $review['client_picture'];
                                                        }
                                                    } else if (strpos($review['client_picture'], 'user/') === 0) {
                                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $review['client_picture'])) {
                                                            $client_img = '/uploads/' . $review['client_picture'];
                                                        }
                                                    }
                                                    ?>
                                                    <img src="<?php echo $client_img; ?>" alt="<?php echo htmlspecialchars($review['client_name']); ?>">
                                                <?php else: ?>
                                                    <div class="default-avatar">
                                                        <?php echo strtoupper(substr($review['client_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reviewer-details">
                                                <h5><?php echo htmlspecialchars($review['client_name']); ?></h5>
                                                <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-body">
                                        <p><?php echo nl2br(htmlspecialchars($review['feedback'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Bookings Tab -->
            <div class="tab-pane fade" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                <div class="tab-section">
                    <h3>Recent Bookings</h3>
                    <?php if (empty($recent_bookings)): ?>
                        <p>No recent bookings found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th>Client</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($booking['booking_datetime'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($booking['booking_datetime'])); ?></td>
                                            <td><?php echo $booking['duration_minutes']; ?> min</td>
                                            <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                                    <?php echo ucfirst(explode('_', $booking['status'])[0]); ?>
                                                </span>
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
</div>

<!-- JavaScript for tab functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabLinks = document.querySelectorAll('.nav-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.classList.remove('show');
                pane.classList.remove('active');
            });
            
            // Add active class to current tab and pane
            this.classList.add('active');
            const target = this.getAttribute('href').substring(1);
            const activePane = document.getElementById(target);
            activePane.classList.add('show');
            
            // Small delay for fade effect
            setTimeout(() => {
                activePane.classList.add('active');
            }, 150);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
