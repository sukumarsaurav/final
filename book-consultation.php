<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db_connect.php';

// Check if consultant_id is provided
if (!isset($_GET['consultant_id']) || empty($_GET['consultant_id'])) {
    header("Location: book-service.php");
    exit;
}

$consultant_id = intval($_GET['consultant_id']);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Get consultant information including profile details
$query = "SELECT 
    u.id AS consultant_id,
    CONCAT(u.first_name, ' ', u.last_name) AS consultant_name,
    u.email,
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
    // Check for new structure first (users/{user_id}/profile/...)
    if (strpos($consultant['profile_picture'], 'users/') === 0) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $consultant['profile_picture'])) {
            $profile_img = '/uploads/' . $consultant['profile_picture'];
        }
    } else {
        // Legacy structure
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/profiles/' . $consultant['profile_picture'])) {
            $profile_img = '/uploads/profiles/' . $consultant['profile_picture'];
        } else if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/profile/' . $consultant['profile_picture'])) {
            $profile_img = '/uploads/profile/' . $consultant['profile_picture'];
        }
    }
}

// Get banner image
$banner_img = '';
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

// Get consultation modes
$modes_query = "SELECT 
    cm.consultation_mode_id,
    cm.mode_name,
    cm.description,
    scm.service_consultation_id,
    scm.additional_fee
FROM 
    consultation_modes cm
JOIN 
    service_consultation_modes scm ON cm.consultation_mode_id = scm.consultation_mode_id
WHERE 
    scm.is_available = 1
GROUP BY 
    cm.consultation_mode_id
ORDER BY 
    cm.mode_name";

$modes_result = $conn->query($modes_query);
$modes = [];

if ($modes_result->num_rows > 0) {
    while ($row = $modes_result->fetch_assoc()) {
        $modes[] = $row;
    }
}

// Get consultant reviews and rating
$reviews_query = "SELECT 
    bf.id,
    bf.booking_id,
    bf.rating,
    bf.feedback,
    bf.created_at,
    CONCAT(u.first_name, ' ', u.last_name) AS client_name
FROM 
    booking_feedback bf
JOIN 
    bookings b ON bf.booking_id = b.id
JOIN 
    users u ON b.user_id = u.id
WHERE 
    b.consultant_id = ? AND bf.rating > 0
ORDER BY 
    bf.created_at DESC
LIMIT 5";

$stmt = $conn->prepare($reviews_query);
$stmt->bind_param('i', $consultant_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
$avg_rating = 0;
$reviews_count = 0;

if ($reviews_result->num_rows > 0) {
    $total_rating = 0;
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
        $total_rating += $row['rating'];
    }
    $reviews_count = count($reviews);
    $avg_rating = $reviews_count > 0 ? round($total_rating / $reviews_count, 1) : 0;
}
$stmt->close();

$page_title = "Book Consultation with " . $consultant['consultant_name'];
require_once 'includes/header.php';
?>

<div class="container">
    <div class="booking-header">
        <h1>Consultant Profile</h1>
        <h2><?php echo htmlspecialchars($consultant['consultant_name']); ?>
        <?php if (!empty($consultant['is_verified'])): ?>
            <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified by Visafy</span>
        <?php endif; ?>
        </h2>
    </div>
    
    <?php if (!empty($banner_img)): ?>
    <div class="consultant-banner">
        <img src="<?php echo $banner_img; ?>" alt="<?php echo htmlspecialchars($consultant['consultant_name']); ?> Banner" class="banner-image">
    </div>
    <?php endif; ?>
    
    <div class="profile-container">
        <div class="profile-sidebar">
            <div class="profile-image-container">
                <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($consultant['consultant_name']); ?>" class="profile-image">
            </div>
            
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($consultant['consultant_name']); ?></h3>
                <p class="company-name"><?php echo htmlspecialchars($consultant['company_name']); ?></p>
                <p class="organization-name"><?php echo htmlspecialchars($consultant['organization_name']); ?></p>
                
                <?php if (!empty($consultant['is_verified'])): ?>
                <div class="verification-info">
                    <i class="fas fa-check-circle"></i> 
                    <span>Verified by Visafy</span>
                    <?php if (!empty($consultant['verified_at'])): ?>
                    <p>Since <?php echo date('F j, Y', strtotime($consultant['verified_at'])); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="consultant-rating">
                    <div class="stars">
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
                    <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $reviews_count; ?> reviews)</span>
                </div>
                
                <?php if (!empty($consultant['years_experience'])): ?>
                <div class="experience">
                    <i class="fas fa-briefcase"></i> <?php echo $consultant['years_experience']; ?> years of experience
                </div>
                <?php endif; ?>
                
                <div class="contact-links">
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
                
                <div class="booking-cta">
                    <a href="#booking-section" class="btn btn-primary btn-block">Book a Consultation</a>
                </div>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="profile-section">
                <h3>About</h3>
                <?php if (!empty($consultant['bio'])): ?>
                    <div class="bio"><?php echo nl2br(htmlspecialchars($consultant['bio'])); ?></div>
                <?php else: ?>
                    <p>No bio available.</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($consultant['specializations'])): ?>
            <div class="profile-section">
                <h3>Specializations</h3>
                <div class="specializations">
                    <?php 
                    $specializations = explode(',', $consultant['specializations']);
                    foreach ($specializations as $specialization): 
                        $specialization = trim($specialization);
                        if (!empty($specialization)):
                    ?>
                        <span class="specialization-tag"><?php echo htmlspecialchars($specialization); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($consultant['languages'])): ?>
            <div class="profile-section">
                <h3>Languages</h3>
                <div class="languages">
                    <?php echo htmlspecialchars($consultant['languages']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($consultant['education'])): ?>
            <div class="profile-section">
                <h3>Education</h3>
                <div class="education">
                    <?php echo nl2br(htmlspecialchars($consultant['education'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($consultant['certifications'])): ?>
            <div class="profile-section">
                <h3>Certifications</h3>
                <div class="certifications">
                    <?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($services)): ?>
            <div class="profile-section">
                <h3>Services Offered</h3>
                <div class="services-list">
                    <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['country_name'] . ' - ' . $service['visa_type']); ?></h4>
                            <p class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></p>
                            <?php if (!empty($service['description'])): ?>
                            <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="service-price">
                            <span class="price">$<?php echo number_format($service['base_price'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($reviews)): ?>
            <div class="profile-section" id="reviews-section">
                <h3>Reviews</h3>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($review['client_name']); ?></span>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
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
                        <?php if (!empty($review['feedback'])): ?>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['feedback'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="profile-section" id="booking-section">
                <h3>Book a Consultation</h3>
                
                <?php if (!$is_logged_in): ?>
                <div class="login-required-message">
                    <p>You need to be logged in to book a consultation with this consultant.</p>
                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">Login to Book</a>
                    <p class="signup-prompt">Don't have an account? <a href="register.php">Sign up here</a></p>
                </div>
                <?php else: ?>
                <div class="booking-form-container">
                    <form id="booking-form" method="post" action="process-booking.php">
                        <input type="hidden" name="consultant_id" value="<?php echo $consultant_id; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <input type="hidden" name="organization_id" value="<?php echo $consultant['organization_id']; ?>">
                        
                        <div class="booking-step active" id="step1">
                            <h4>Step 1: Select a Service</h4>
                            <?php if (empty($services)): ?>
                                <div class="alert alert-warning">
                                    This consultant doesn't have any active services available for booking.
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label for="visa_service_id">Select a Service:</label>
                                    <select id="visa_service_id" name="visa_service_id" class="form-control" required>
                                        <option value="">-- Select a Service --</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['visa_service_id']; ?>" data-price="<?php echo $service['base_price']; ?>">
                                                <?php echo htmlspecialchars($service['country_name'] . ' - ' . $service['visa_type'] . ' - ' . $service['service_name']); ?>
                                                ($<?php echo number_format($service['base_price'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="consultation_mode_id">Consultation Mode:</label>
                                    <select id="consultation_mode_id" name="consultation_mode_id" class="form-control" required>
                                        <option value="">-- Select a Consultation Mode --</option>
                                        <?php foreach ($modes as $mode): ?>
                                            <option value="<?php echo $mode['consultation_mode_id']; ?>" data-fee="<?php echo $mode['additional_fee']; ?>">
                                                <?php echo htmlspecialchars($mode['mode_name']); ?>
                                                <?php if ($mode['additional_fee'] > 0): ?>
                                                    (+ $<?php echo number_format($mode['additional_fee'], 2); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary next-step" data-next="step2">Continue to Date & Time</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="booking-step" id="step2">
                            <h4>Step 2: Select Date & Time</h4>
                            <div class="calendar-container">
                                <div id="booking-calendar"></div>
                            </div>
                            
                            <div class="time-slots-container" style="display: none;">
                                <h5>Available Time Slots for <span id="selected-date"></span></h5>
                                <div id="time-slots"></div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary prev-step" data-prev="step1">Back</button>
                                <button type="button" class="btn btn-primary next-step" data-next="step3" disabled>Continue to Details</button>
                            </div>
                        </div>
                        
                        <div class="booking-step" id="step3">
                            <h4>Step 3: Additional Details</h4>
                            
                            <div class="form-group">
                                <label for="client_notes">Notes or Special Requests:</label>
                                <textarea id="client_notes" name="client_notes" class="form-control" rows="4"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="language_preference">Preferred Language:</label>
                                <select id="language_preference" name="language_preference" class="form-control">
                                    <option value="English">English</option>
                                    <option value="Spanish">Spanish</option>
                                    <option value="French">French</option>
                                    <option value="German">German</option>
                                    <option value="Chinese">Chinese</option>
                                    <option value="Arabic">Arabic</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary prev-step" data-prev="step2">Back</button>
                                <button type="button" class="btn btn-primary next-step" data-next="step4">Review Booking</button>
                            </div>
                        </div>
                        
                        <div class="booking-step" id="step4">
                            <h4>Step 4: Review & Confirm</h4>
                            
                            <div class="booking-summary">
                                <h5>Booking Summary</h5>
                                <div class="summary-item">
                                    <span class="label">Consultant:</span>
                                    <span class="value"><?php echo htmlspecialchars($consultant['consultant_name']); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Service:</span>
                                    <span class="value" id="summary-service">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Consultation Mode:</span>
                                    <span class="value" id="summary-mode">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Date & Time:</span>
                                    <span class="value" id="summary-datetime">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Language:</span>
                                    <span class="value" id="summary-language">English</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Notes:</span>
                                    <span class="value" id="summary-notes">-</span>
                                </div>
                                <div class="summary-item total">
                                    <span class="label">Total Price:</span>
                                    <span class="value" id="summary-price">$0.00</span>
                                </div>
                            </div>
                            
                            <div class="terms-container">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary prev-step" data-prev="step3">Back</button>
                                <button type="submit" class="btn btn-success" id="confirm-booking" disabled>Confirm Booking</button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll to booking section
    const bookingLinks = document.querySelectorAll('a[href="#booking-section"]');
    bookingLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector('#booking-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Navigation between steps (only runs if user is logged in)
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.booking-step');
            const nextStepId = this.dataset.next;
            
            currentStep.classList.remove('active');
            document.getElementById(nextStepId).classList.add('active');
            
            // Scroll to top of form
            document.querySelector('#booking-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.booking-step');
            const prevStepId = this.dataset.prev;
            
            currentStep.classList.remove('active');
            document.getElementById(prevStepId).classList.add('active');
            
            // Scroll to top of form
            document.querySelector('#booking-section').scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Service selection
    const serviceSelect = document.getElementById('visa_service_id');
    const modeSelect = document.getElementById('consultation_mode_id');
    
    if (serviceSelect && modeSelect) {
        serviceSelect.addEventListener('change', updateSummary);
        modeSelect.addEventListener('change', updateSummary);
    }
    
    // Language preference
    const languageSelect = document.getElementById('language_preference');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            document.getElementById('summary-language').textContent = this.value;
        });
    }
    
    // Client notes
    const notesInput = document.getElementById('client_notes');
    if (notesInput) {
        notesInput.addEventListener('input', function() {
            document.getElementById('summary-notes').textContent = this.value || '-';
        });
    }
    
    // Terms checkbox
    const termsCheckbox = document.getElementById('terms');
    const confirmButton = document.getElementById('confirm-booking');
    
    if (termsCheckbox && confirmButton) {
        termsCheckbox.addEventListener('change', function() {
            confirmButton.disabled = !this.checked;
        });
    }
    
    // Function to update booking summary
    function updateSummary() {
        const serviceSelect = document.getElementById('visa_service_id');
        const modeSelect = document.getElementById('consultation_mode_id');
        
        let totalPrice = 0;
        
        if (serviceSelect.value) {
            const selectedService = serviceSelect.options[serviceSelect.selectedIndex].text;
            document.getElementById('summary-service').textContent = selectedService;
            
            const servicePrice = parseFloat(serviceSelect.options[serviceSelect.selectedIndex].dataset.price);
            totalPrice += servicePrice;
        }
        
        if (modeSelect.value) {
            const selectedMode = modeSelect.options[modeSelect.selectedIndex].text;
            document.getElementById('summary-mode').textContent = selectedMode;
            
            const modeFee = parseFloat(modeSelect.options[modeSelect.selectedIndex].dataset.fee) || 0;
            totalPrice += modeFee;
        }
        
        document.getElementById('summary-price').textContent = '$' + totalPrice.toFixed(2);
    }
    
    // Initialize date picker (placeholder - would be replaced with actual implementation)
    // In a real implementation, you would fetch available slots from the server
    const mockTimeslots = [
        { time: '09:00', available: true },
        { time: '10:00', available: true },
        { time: '11:00', available: false },
        { time: '12:00', available: true },
        { time: '13:00', available: false },
        { time: '14:00', available: true },
        { time: '15:00', available: true },
        { time: '16:00', available: true }
    ];
    
    // For demonstration purposes only - in a real implementation, 
    // you would integrate a proper calendar library like FullCalendar
    const calendarContainer = document.getElementById('booking-calendar');
    if (calendarContainer) {
        calendarContainer.innerHTML = '<div class="demo-calendar"><p>This is a placeholder for the calendar. In a real implementation, this would be an interactive calendar where you can select available dates.</p><button id="demo-date-select" class="btn btn-outline">Select Tomorrow</button></div>';
        
        document.getElementById('demo-date-select').addEventListener('click', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = tomorrow.toLocaleDateString('en-US', options);
            
            document.getElementById('selected-date').textContent = formattedDate;
            document.querySelector('.time-slots-container').style.display = 'block';
            
            // Populate time slots
            const timeSlotsContainer = document.getElementById('time-slots');
            timeSlotsContainer.innerHTML = '';
            
            mockTimeslots.forEach(slot => {
                const slotElement = document.createElement('div');
                slotElement.className = 'time-slot ' + (slot.available ? 'available' : 'unavailable');
                slotElement.textContent = slot.time;
                
                if (slot.available) {
                    slotElement.addEventListener('click', function() {
                        // Remove selected class from all slots
                        document.querySelectorAll('.time-slot').forEach(el => {
                            el.classList.remove('selected');
                        });
                        
                        // Add selected class to clicked slot
                        this.classList.add('selected');
                        
                        // Update summary
                        document.getElementById('summary-datetime').textContent = formattedDate + ' at ' + slot.time;
                        
                        // Enable next button
                        document.querySelector('#step2 .next-step').disabled = false;
                    });
                }
                
                timeSlotsContainer.appendChild(slotElement);
            });
        });
    }
});
</script>

<style>
/* Basic styles for the profile and booking page */
.booking-header {
    text-align: center;
    margin-bottom: 30px;
}

.booking-header h1 {
    color: #042167;
    margin-bottom: 10px;
}

.booking-header h2 {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

.verified-badge {
    background-color: #28a745;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.consultant-banner {
    width: 100%;
    height: 200px;
    overflow: hidden;
    margin-bottom: 30px;
    border-radius: 8px;
}

.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-container {
    display: flex;
    gap: 30px;
    margin-bottom: 50px;
}

.profile-sidebar {
    flex: 1;
    max-width: 320px;
}

.profile-image-container {
    width: 100%;
    margin-bottom: 20px;
    text-align: center;
}

.profile-image {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-info {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-info h3 {
    color: #042167;
    margin-top: 0;
    margin-bottom: 10px;
}

.company-name, .organization-name {
    margin: 5px 0;
    color: #666;
}

.verification-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    color: #28a745;
    margin: 15px 0;
    padding: 10px;
    background-color: rgba(40, 167, 69, 0.1);
    border-radius: 4px;
}

.verification-info i {
    margin-right: 5px;
}

.verification-info p {
    margin: 5px 0 0;
    font-size: 0.8rem;
    color: #666;
}

.consultant-rating {
    margin: 15px 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
}

.stars {
    color: #ffc107;
}

.experience {
    margin: 15px 0;
    color: #666;
}

.contact-links {
    display: flex;
    gap: 10px;
    margin: 15px 0;
}

.social-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    color: #042167;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.2s;
}

.social-link:hover {
    background-color: #042167;
    color: white;
}

.booking-cta {
    margin-top: 20px;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

.profile-content {
    flex: 3;
}

.profile-section {
    background-color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.profile-section h3 {
    color: #042167;
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.bio, .education, .certifications {
    line-height: 1.6;
    color: #555;
}

.specializations {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.specialization-tag {
    background-color: #e9ecef;
    color: #495057;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.9rem;
}

.services-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.service-info h4 {
    margin: 0 0 5px;
    color: #042167;
}

.service-name {
    margin: 0 0 5px;
    font-weight: 500;
}

.service-description {
    margin: 5px 0 0;
    font-size: 0.9rem;
    color: #666;
}

.service-price {
    font-weight: bold;
    color: #042167;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-item {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.reviewer-name {
    font-weight: 500;
}

.review-date {
    color: #666;
    font-size: 0.9rem;
}

.review-rating {
    margin-bottom: 10px;
    color: #ffc107;
}

.review-text {
    line-height: 1.5;
    color: #555;
}

.login-required-message {
    text-align: center;
    padding: 30px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.login-required-message p {
    margin-bottom: 20px;
}

.signup-prompt {
    margin-top: 20px;
    font-size: 0.9rem;
}

.booking-form-container {
    margin-top: 15px;
}

.booking-step {
    display: none;
}

.booking-step.active {
    display: block;
}

.booking-step h4 {
    color: #042167;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-control:focus {
    border-color: #042167;
    outline: none;
    box-shadow: 0 0 0 2px rgba(4,33,103,0.1);
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    border: none;
}

.btn-primary {
    background-color: #042167;
    color: white;
}

.btn-primary:hover {
    background-color: #031854;
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid #042167;
    color: #042167;
}

.btn-outline:hover {
    background-color: #f0f4ff;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.calendar-container {
    margin-bottom: 20px;
}

.demo-calendar {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.time-slots-container {
    margin-top: 20px;
}

#time-slots {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
}

.time-slot {
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    min-width: 80px;
}

.time-slot.available {
    background-color: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.time-slot.unavailable {
    background-color: #f5f5f5;
    color: #9e9e9e;
    border: 1px solid #e0e0e0;
    cursor: not-allowed;
    opacity: 0.7;
}

.time-slot.selected {
    background-color: #2e7d32;
    color: white;
    border: 1px solid #2e7d32;
}

.booking-summary {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.booking-summary h5 {
    color: #042167;
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    font-weight: bold;
    color: #042167;
    font-size: 1.1em;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

.terms-container {
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .profile-container {
        flex-direction: column;
    }
    
    .profile-sidebar {
        max-width: 100%;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 