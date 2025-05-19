<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Include session management
require_once "includes/session.php";

// Include config files
require_once "config/db_connect.php";

$page_title = "Become a Member";
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get membership plans
$query = "SELECT * FROM membership_plans ORDER BY price ASC";
$result = $conn->query($query);
$plans = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>

<!-- Hero Section -->
<section class="hero consultant-hero">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Transform Your Immigration Consulting Practice</h1>
            <p class="hero-subtitle">Join Visafy to access 30+ powerful features designed for immigration consultants</p>
            <div class="feature-search">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search features (Press ⌘K)" id="featureSearch">
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content">
    <!-- Consultant Benefits Section -->
    <div class="registration-container" id="membership-plans">
        <div class="membership-plans">
            <h2>Choose Your Membership Plan</h2>
            <p>Select the plan that best fits your business needs</p>
            
            <!-- Membership Plans -->
            <div class="plans-grid">
                <?php
                if (count($plans) > 0) {
                    foreach ($plans as $plan): 
                ?>
                    <div class="plan-card">
                        <div class="plan-header">
                            <h3 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="plan-price">$<?php echo number_format($plan['price'], 2); ?></div>
                            <div class="plan-billing">per month</div>
                        </div>
                        <div class="plan-features">
                            <div class="feature">
                                <i class="fas fa-users"></i>
                                <div>Up to <?php echo (int)$plan['max_team_members']; ?> team members</div>
                            </div>
                            <div class="feature">
                                <i class="fas fa-check-circle"></i>
                                <div>Client management tools</div>
                            </div>
                            <div class="feature">
                                <i class="fas fa-check-circle"></i>
                                <div>Document management</div>
                            </div>
                            <div class="feature">
                                <i class="fas fa-check-circle"></i>
                                <div>Visa tracking system</div>
                            </div>
                        </div>
                        <div class="plan-action">
                            <a href="consultant-registration.php?plan_id=<?php echo $plan['id']; ?>" class="btn select-plan-btn">
                                Select Plan
                            </a>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                } else {
                    echo '<div class="no-plans-message">No plans are currently available. Please check back later.</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <section class="section platform-benefits">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Why Join Visafy as a Consultant</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Our platform is designed to help you deliver exceptional immigration services</p>
            
            <div class="benefits-container">
                <!-- Client Management Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Comprehensive Client Management</h3>
                            <p class="benefit-description">
                                Manage your entire client base efficiently with our powerful tools.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Client Profiles & History</strong>
                                        <p>Maintain detailed client profiles with complete application history and relationship tracking</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Document Management</strong>
                                        <p>Secure document storage, version control, and easy sharing with clients</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Communication Hub</strong>
                                        <p>Integrated messaging, email templates, and automated notifications</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-1">
                                    <path d="M42.7,-73.4C55.9,-67.1,67.7,-57.2,75.9,-44.6C84.1,-32,88.7,-16,88.1,-0.3C87.5,15.3,81.8,30.6,73.1,43.9C64.4,57.2,52.8,68.5,39.1,75.3C25.4,82.1,9.7,84.4,-5.9,83.1C-21.5,81.8,-37,76.9,-50.9,68.5C-64.8,60.1,-77.1,48.3,-83.3,33.8C-89.5,19.3,-89.6,2.2,-85.1,-13.2C-80.6,-28.6,-71.5,-42.3,-59.8,-51.6C-48.1,-60.9,-33.8,-65.8,-20.4,-70.3C-7,-74.8,5.5,-78.9,18.8,-79.1C32.1,-79.3,46.2,-75.6,42.7,-73.4Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/client-management.png" alt="Client Management">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visa Processing Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content reverse">
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-3">
                                    <path d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/visa-processing.png" alt="Visa Processing">
                            </div>
                        </div>
                        <div class="benefit-info">
                            <h3>Advanced Visa Processing</h3>
                            <p class="benefit-description">
                                Streamline your visa application process with our comprehensive tools.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Application Tracking</strong>
                                        <p>Monitor application status, processing times, and deadlines</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Document Verification</strong>
                                        <p>Automated document checks and verification workflows</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Status Updates</strong>
                                        <p>Real-time status tracking and automated notifications</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Service Management Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Service & Team Management</h3>
                            <p class="benefit-description">
                                Optimize your service delivery and team collaboration.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Service Customization</strong>
                                        <p>Create and manage customized service packages with flexible pricing</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Team Collaboration</strong>
                                        <p>Assign tasks, track progress, and manage team performance</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Booking Management</strong>
                                        <p>Automated scheduling, availability management, and appointment tracking</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-5">
                                    <path d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/service-management.png" alt="Service Management">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Growth Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content reverse">
                        <div class="benefit-image">
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-7">
                                    <path d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/business-growth.png" alt="Business Growth">
                            </div>
                        </div>
                        <div class="benefit-info">
                            <h3>Business Growth & Analytics</h3>
                            <p class="benefit-description">
                                Scale your practice with data-driven insights and growth tools.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Performance Analytics</strong>
                                        <p>Track success rates, processing times, and revenue metrics</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Client Engagement</strong>
                                        <p>Automated follow-ups and service reminders</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Business Intelligence</strong>
                                        <p>Data-driven insights for business growth and optimization</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="feature-categories">
        <div class="container">
            <div class="categories-grid">
                <!-- Client Management -->
                <div class="category-card" data-category="client">
                    <div class="category-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Client Management</h3>
                    <ul class="feature-list">
                        <li>Client profiles & history</li>
                        <li>Relationship tracking</li>
                        <li>Communication tools</li>
                        <li>Document management</li>
                    </ul>
                </div>

                <!-- Visa Processing -->
                <div class="category-card" data-category="visa">
                    <div class="category-icon">
                        <i class="fas fa-passport"></i>
                    </div>
                    <h3>Visa Processing</h3>
                    <ul class="feature-list">
                        <li>Application tracking</li>
                        <li>Document verification</li>
                        <li>Status updates</li>
                        <li>Processing timelines</li>
                    </ul>
                </div>

                <!-- Service Management -->
                <div class="category-card" data-category="service">
                    <div class="category-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Service Management</h3>
                    <ul class="feature-list">
                        <li>Service customization</li>
                        <li>Availability scheduling</li>
                        <li>Booking management</li>
                        <li>Team collaboration</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
:root {
    --primary-color: #eaaa34;
    --primary-light: rgba(234, 170, 52, 0.1);
    --primary-medium: rgba(234, 170, 52, 0.2);
    --dark-blue: #042167;
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
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --danger-color: #e74a3b;
    --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.1);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Existing styles... */

.content {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    text-align: center;
}

.header-container h1 {
    margin: 0;
    color: var(--dark-blue);
    font-size: 2rem;
    font-weight: 700;
}

.header-container p {
    margin: 10px 0 0;
    color: var(--text-light);
    font-size: 1.1rem;
}

.alert {
    padding: 12px 15px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
}

.alert-danger {
    background-color: rgba(231, 74, 59, 0.1);
    color: var(--danger-color);
    border: 1px solid rgba(231, 74, 59, 0.2);
}

.alert-success {
    background-color: rgba(28, 200, 138, 0.1);
    color: var(--success-color);
    border: 1px solid rgba(28, 200, 138, 0.2);
}

.registration-container {
    margin-top: 20px;
}

.membership-plans {
    margin-bottom: 40px;
}

.membership-plans h2 {
    color: var(--dark-blue);
    font-size: 1.6rem;
    margin-bottom: 5px;
    text-align: center;
    font-weight: 700;
}

.membership-plans p {
    color: var(--text-light);
    text-align: center;
    margin-bottom: 30px;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.plan-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--border-color);
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.plan-header {
    padding: 20px;
    text-align: center;
    background-color: var(--primary-light);
    border-bottom: 1px solid var(--border-color);
}

.plan-name {
    color: var(--dark-blue);
    font-size: 1.4rem;
    margin: 0 0 10px;
    font-weight: 700;
}

.plan-price {
    font-size: 2rem;
    color: var(--primary-color);
    font-weight: 700;
}

.plan-billing {
    color: var(--text-light);
    font-size: 0.9rem;
}

.plan-features {
    padding: 20px;
}

.feature {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.feature i {
    color: var(--primary-color);
    margin-right: 10px;
    margin-top: 4px;
    flex-shrink: 0;
}

.plan-action {
    padding: 0 20px 20px;
    text-align: center;
}

.select-plan-btn {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
    padding: 12px 20px;
    border-radius: var(--border-radius);
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
    width: 100%;
    font-size: 1rem;
}

.select-plan-btn:hover {
    background-color: var(--dark-blue);
    transform: translateY(-2px);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    overflow: auto;
}

.modal-dialog {
    margin: 60px auto;
    max-width: 700px;
    width: 90%;
}

.modal-content {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    background-color: var(--primary-light);
}

.modal-title {
    margin: 0;
    color: var(--dark-blue);
    font-size: 1.4rem;
    font-weight: 700;
}

.close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-light);
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.selected-plan-info {
    background-color: var(--background-light);
    border-radius: var(--border-radius);
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid var(--primary-light);
}

.selected-plan-info h4 {
    color: var(--dark-blue);
    margin: 0 0 10px 0;
    font-weight: 600;
}

.plan-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.detail {
    flex: 1;
    min-width: 150px;
}

.detail .label {
    font-weight: 600;
    color: var(--text-color);
    display: block;
    margin-bottom: 5px;
}

.detail .value {
    color: var(--text-light);
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    flex: 1;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-color);
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: var(--text-light);
}

.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.cancel-btn {
    background-color: var(--background-light);
    color: var(--text-light);
    border: 1px solid var(--border-color);
    padding: 12px 20px;
    border-radius: var(--border-radius);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.cancel-btn:hover {
    background-color: #e9ecef;
}

.submit-btn {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
    padding: 12px 20px;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.submit-btn:hover {
    background-color: var(--dark-blue);
}

.terms-privacy {
    margin-bottom: 25px;
}

.checkbox-group {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    margin-top: 3px;
}

.checkbox-group label {
    font-size: 14px;
    color: var(--text-color);
}

.checkbox-group a {
    color: var(--primary-color);
    text-decoration: none;
}

.checkbox-group a:hover {
    text-decoration: underline;
}

.registration-success {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 40px;
    text-align: center;
    max-width: 600px;
    margin: 40px auto;
}

.success-icon {
    font-size: 5rem;
    color: var(--success-color);
    margin-bottom: 20px;
}

.registration-success h2 {
    color: var(--dark-blue);
    font-size: 1.8rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.registration-success p {
    color: var(--text-light);
    margin-bottom: 10px;
}

.success-actions {
    margin-top: 30px;
}

.no-plans-message {
    text-align: center;
    padding: 20px;
    background-color: var(--background-light);
    border-radius: var(--border-radius);
    color: var(--text-light);
    font-style: italic;
    width: 100%;
    grid-column: 1 / -1;
}

.primary-btn {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: var(--transition);
}

.primary-btn:hover {
    background-color: var(--dark-blue);
}

/* Hero Section Styles */
.hero {
    padding: 60px 0;
    background-color: var(--background-light);
    overflow: hidden;
    position: relative;
    min-height: 300px;
    display: flex;
    align-items: center;
}

.hero.consultant-hero {
    background-color: rgba(234, 170, 52, 0.05);
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-blue);
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.2rem;
    color: var(--text-light);
    margin-bottom: 30px;
    line-height: 1.5;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn {
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
    border: none;
}

.btn-primary:hover {
    background-color: var(--dark-blue);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: var(--white);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background-color: var(--background-light);
    transform: translateY(-2px);
}

.hero-image-container {
    position: relative;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.floating-image-hero {
    animation: float 3s ease-in-out infinite;
    max-width: 100%;
}

.floating-image-hero img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: var(--shadow-lg);
}

@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-15px);
    }
    100% {
        transform: translateY(0px);
    }
}

/* Platform Benefits Styles */
.section {
    padding: 60px 0;
}

.section-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-blue);
    text-align: center;
    margin-bottom: 15px;
}

.section-subtitle {
    font-size: 1.1rem;
    color: var(--text-light);
    text-align: center;
    margin-bottom: 50px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.platform-benefits {
    background-color: var(--white);
}

.benefits-container {
    max-width: 1100px;
    margin: 0 auto;
}

.benefit-section {
    margin-bottom: 70px;
}

.benefit-content {
    display: flex;
    align-items: center;
    gap: 40px;
}

.benefit-content.reverse {
    flex-direction: row-reverse;
}

.benefit-info {
    flex: 1;
}

.benefit-info h3 {
    color: var(--dark-blue);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.benefit-description {
    color: var(--text-light);
    margin-bottom: 20px;
}

.benefit-features {
    list-style: none;
    padding: 0;
}

.benefit-features li {
    display: flex;
    margin-bottom: 20px;
}

.check-icon {
    color: var(--primary-color);
    background-color: var(--primary-light);
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
    margin-top: 4px;
}

.benefit-features li strong {
    display: block;
    margin-bottom: 5px;
    color: var(--text-color);
}

.benefit-features li p {
    color: var(--text-light);
    margin: 0;
    font-size: 0.95rem;
}

.benefit-image {
    flex: 1;
    position: relative;
}

.svg-background {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -1;
}

.shape {
    position: absolute;
    width: 100%;
    height: 100%;
    fill: var(--primary-light);
    opacity: 0.3;
}

.shape-1, .shape-3, .shape-5 {
    top: -10%;
    left: -10%;
    animation: morph 8s ease-in-out infinite;
}

.shape-2, .shape-4, .shape-6 {
    top: -5%;
    left: -5%;
    animation: morph 8s ease-in-out infinite reverse;
}

@keyframes morph {
    0% {
        transform: scale(1) translate(0, 0);
    }
    50% {
        transform: scale(1.05) translate(1%, 1%);
    }
    100% {
        transform: scale(1) translate(0, 0);
    }
}

.image-container {
    padding: 10px;
}

.image-container img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: var(--shadow-md);
}

/* Responsive styles */
@media (max-width: 991px) {
    .hero-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-image-container {
        order: -1;
    }
    
    .benefit-content {
        flex-direction: column;
    }
    
    .benefit-content.reverse {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-buttons {
        flex-direction: column;
    }
    
    .section-title {
        font-size: 1.7rem;
    }

    .categories-grid {
        grid-template-columns: 1fr;
    }

    .search-box {
        margin: 1rem;
    }

    .category-card {
        margin: 1rem;
    }
}
/* Add Stripe Element Styles */
.StripeElement {
    background-color: white;
    padding: 12px 15px;
    border-radius: var(--border-radius);
}

.StripeElement--focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

.StripeElement--invalid {
    border-color: var(--danger-color);
}

.StripeElement--webkit-autofill {
    background-color: #fefde5 !important;
}

.payment-error-message {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 8px;
}

.payment-form {
    margin-bottom: 25px;
}

/* Disable button styles */
.submit-btn.disabled {
    background-color: #cccccc;
    cursor: not-allowed;
    opacity: 0.7;
}

.processing-payment {
    display: inline-block;
    margin-left: 10px;
}

/* Additional styles remain the same... */

/* Feature Search */
.feature-search {
    max-width: 600px;
    margin: 2rem auto;
}

.search-box {
    background: var(--white);
    border-radius: 50px;
    padding: 1rem 1.5rem;
    box-shadow: var(--shadow-soft);
    display: flex;
    align-items: center;
    transition: var(--transition-smooth);
}

.search-box:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.search-box input {
    border: none;
    outline: none;
    width: 100%;
    margin-left: 1rem;
    font-size: 1.1rem;
}

/* Category Cards */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    padding: 2rem 0;
}

.category-card {
    background: var(--white);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow-soft);
    transition: var(--transition-smooth);
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: var(--gradient-primary);
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.category-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-light);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.category-icon i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.feature-list {
    list-style: none;
    padding: 0;
    margin-top: 1rem;
}

.feature-list li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
    color: var(--text-light);
}

.feature-list li::before {
    content: '✓';
    color: var(--primary-color);
    margin-right: 0.5rem;
    font-weight: bold;
}
</style>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('featureSearch');
    const categoryCards = document.querySelectorAll('.category-card');

    // Keyboard shortcut for search
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    // Search functionality
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        categoryCards.forEach(card => {
            const category = card.dataset.category;
            const features = card.querySelectorAll('.feature-list li');
            let hasMatch = false;

            features.forEach(feature => {
                const text = feature.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    hasMatch = true;
                    feature.style.backgroundColor = 'var(--primary-light)';
                } else {
                    feature.style.backgroundColor = 'transparent';
                }
            });

            card.style.display = hasMatch ? 'block' : 'none';
        });
    });
});
</script>