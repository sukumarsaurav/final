<?php
// File: consultant-registration.php

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

// Initialize Stripe with API key from environment variable
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$page_title = "Consultant Registration";
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

// Get selected plan from URL if available
$selected_plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
$selected_plan = null;

if ($selected_plan_id > 0) {
    foreach ($plans as $plan) {
        if ($plan['id'] == $selected_plan_id) {
            $selected_plan = $plan;
            break;
        }
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

<div class="content">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
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
    <?php else: ?>
        <div class="registration-page">
            <div class="registration-container">
                <h1>Consultant Registration</h1>
                <p class="subtitle">Complete your registration to join Visafy as a consultant</p>
                
                <div class="registration-grid">
                    <!-- Registration Form Section -->
                    <div class="registration-form-container">
                        <form action="" method="POST" id="registrationForm">
                            <div class="form-section">
                                <h3>Personal Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name*</label>
                                        <input type="text" name="first_name" id="first_name" class="form-control" required value="<?php echo htmlspecialchars($first_name ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name*</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control" required value="<?php echo htmlspecialchars($last_name ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address*</label>
                                        <input type="email" name="email" id="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number*</label>
                                        <input type="tel" name="phone" id="phone" class="form-control" required value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="password">Password*</label>
                                        <input type="password" name="password" id="password" class="form-control" required>
                                        <small class="form-text">Must be at least 8 characters long</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password*</label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Company Information</h3>
                                <div class="form-group">
                                    <label for="company_name">Company Name (Optional)</label>
                                    <input type="text" name="company_name" id="company_name" class="form-control" value="<?php echo htmlspecialchars($company_name ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Billing Address</h3>
                                <div class="form-group">
                                    <label for="address_line1">Address Line 1*</label>
                                    <input type="text" name="address_line1" id="address_line1" class="form-control" required value="<?php echo htmlspecialchars($address_line1 ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="address_line2">Address Line 2</label>
                                    <input type="text" name="address_line2" id="address_line2" class="form-control" value="<?php echo htmlspecialchars($address_line2 ?? ''); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City*</label>
                                        <input type="text" name="city" id="city" class="form-control" required value="<?php echo htmlspecialchars($city ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="state">State/Province*</label>
                                        <input type="text" name="state" id="state" class="form-control" required value="<?php echo htmlspecialchars($state ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code*</label>
                                        <input type="text" name="postal_code" id="postal_code" class="form-control" required value="<?php echo htmlspecialchars($postal_code ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="country">Country*</label>
                                        <select name="country" id="country" class="form-control" required>
                                            <option value="">Select Country</option>
                                            <option value="US" <?php echo (($country ?? '') === 'US') ? 'selected' : ''; ?>>United States</option>
                                            <option value="CA" <?php echo (($country ?? '') === 'CA') ? 'selected' : ''; ?>>Canada</option>
                                            <option value="GB" <?php echo (($country ?? '') === 'GB') ? 'selected' : ''; ?>>United Kingdom</option>
                                            <option value="AU" <?php echo (($country ?? '') === 'AU') ? 'selected' : ''; ?>>Australia</option>
                                            <option value="IN" <?php echo (($country ?? '') === 'IN') ? 'selected' : ''; ?>>India</option>
                                            <!-- Add more countries as needed -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Payment Information</h3>
                                <div class="payment-form">
                                    <div class="form-group">
                                        <label for="card-element">Credit or Debit Card*</label>
                                        <div id="card-element" class="form-control">
                                            <!-- Stripe Element will be inserted here -->
                                        </div>
                                        <!-- Used to display form errors -->
                                        <div id="card-errors" role="alert" class="payment-error-message"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="membership_plan_id" id="membership_plan_id" value="<?php echo $selected_plan ? $selected_plan['id'] : ''; ?>">
                            <input type="hidden" name="stripe_token" id="stripe_token">
                            
                            <div class="terms-privacy">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="terms_agree" id="terms_agree" required>
                                    <label for="terms_agree">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <a href="become-member.php" class="btn cancel-btn">Back to Plans</a>
                                <button type="submit" name="register_member" id="register_member_btn" class="btn submit-btn">Register Now</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Plan Summary Section -->
                    <div class="plan-summary-container">
                        <div class="plan-summary">
                            <h3>Selected Plan</h3>
                            
                            <?php if ($selected_plan): ?>
                                <div class="selected-plan">
                                    <div class="plan-header">
                                        <h4 class="plan-name"><?php echo htmlspecialchars($selected_plan['name']); ?></h4>
                                        <div class="plan-price">$<?php echo number_format($selected_plan['price'], 2); ?></div>
                                        <div class="plan-billing">per month</div>
                                    </div>
                                    <div class="plan-features">
                                        <div class="feature">
                                            <i class="fas fa-users"></i>
                                            <div>Up to <?php echo (int)$selected_plan['max_team_members']; ?> team members</div>
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
                                </div>
                            <?php else: ?>
                                <div class="no-plan-selected">
                                    <p>You haven't selected a plan yet.</p>
                                    <a href="become-member.php#membership-plans" class="btn btn-primary">Choose a Plan</a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="change-plan">
                                <h4>Want a different plan?</h4>
                                <div class="plan-options">
                                    <?php foreach ($plans as $plan): ?>
                                        <div class="plan-option <?php echo $selected_plan && $selected_plan['id'] == $plan['id'] ? 'selected' : ''; ?>">
                                            <input type="radio" name="plan_selection" id="plan-<?php echo $plan['id']; ?>" value="<?php echo $plan['id']; ?>" 
                                                <?php echo $selected_plan && $selected_plan['id'] == $plan['id'] ? 'checked' : ''; ?>
                                                onchange="updateSelectedPlan(<?php echo $plan['id']; ?>)">
                                            <label for="plan-<?php echo $plan['id']; ?>">
                                                <span class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></span>
                                                <span class="plan-price">$<?php echo number_format($plan['price'], 2); ?>/month</span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="registration-help">
                            <h4>Need Help?</h4>
                            <p>If you have any questions about our membership plans or the registration process, please contact us.</p>
                            <a href="contact.php" class="btn btn-secondary btn-small">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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

.content {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
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

.registration-page {
    margin: 20px 0;
}

.registration-container {
    max-width: 1100px;
    margin: 0 auto;
}

.registration-container h1 {
    color: var(--dark-blue);
    text-align: center;
    margin-bottom: 10px;
}

.subtitle {
    text-align: center;
    color: var(--text-light);
    margin-bottom: 30px;
}

.registration-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.registration-form-container {
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow);
}

.form-section {
    margin-bottom: 30px;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 20px;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 20px;
}

.form-section h3 {
    color: var(--dark-blue);
    font-size: 1.2rem;
    margin-bottom: 15px;
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

.payment-error-message {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 8px;
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

.form-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.btn {
    padding: 12px 25px;
    border-radius: var(--border-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-block;
    cursor: pointer;
    border: none;
    font-size: 1rem;
}

.cancel-btn {
    background-color: var(--background-light);
    color: var(--text-light);
    border: 1px solid var(--border-color);
}

.cancel-btn:hover {
    background-color: #e9ecef;
}

.submit-btn {
    background-color: var(--primary-color);
    color: var(--white);
}

.submit-btn:hover {
    background-color: var(--dark-blue);
}

.submit-btn.disabled {
    background-color: #cccccc;
    cursor: not-allowed;
    opacity: 0.7;
}

.processing-payment {
    display: inline-block;
    margin-left: 10px;
}

/* Plan Summary Styles */
.plan-summary-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.plan-summary {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 25px;
}

.plan-summary h3 {
    color: var(--dark-blue);
    font-size: 1.2rem;
    margin-bottom: 20px;
    text-align: center;
}

.selected-plan {
    margin-bottom: 25px;
}

.plan-header {
    text-align: center;
    margin-bottom: 20px;
}

.plan-name {
    color: var(--dark-blue);
    font-size: 1.4rem;
    margin: 0 0 5px;
    font-weight: 600;
}

.plan-price {
    font-size: 1.8rem;
    color: var(--primary-color);
    font-weight: 700;
}

.plan-billing {
    color: var(--text-light);
    font-size: 0.9rem;
}

.plan-features {
    margin-bottom: 20px;
}

.feature {
    display: flex;
    align-items: flex-start;
    margin-bottom: 12px;
}

.feature i {
    color: var(--primary-color);
    margin-right: 10px;
    margin-top: 4px;
    flex-shrink: 0;
}

.no-plan-selected {
    text-align: center;
    padding: 30px 0;
}

.no-plan-selected p {
    margin-bottom: 15px;
    color: var(--text-light);
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--dark-blue);
}

.change-plan {
    margin-top: 30px;
}

.change-plan h4 {
    color: var(--dark-blue);
    font-size: 1.1rem;
    margin-bottom: 15px;
    text-align: center;
}

.plan-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.plan-option {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.plan-option:hover {
    background-color: var(--primary-light);
}

.plan-option.selected {
    background-color: var(--primary-light);
    border-color: var(--primary-color);
}

.plan-option input[type="radio"] {
    margin-right: 15px;
}

.plan-option label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex: 1;
    cursor: pointer;
}

.plan-option .plan-name {
    font-size: 1rem;
    font-weight: 500;
}

.plan-option .plan-price {
    font-size: 1rem;
    color: var(--text-color);
}

.registration-help {
    background-color: var(--background-light);
    border-radius: var(--border-radius);
    padding: 25px;
    text-align: center;
}

.registration-help h4 {
    color: var(--dark-blue);
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.registration-help p {
    color: var(--text-light);
    margin-bottom: 15px;
}

.btn-secondary {
    background-color: var(--white);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background-color: var(--background-light);
}

.btn-small {
    padding: 8px 15px;
    font-size: 0.9rem;
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

/* Responsive styles */
@media (max-width: 991px) {
    .registration-grid {
        grid-template-columns: 1fr;
    }
    
    .plan-summary-container {
        order: -1;
    }
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<!-- Include Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Stripe using the publishable key from environment
    const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLISHABLE_KEY']; ?>');
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
    
    // Mount the card element right away
    card.mount('#card-element');
    
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
    const form = document.getElementById('registrationForm');
    const submitButton = document.getElementById('register_member_btn');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Disable the submit button to prevent multiple submissions
            submitButton.disabled = true;
            submitButton.classList.add('disabled');
            submitButton.innerHTML = 'Processing Payment... <span class="processing-payment"><i class="fas fa-spinner fa-spin"></i></span>';
            
            // Get the cardholder name from the form
            const cardholderName = document.getElementById('first_name').value + ' ' + document.getElementById('last_name').value;
            
            // Create a token with card info and billing details
            stripe.createToken(card, {
                name: cardholderName,
                address_line1: document.getElementById('address_line1').value,
                address_line2: document.getElementById('address_line2').value,
                address_city: document.getElementById('city').value,
                address_state: document.getElementById('state').value,
                address_zip: document.getElementById('postal_code').value,
                address_country: document.getElementById('country').value
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
});

// Function to update selected plan
function updateSelectedPlan(planId) {
    document.getElementById('membership_plan_id').value = planId;
    // Optional: redirect to same page with plan_id parameter to refresh the view
    window.location.href = 'consultant-registration.php?plan_id=' + planId;
}
</script>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>
