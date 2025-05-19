<?php
// Include session management
require_once "includes/session.php";

// Get consultant ID from URL
$consultant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Database connection
require_once "config/db_connect.php";

// Fetch consultant profile data
$query = "SELECT 
    u.id,
    u.first_name,
    u.last_name,
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
    cp.profile_image,
    cp.banner_image,
    cp.website,
    cp.social_linkedin,
    cp.social_twitter,
    cp.social_facebook,
    cp.is_verified,
    o.name as organization_name,
    o.id as organization_id,
    COUNT(DISTINCT b.id) as total_bookings,
    ROUND(AVG(bf.rating), 1) as average_rating,
    COUNT(DISTINCT bf.id) as total_reviews
FROM users u
JOIN consultants c ON u.id = c.user_id
LEFT JOIN consultant_profiles cp ON u.id = cp.consultant_id
LEFT JOIN organizations o ON u.organization_id = o.id
LEFT JOIN bookings b ON u.id = b.consultant_id AND b.deleted_at IS NULL
LEFT JOIN booking_feedback bf ON b.id = bf.booking_id
WHERE u.id = ? AND u.user_type = 'consultant' AND u.status = 'active'
GROUP BY u.id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /404.php");
    exit();
}

$consultant = $result->fetch_assoc();

// Set page title
$page_title = $consultant['first_name'] . " " . $consultant['last_name'] . " | Immigration Consultant";
include('includes/header.php');
?>

<!-- Profile Header Section -->
<section class="profile-header">
    <div class="container">
        <div class="profile-banner" style="background-image: url('<?php echo !empty($consultant['banner_image']) ? '/uploads/' . $consultant['banner_image'] : '/assets/images/default-banner.jpg'; ?>')">
            <div class="profile-info">
                <div class="profile-image">
                    <img src="<?php echo !empty($consultant['profile_image']) ? '/uploads/' . $consultant['profile_image'] : '/assets/images/default-profile.svg'; ?>" 
                         alt="<?php echo htmlspecialchars($consultant['first_name'] . ' ' . $consultant['last_name']); ?>">
                    <?php if ($consultant['is_verified']): ?>
                    <div class="verified-badge" title="Verified Consultant">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h1><?php echo htmlspecialchars($consultant['first_name'] ?? '' . ' ' . $consultant['last_name'] ?? ''); ?></h1>
                    <p class="company-name"><?php echo htmlspecialchars($consultant['company_name'] ?? ''); ?></p>
                   
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-value"><?php echo number_format($consultant['average_rating'] ?? 0, 1); ?></span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo $consultant['total_reviews'] ?? 0; ?></span>
                            <span class="stat-label">Reviews</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo $consultant['total_bookings'] ?? 0; ?></span>
                            <span class="stat-label">Consultations</span>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="contact-info">
                        <?php if (!empty($consultant['email'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?php echo htmlspecialchars($consultant['email']); ?>">
                                <?php echo htmlspecialchars($consultant['email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($consultant['phone'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo htmlspecialchars($consultant['phone']); ?>">
                                <?php echo htmlspecialchars($consultant['phone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($consultant['website'])): ?>
                        <div class="contact-item">
                            <i class="fas fa-globe"></i>
                            <a href="<?php echo htmlspecialchars($consultant['website']); ?>" target="_blank">
                                Visit Website
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Social Links -->
                    <?php if (!empty($consultant['social_linkedin']) || !empty($consultant['social_twitter']) || !empty($consultant['social_facebook'])): ?>
                    <div class="social-links">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="profile-content">
    <div class="container">
        <div class="content-grid">
            <!-- Left Column -->
            <div class="main-content">
                <!-- About Section -->
                <div class="content-section">
                    <h2>About</h2>
                    <div class="bio">
                        <?php echo nl2br(htmlspecialchars($consultant['bio'])); ?>
                    </div>
                </div>

                <!-- Experience Section -->
                <div class="content-section">
                    <h2>Experience</h2>
                    <div class="experience-details">
                        <div class="detail-item">
                            <i class="fas fa-briefcase"></i>
                            <div>
                                <h3>Years of Experience</h3>
                                <p><?php echo $consultant['years_experience']; ?> years</p>
                            </div>
                        </div>
                        <?php if (!empty($consultant['education'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-graduation-cap"></i>
                            <div>
                                <h3>Education</h3>
                                <p><?php echo nl2br(htmlspecialchars($consultant['education'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($consultant['certifications'])): ?>
                        <div class="detail-item">
                            <i class="fas fa-certificate"></i>
                            <div>
                                <h3>Certifications</h3>
                                <p><?php echo nl2br(htmlspecialchars($consultant['certifications'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Specializations Section -->
                <?php if (!empty($consultant['specializations'])): ?>
                <div class="content-section">
                    <h2>Specializations</h2>
                    <div class="specializations">
                        <?php
                        $specializations = explode(',', $consultant['specializations']);
                        foreach ($specializations as $spec):
                        ?>
                        <span class="specialization-tag"><?php echo htmlspecialchars(trim($spec)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Languages Section -->
                <?php if (!empty($consultant['languages'])): ?>
                <div class="content-section">
                    <h2>Languages</h2>
                    <div class="languages">
                        <?php
                        $languages = explode(',', $consultant['languages']);
                        foreach ($languages as $lang):
                        ?>
                        <span class="language-tag"><?php echo htmlspecialchars(trim($lang)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Booking Form -->
            <div class="sidebar">
                <div class="booking-form">
                    <h3>Book a Consultation</h3>
                    <form id="bookingForm" action="process-booking.php" method="POST">
                        <input type="hidden" name="consultant_id" value="<?php echo $consultant_id; ?>">
                        <?php if (!empty($consultant['organization_id'])): ?>
                        <input type="hidden" name="organization_id" value="<?php echo $consultant['organization_id']; ?>">
                        <?php endif; ?>

                        <!-- Service Selection -->
                        <div class="form-group">
                            <label for="service">Select Service</label>
                            <select name="visa_service_id" id="service" required>
                                <option value="">Choose a service...</option>
                                <?php 
                                // Fetch available services for this consultant
                                $services_query = "SELECT 
                                    vs.visa_service_id,
                                    v.visa_type,
                                    st.service_name,
                                    vs.base_price,
                                    vs.description,
                                    GROUP_CONCAT(DISTINCT cm.mode_name) as consultation_modes
                                FROM visa_services vs
                                JOIN visas v ON vs.visa_id = v.visa_id
                                JOIN service_types st ON vs.service_type_id = st.service_type_id
                                LEFT JOIN service_consultation_modes scm ON vs.visa_service_id = scm.visa_service_id
                                LEFT JOIN consultation_modes cm ON scm.consultation_mode_id = cm.consultation_mode_id
                                WHERE vs.consultant_id = ? AND vs.is_active = 1
                                GROUP BY vs.visa_service_id";
                                
                                $stmt = $conn->prepare($services_query);
                                $stmt->bind_param("i", $consultant_id);
                                $stmt->execute();
                                $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                
                                foreach ($services as $service): 
                                ?>
                                <option value="<?php echo $service['visa_service_id']; ?>" 
                                        data-price="<?php echo $service['base_price']; ?>"
                                        data-modes="<?php echo htmlspecialchars($service['consultation_modes']); ?>">
                                    <?php echo htmlspecialchars($service['visa_type'] . ' - ' . $service['service_name']); ?>
                                    (<?php echo number_format($service['base_price'], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Consultation Mode -->
                        <div class="form-group">
                            <label for="consultation_mode">Consultation Mode</label>
                            <select name="consultation_mode" id="consultation_mode" required>
                                <option value="">Select mode...</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="form-group">
                            <label for="booking_date">Select Date</label>
                            <input type="date" id="booking_date" name="booking_date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Time Selection -->
                        <div class="form-group">
                            <label for="booking_time">Select Time</label>
                            <select name="booking_time" id="booking_time" required>
                                <option value="">Select time...</option>
                            </select>
                        </div>

                        <!-- Duration -->
                        <div class="form-group">
                            <label for="duration">Duration</label>
                            <select name="duration_minutes" id="duration" required>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea name="client_notes" id="notes" rows="4" 
                                      placeholder="Any specific questions or topics you'd like to discuss?"></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Book Consultation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Profile Header Styles */
.profile-header {
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
}

.profile-banner {
    height: 300px;
    background-size: cover;
    background-position: center;
    position: relative;
    border-radius: 10px;
    overflow: hidden;
}

.profile-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem;
    background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0.7));
    color: white;
    display: flex;
    align-items: flex-end;
    gap: 2rem;
}

.profile-image {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid white;
    flex-shrink: 0;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.verified-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #4CAF50;
    color: white;
    border-radius: 50%;
    padding: 5px;
}

.profile-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.profile-details h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.company-name {
    font-size: 1.4rem;
    margin: 0;
    opacity: 0.9;
}

.organization {
    font-size: 1rem;
    margin: 0;
    opacity: 0.8;
    color: #ccc;
}

.profile-stats {
    display: flex;
    gap: 2rem;
    margin-top: 0.5rem;
    font-size: 0.9rem;
}

.stat {
    text-align: left;
}

.stat-value {
    display: block;
    font-size: 1.2rem;
    font-weight: 600;
}

.stat-label {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* Contact Info Styles */
.contact-info {
    margin-top: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    justify-content: flex-start;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.contact-item i {
    width: 20px;
    color: white;
}

.contact-item a {
    color: white;
    text-decoration: none;
    opacity: 0.9;
    font-size: 0.9rem;
}

.contact-item a:hover {
    opacity: 1;
}

/* Social Links Styles */
.social-links {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.5rem;
    z-index: 2;
}

.social-link {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.social-link:hover {
    background: white;
    color: var(--primary-color);
}

/* Content Grid Styles */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.content-section {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.content-section h2 {
    margin: 0 0 1.5rem;
    color: #333;
    font-size: 1.5rem;
}

.bio {
    line-height: 1.6;
    color: #666;
}

.experience-details {
    display: grid;
    gap: 1.5rem;
}

.detail-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.detail-item i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.detail-item h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: #333;
}

.detail-item p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.specializations, .languages {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.specialization-tag, .language-tag {
    background: var(--primary-light);
    color: var(--primary-color);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

/* Booking Form Styles */
.booking-form {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.booking-form h3 {
    margin: 0 0 1.5rem;
    color: #333;
    font-size: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.form-group textarea {
    resize: vertical;
}

.btn-primary {
    width: 100%;
    padding: 1rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .profile-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 1.5rem;
    }

    .profile-image {
        width: 120px;
        height: 120px;
    }

    .profile-details h1 {
        font-size: 2rem;
        text-align: center;
    }

    .company-name {
        font-size: 1.2rem;
        text-align: center;
    }

    .profile-stats {
        justify-content: center;
    }

    .contact-info {
        justify-content: center;
    }

    .social-links {
        position: relative;
        top: 0;
        right: 0;
        justify-content: center;
        margin-top: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service');
    const modeSelect = document.getElementById('consultation_mode');
    const dateInput = document.getElementById('booking_date');
    const timeSelect = document.getElementById('booking_time');

    // Update consultation modes when service is selected
    serviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const modes = selectedOption.dataset.modes.split(',');
        
        modeSelect.innerHTML = '<option value="">Select mode...</option>';
        modes.forEach(mode => {
            const option = document.createElement('option');
            option.value = mode.trim();
            option.textContent = mode.trim();
            modeSelect.appendChild(option);
        });
    });

    // Fetch available time slots when date is selected
    dateInput.addEventListener('change', function() {
        const date = this.value;
        const serviceId = serviceSelect.value;
        const consultantId = <?php echo $consultant_id; ?>;

        if (date && serviceId) {
            // Fetch available time slots from the server
            fetch(`get-available-slots.php?date=${date}&service_id=${serviceId}&consultant_id=${consultantId}`)
                .then(response => response.json())
                .then(slots => {
                    timeSelect.innerHTML = '<option value="">Select time...</option>';
                    slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = slot.time;
                        timeSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching time slots:', error));
        }
    });

    // Form submission handling
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        // Submit form
        const formData = new FormData(this);
        fetch('process-booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `booking-confirmation.php?reference=${data.reference}`;
            } else {
                alert(data.message || 'An error occurred while processing your booking.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your booking.');
        });
    });
});
</script>

<?php include('includes/footer.php'); ?>
