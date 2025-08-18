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
<!-- Hero Section -->
<section class="hero consultant-hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Manage your Immigration Law Firm. Grow your clients.</h1>
                <p class="hero-subtitle"> The only Case Management Software you need. Try it for free today."Clear, Simple, Flexible plans for Immigration firms of any size</p>
            </div>
            <div class="hero-image-container">
                <img src="assets/images/main-consultant.png" alt="Main Consultant" class="hero-image">
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
                                <img src="assets/images/main-consultant.png" alt="Visa Processing">
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
                                <img src="assets/images/main-consultant.png" alt="Service Management">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Growth Section -->
              
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