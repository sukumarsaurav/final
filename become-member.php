<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Include session management
require_once "includes/session.php";

// Include config files
require_once "config/db_connect.php";
require_once "config/email_config.php";
require_once "includes/email_function.php";

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
if (file_exists(__DIR__ . '/config/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/config');
    $dotenv->load();
}

// Stripe API Keys - get from environment variables or fallback to test keys
$stripe_publishable_key = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? 'pk_test_51KOPdxSFv85B7DwKRJlqHA6GhJ3VVzDxvtpwCk2ANgCtxfi7lG8Rs3iNuCRBMhwja33vVRtcqlsmIOjvMkhalqMA00YC8HK4Zi';
$stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'] ?? 'sk_test_51KOPdxSFv85B7DwK7hyEvsCCjA1dT87FU3PqFYlzEHfcmtaEpT2HZEqSztQtqrFYZ1R5inH0GuFUFz5YreFtLyU0002Yvaubla';

// Initialize Stripe
\Stripe\Stripe::setApiKey($stripe_secret_key);

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

// Check if form is submitted
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_member'])) {
    // Personal Information
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Company Information
    $company_name = trim($_POST['company_name'] ?? '');
    
    // Billing Address Information
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    // Plan Selection
    $membership_plan_id = isset($_POST['membership_plan_id']) ? (int)$_POST['membership_plan_id'] : 0;
    
    // Payment Information from hidden fields populated by Stripe.js
    $stripe_token = isset($_POST['stripe_token']) ? $_POST['stripe_token'] : '';
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email is not valid";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($password)) $errors[] = "Password is required";
    elseif (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if ($membership_plan_id === 0) $errors[] = "Please select a membership plan";
    if (empty($stripe_token)) $errors[] = "Payment information is required";
    
    // Validate address fields
    if (empty($address_line1)) $errors[] = "Address Line 1 is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State/Province is required";
    if (empty($postal_code)) $errors[] = "Postal Code is required";
    if (empty($country)) $errors[] = "Country is required";
    
    // Check if email already exists
    $check_email_query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email is already registered. Please use a different email or login.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        // Get the selected plan details from the database
        $plan_query = "SELECT * FROM membership_plans WHERE id = ?";
        $stmt = $conn->prepare($plan_query);
        $stmt->bind_param('i', $membership_plan_id);
        $stmt->execute();
        $plan_result = $stmt->get_result();
        $plan = $plan_result->fetch_assoc();
        $stmt->close();
        
        if (!$plan) {
            $errors[] = "Selected plan not found.";
        } else {
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // 1. Create a Stripe Customer
                $stripe_customer = \Stripe\Customer::create([
                    'email' => $email,
                    'name' => $first_name . ' ' . $last_name,
                    'phone' => $phone,
                    'source' => $stripe_token, // Attach the payment method
                    'description' => 'Visafy Consultant - ' . ($company_name ?: ($first_name . ' ' . $last_name)),
                    'address' => [
                        'line1' => $address_line1,
                        'line2' => $address_line2,
                        'city' => $city,
                        'state' => $state,
                        'postal_code' => $postal_code,
                        'country' => $country,
                    ],
                    'metadata' => [
                        'membership_plan_id' => $membership_plan_id,
                        'company_name' => $company_name,
                    ]
                ]);
                
                // 2. Create a subscription
                $subscription = \Stripe\Subscription::create([
                    'customer' => $stripe_customer->id,
                    'items' => [
                        [
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => $plan['name'] . ' Membership Plan',
                                    'description' => 'Up to ' . $plan['max_team_members'] . ' team members'
                                ],
                                'unit_amount' => $plan['price'] * 100, // Stripe requires amount in cents
                                'recurring' => [
                                    'interval' => 'month',
                                ]
                            ],
                        ],
                    ],
                ]);
                
                // 3. Create organization first using company name or user name
                $org_name = !empty($company_name) ? $company_name : $first_name . ' ' . $last_name . "'s Organization";
                $org_description = "Organization for " . $first_name . " " . $last_name;
                
                $insert_org_query = "INSERT INTO organizations (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_org_query);
                $stmt->bind_param('ss', $org_name, $org_description);
                $stmt->execute();
                
                // Get organization ID
                $organization_id = $conn->insert_id;
                
                // 4. Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // 5. Insert user with organization_id
                $insert_user_query = "INSERT INTO users (first_name, last_name, email, phone, password, user_type, email_verified, organization_id) 
                                     VALUES (?, ?, ?, ?, ?, 'consultant', 0, ?)";
                $stmt = $conn->prepare($insert_user_query);
                $stmt->bind_param('sssssi', $first_name, $last_name, $email, $phone, $hashed_password, $organization_id);
                $stmt->execute();
                
                // Get user ID
                $user_id = $conn->insert_id;
                
                // 6. Insert consultant
                $insert_consultant_query = "INSERT INTO consultants (user_id, membership_plan_id, company_name) 
                                           VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_consultant_query);
                $stmt->bind_param('iis', $user_id, $membership_plan_id, $company_name);
                $stmt->execute();
                
                // 7. Add an entry in consultant_profiles table with default values
                $insert_profile_query = "INSERT INTO consultant_profiles (consultant_id) VALUES (?)";
                $stmt = $conn->prepare($insert_profile_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                
                // 8. Insert payment method
                $insert_payment_query = "INSERT INTO payment_methods (user_id, method_type, provider, account_number, token, billing_address_line1, billing_address_line2, billing_city, billing_state, billing_postal_code, billing_country, is_default) 
                                        VALUES (?, 'credit_card', 'stripe', ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $last_four = substr($stripe_token, -4); // This is simplified - in reality you'd get last4 from Stripe
                $stmt = $conn->prepare($insert_payment_query);
                $stmt->bind_param('issssssssss', $user_id, $last_four, $stripe_customer->id, $address_line1, $address_line2, $city, $state, $postal_code, $country);
                $stmt->execute();
                
                // 9. Insert subscription
                $insert_subscription_query = "INSERT INTO subscriptions (user_id, membership_plan_id, payment_method_id, status, start_date, end_date, auto_renew) 
                                             VALUES (?, ?, LAST_INSERT_ID(), 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1)";
                $stmt = $conn->prepare($insert_subscription_query);
                $stmt->bind_param('ii', $user_id, $membership_plan_id);
                $stmt->execute();
                
                // 10. Generate verification token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Update user with verification token
                $update_token_query = "UPDATE users SET email_verification_token = ?, email_verification_expires = ? WHERE id = ?";
                $stmt = $conn->prepare($update_token_query);
                $stmt->bind_param('ssi', $token, $expires, $user_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Send verification email
                $verification_link = "https://visafy.io/verify_email.php?token=" . $token;
                $email_subject = "Verify Your Email Address";
                $email_body = "Hi $first_name,\n\nPlease click the following link to verify your email address:\n$verification_link\n\nThis link will expire in 24 hours.\n\nThank you,\nThe Visafy Team";
                
                // Use your email function to send verification email
                if(function_exists('send_email')) {
                    send_email($email, $email_subject, $email_body);
                }
                
                $success_message = "Registration successful! Please check your email to verify your account.";
                
                // Clear form data
                $first_name = $last_name = $email = $phone = $company_name = $address_line1 = $address_line2 = $city = $state = $postal_code = $country = '';
                
            } catch (\Stripe\Exception\CardException $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Payment error: " . $e->getMessage();
            } catch (\Stripe\Exception\RateLimitException $e) {
                $conn->rollback();
                $error_message = "Too many requests to Stripe. Please try again later.";
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $conn->rollback();
                $error_message = "Invalid payment information: " . $e->getMessage();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                $conn->rollback();
                $error_message = "Authentication with Stripe failed. Please contact support.";
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                $conn->rollback();
                $error_message = "Network error. Please try again later.";
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $conn->rollback();
                $error_message = "Payment error: " . $e->getMessage();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Registration failed: " . $e->getMessage();
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!-- Hero Section -->
<section class="hero consultant-hero">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <h1 class="hero-title">Grow Your Immigration Consulting Business</h1>
                <p class="hero-subtitle">Join Visafy to streamline your services, manage clients efficiently, and scale your practice</p>
                <div class="hero-buttons">
                    <a href="#membership-plans" class="btn btn-primary">View Membership Plans</a>
                    <a href="contact.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            <div class="hero-image-container">
                <div class="floating-image-hero">
                    <img src="assets/images/consultant-hero.png" alt="Immigration Consultant">
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <!-- Consultant Benefits Section -->
    <section class="section platform-benefits">
        <div class="container">
            <h2 class="section-title" data-aos="fade-up">Why Join Visafy as a Consultant</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Our platform is designed to help you deliver exceptional immigration services</p>
            
            <div class="benefits-container">
                <!-- Client Management Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Streamlined Client Management</h3>
                            <p class="benefit-description">
                                Efficiently manage all your clients in one place with our comprehensive dashboard.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Centralized Client Database</strong>
                                        <p>Maintain detailed profiles for all your clients with searchable data and custom fields</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Case Tracking System</strong>
                                        <p>Monitor application progress with customizable milestones and automated deadline reminders</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Team Collaboration Tools</strong>
                                        <p>Assign tasks, share notes, and collaborate with your team members on cases</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <!-- SVG Shape Background -->
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
                
                <!-- Document Management Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content reverse">
                        <div class="benefit-image">
                            <!-- SVG Shape Background -->
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-3">
                                    <path d="M39.9,-68.1C52.6,-62.1,64.5,-53.1,72.7,-41C80.9,-28.8,85.4,-14.4,83.9,-0.9C82.3,12.7,74.8,25.4,66.4,37.8C58,50.3,48.7,62.5,36.5,70.1C24.2,77.7,9.1,80.7,-5.9,79.5C-20.9,78.3,-35.9,72.9,-47.5,64C-59.1,55,-67.3,42.5,-73.4,28.5C-79.5,14.5,-83.5,-1,-80.8,-15.2C-78.1,-29.4,-68.7,-42.3,-56.8,-48.9C-44.9,-55.5,-30.5,-55.8,-17.7,-61.8C-4.9,-67.8,6.3,-79.5,18.4,-80.5C30.5,-81.5,43.5,-71.8,39.9,-68.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/documents.png" alt="Document Management">
                            </div>
                        </div>
                        <div class="benefit-info">
                            <h3>Secure Document Management</h3>
                            <p class="benefit-description">
                                Organize, store, and share client documents securely through our encrypted platform.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Document Templates</strong>
                                        <p>Access a library of customizable templates for various immigration applications</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Secure Client Portal</strong>
                                        <p>Allow clients to securely upload and view their documents with controlled access</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Document Verification System</strong>
                                        <p>Easily review, approve, or request revisions to client-submitted documents</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Business Growth Section -->
                <div class="benefit-section" data-aos="fade-up">
                    <div class="benefit-content">
                        <div class="benefit-info">
                            <h3>Business Growth Tools</h3>
                            <p class="benefit-description">
                                Scale your practice with our suite of tools designed to help you acquire and retain clients.
                            </p>
                            <ul class="benefit-features">
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Consultant Profile Page</strong>
                                        <p>Create a professional profile showcasing your expertise and services to potential clients</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Client Engagement Tools</strong>
                                        <p>Automated follow-ups, appointment scheduling, and service reminders to enhance client experience</p>
                                    </div>
                                </li>
                                <li>
                                    <div class="check-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <strong>Analytics Dashboard</strong>
                                        <p>Monitor your business performance with visual reports on case types, success rates, and revenue</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="benefit-image">
                            <!-- SVG Shape Background -->
                            <div class="svg-background">
                                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="shape shape-5">
                                    <path d="M48.2,-76.1C63.3,-69.2,77.2,-58.4,84.6,-44.2C92,-30,92.8,-12.5,89.6,3.7C86.3,19.9,78.9,34.8,68.9,47.9C58.9,61,46.2,72.3,31.5,77.8C16.8,83.2,0.1,82.8,-16.4,79.7C-32.9,76.6,-49.2,70.8,-62.7,60.3C-76.2,49.8,-87,34.6,-90.9,17.8C-94.8,0.9,-91.9,-17.5,-84.2,-32.8C-76.5,-48.1,-64,-60.2,-49.5,-67.5C-35,-74.8,-18.5,-77.3,-1.2,-75.5C16.1,-73.7,33.1,-83,48.2,-76.1Z" transform="translate(100 100)" />
                                </svg>
                            </div>
                            <div class="image-container">
                                <img src="assets/images/business-growth.png" alt="Business Growth">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="registration-container" id="membership-plans">
        <?php if (empty($success_message)): ?>
            <div class="membership-plans">
                <h2>Choose Your Membership Plan</h2>
                <p>Select the plan that best fits your business needs</p>
                
                <!-- Membership Plans -->
                <div class="plans-grid">
                    <?php
                    if (count($plans) > 0) {
                        foreach ($plans as $plan): 
                    ?>
                        <div class="plan-card" data-plan-id="<?php echo $plan['id']; ?>">
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
                                <button type="button" class="btn select-plan-btn" 
                                    data-plan-id="<?php echo $plan['id']; ?>" 
                                    data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>" 
                                    data-plan-price="<?php echo number_format($plan['price'], 2); ?>" 
                                    data-plan-cycle="monthly" 
                                    data-plan-members="<?php echo (int)$plan['max_team_members']; ?>">
                                    Select Plan
                                </button>
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
        <?php else: ?>
            <div class="registration-success">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Registration Successful!</h2>
                <p>Thank you for registering. Please check your email to verify your account.</p>
                <p>Once verified, you'll be able to log in and set up your profile.</p>
                <div class="success-actions">
                    <a href="login.php" class="btn primary-btn">Go to Login</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Plan Selection Modal -->
<div class="modal" id="planSelectionModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Complete Your Registration</h3>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="selected-plan-info">
                    <h4>Selected Plan: <span id="selected-plan-name"></span></h4>
                    <div class="plan-details">
                        <div class="detail">
                            <span class="label">Price:</span>
                            <span class="value">$<span id="selected-plan-price"></span> per month</span>
                        </div>
                        <div class="detail">
                            <span class="label">Team Members:</span>
                            <span class="value">Up to <span id="selected-plan-members"></span></span>
                        </div>
                    </div>
                </div>

                <form action="" method="POST" id="planRegistrationForm">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_first_name">First Name*</label>
                            <input type="text" name="first_name" id="modal_first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_last_name">Last Name*</label>
                            <input type="text" name="last_name" id="modal_last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_email">Email Address*</label>
                            <input type="email" name="email" id="modal_email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_phone">Phone Number*</label>
                            <input type="tel" name="phone" id="modal_phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_password">Password*</label>
                            <input type="password" name="password" id="modal_password" class="form-control" required>
                            <small class="form-text">Must be at least 8 characters long</small>
                        </div>
                        <div class="form-group">
                            <label for="modal_confirm_password">Confirm Password*</label>
                            <input type="password" name="confirm_password" id="modal_confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <h3>Company Information</h3>
                    <div class="form-group">
                        <label for="modal_company_name">Company Name (Optional)</label>
                        <input type="text" name="company_name" id="modal_company_name" class="form-control">
                    </div>
                    
                    <h3>Billing Address</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_address_line1">Address Line 1*</label>
                            <input type="text" name="address_line1" id="modal_address_line1" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_address_line2">Address Line 2</label>
                            <input type="text" name="address_line2" id="modal_address_line2" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_city">City*</label>
                            <input type="text" name="city" id="modal_city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_state">State/Province*</label>
                            <input type="text" name="state" id="modal_state" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_postal_code">Postal Code*</label>
                            <input type="text" name="postal_code" id="modal_postal_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="modal_country">Country*</label>
                            <select name="country" id="modal_country" class="form-control" required>
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="IN">India</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                    </div>
                    
                    <h3>Payment Information</h3>
                    <div class="payment-form">
                        <!-- Stripe Elements Placeholder -->
                        <div class="form-group">
                            <label for="card-element">Credit or Debit Card*</label>
                            <div id="card-element" class="form-control">
                                <!-- Stripe Element will be inserted here -->
                            </div>
                            <!-- Used to display form errors -->
                            <div id="card-errors" role="alert" class="payment-error-message"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="membership_plan_id" id="modal_membership_plan_id">
                    <input type="hidden" name="stripe_token" id="stripe_token">
                    
                    <div class="terms-privacy">
                        <div class="checkbox-group">
                            <input type="checkbox" name="terms_agree" id="modal_terms_agree" required>
                            <label for="modal_terms_agree">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn cancel-btn" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="register_member" id="register_member_btn" class="btn submit-btn">Register Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
}

.hero.consultant-hero {
    background-color: rgba(234, 170, 52, 0.05);
}

.hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: center;
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
</style>

<!-- Include Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Stripe
    const stripe = Stripe('<?php echo $stripe_publishable_key; ?>');
    const elements = stripe.elements();
    
    // Create card Element and mount it
    const card = elements.create('card', {
        style: {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#e74a3b',
                iconColor: '#e74a3b'
            }
        }
    });
    
    // Wait for the modal to be shown before mounting the card element
    const selectPlanButtons = document.querySelectorAll('.select-plan-btn');
    
    if (selectPlanButtons.length > 0) {
        selectPlanButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Allow time for modal to be visible before mounting
                setTimeout(() => {
                    card.mount('#card-element');
                }, 100);
            });
        });
    }
    
    // Handle real-time validation errors
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('planRegistrationForm');
    const submitButton = document.getElementById('register_member_btn');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Disable the submit button to prevent multiple submissions
            submitButton.disabled = true;
            submitButton.classList.add('disabled');
            submitButton.innerHTML = 'Processing Payment... <span class="processing-payment"><i class="fas fa-spinner fa-spin"></i></span>';
            
            // Get the cardholder name from the form
            const cardholderName = document.getElementById('modal_first_name').value + ' ' + document.getElementById('modal_last_name').value;
            
            // Create a token with card info and billing details
            stripe.createToken(card, {
                name: cardholderName,
                address_line1: document.getElementById('modal_address_line1').value,
                address_line2: document.getElementById('modal_address_line2').value,
                address_city: document.getElementById('modal_city').value,
                address_state: document.getElementById('modal_state').value,
                address_zip: document.getElementById('modal_postal_code').value,
                address_country: document.getElementById('modal_country').value
            }).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    
                    // Re-enable the submit button
                    submitButton.disabled = false;
                    submitButton.classList.remove('disabled');
                    submitButton.innerHTML = 'Register Now';
                } else {
                    // Send the token to your server
                    stripeTokenHandler(result.token);
                }
            });
        });
    }
    
    // Function to handle the token submission
    function stripeTokenHandler(token) {
        // Insert the token ID into the form so it gets submitted to the server
        const hiddenInput = document.getElementById('stripe_token');
        if (hiddenInput) {
            hiddenInput.value = token.id;
        }
        
        // Submit the form
        form.submit();
    }
    
    // Initialize AOS animations if available
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
    
    // Modal functionality
    const modal = document.getElementById('planSelectionModal');
    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
    
    if (modal && selectPlanButtons.length > 0) {
        // Open modal when clicking "Select Plan" button
        selectPlanButtons.forEach(button => {
            button.addEventListener('click', function() {
                const planId = this.getAttribute('data-plan-id');
                const planName = this.getAttribute('data-plan-name');
                const planPrice = this.getAttribute('data-plan-price');
                const planMembers = this.getAttribute('data-plan-members');
                
                // Set modal values
                document.getElementById('selected-plan-name').textContent = planName;
                document.getElementById('selected-plan-price').textContent = planPrice;
                document.getElementById('selected-plan-members').textContent = planMembers;
                document.getElementById('modal_membership_plan_id').value = planId;
                
                // Show modal
                modal.style.display = 'block';
            });
        });
        
        // Close modal when clicking close button or outside the modal
        if (closeButtons.length > 0) {
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                    // Unmount card element when closing modal to avoid issues when reopening
                    card.unmount();
                });
            });
        }
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                // Unmount card element when closing modal
                card.unmount();
            }
        });
    }
    
    // Form validation
    const passwordField = document.getElementById('modal_password');
    const confirmPasswordField = document.getElementById('modal_confirm_password');
    
    if (passwordField && confirmPasswordField) {
        // Check password match on input
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity("Passwords don't match");
            } else {
                confirmPasswordField.setCustomValidity('');
            }
        });
        
        // Check password strength
        passwordField.addEventListener('input', function() {
            const password = passwordField.value;
            
            // Basic validation
            if (password.length < 8) {
                passwordField.setCustomValidity("Password must be at least 8 characters long");
            } else {
                passwordField.setCustomValidity('');
            }
            
            // If confirm password is already filled, check match
            if (confirmPasswordField.value) {
                if (password !== confirmPasswordField.value) {
                    confirmPasswordField.setCustomValidity("Passwords don't match");
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }
        });
    }
});
</script>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>