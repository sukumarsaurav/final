<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Include session management
require_once "includes/session.php";

// Include config files
require_once "config/db_connect.php";
require_once "config/email_config.php";
require_once "includes/email_function.php";

$page_title = "Become a Member";
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get membership plans
$query = "SELECT * FROM membership_plans ORDER BY price ASC";
$result = $conn->query($query);
$plans = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ensure billing_cycle is one of the expected values
        if (!in_array($row['billing_cycle'], ['monthly', 'quarterly', 'annually'])) {
            // Set a default if not valid
            $row['billing_cycle'] = 'monthly';
        }
        $plans[] = $row;
    }
}

// Debug - Count plans by billing cycle
$monthly_count = 0;
$quarterly_count = 0;
$annually_count = 0;

foreach ($plans as $plan) {
    if ($plan['billing_cycle'] === 'monthly') $monthly_count++;
    elseif ($plan['billing_cycle'] === 'quarterly') $quarterly_count++;
    elseif ($plan['billing_cycle'] === 'annually') $annually_count++;
}

// If no plans for a cycle, create a dummy plan
if ($monthly_count === 0) {
    $plans[] = [
        'id' => 'dummy_monthly',
        'name' => 'Basic Monthly',
        'price' => 9.99,
        'billing_cycle' => 'monthly',
        'max_team_members' => 1
    ];
}

if ($quarterly_count === 0) {
    $plans[] = [
        'id' => 'dummy_quarterly',
        'name' => 'Basic Quarterly',
        'price' => 24.99,
        'billing_cycle' => 'quarterly',
        'max_team_members' => 1
    ];
}

if ($annually_count === 0) {
    $plans[] = [
        'id' => 'dummy_annually',
        'name' => 'Basic Annual',
        'price' => 89.99,
        'billing_cycle' => 'annually',
        'max_team_members' => 1
    ];
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
    
    // Plan Selection
    $membership_plan_id = isset($_POST['membership_plan_id']) ? (int)$_POST['membership_plan_id'] : 0;
    
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
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create organization first using company name or user name
            $org_name = !empty($company_name) ? $company_name : $first_name . ' ' . $last_name . "'s Organization";
            $org_description = "Organization for " . $first_name . " " . $last_name;
            
            $insert_org_query = "INSERT INTO organizations (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_org_query);
            $stmt->bind_param('ss', $org_name, $org_description);
            $stmt->execute();
            
            // Get organization ID
            $organization_id = $conn->insert_id;
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with organization_id
            $insert_user_query = "INSERT INTO users (first_name, last_name, email, phone, password, user_type, email_verified, organization_id) 
                                 VALUES (?, ?, ?, ?, ?, 'consultant', 0, ?)";
            $stmt = $conn->prepare($insert_user_query);
            $stmt->bind_param('sssssi', $first_name, $last_name, $email, $phone, $hashed_password, $organization_id);
            $stmt->execute();
            
            // Get user ID
            $user_id = $conn->insert_id;
            
            // Insert consultant
            $insert_consultant_query = "INSERT INTO consultants (user_id, membership_plan_id, company_name) 
                                       VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_consultant_query);
            $stmt->bind_param('iis', $user_id, $membership_plan_id, $company_name);
            $stmt->execute();
            
            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Update user with verification token
            $update_token_query = "UPDATE users SET email_verification_token = ?, email_verification_expires = ? WHERE id = ?";
            $stmt = $conn->prepare($update_token_query);
            $stmt->bind_param('ssi', $token, $expires, $user_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Send verification email (this is a placeholder - implement actual email sending)
            $verification_link = "https://visafy.io/verify_email.php?token=" . $token;
            $email_subject = "Verify Your Email Address";
            $email_body = "Hi $first_name,\n\nPlease click the following link to verify your email address:\n$verification_link\n\nThis link will expire in 24 hours.\n\nThank you,\nThe Visafy Team";
            
            // Use your email function to send verification email
            if(function_exists('send_email')) {
                send_email($email, $email_subject, $email_body);
            }
            
            $success_message = "Registration successful! Please check your email to verify your account.";
            
            // Clear form data
            $first_name = $last_name = $email = $phone = $company_name = '';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Registration failed: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<div class="content">
    <div class="header-container">
        <div>
            <h1>Become a Member</h1>
            <p>Join our platform as a consultant and start managing your visa services.</p>
        </div>
    </div>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <div class="registration-container">
        <?php if (empty($success_message)): ?>
            <div class="membership-plans">
                <h2>Choose Your Membership Plan</h2>
                <p>Select the plan that best fits your business needs</p>
                
                <!-- Billing cycle tabs -->
                <div class="billing-cycle-tabs">
                    <div class="tabs-container">
                        <div class="tab active" data-cycle="monthly">Monthly</div>
                        <div class="tab" data-cycle="quarterly">Quarterly</div>
                        <div class="tab" data-cycle="annually">Annually</div>
                    </div>
                </div>
                
                <!-- Monthly Plans -->
                <div class="plans-grid cycle-plans active" id="monthly-plans">
                    <?php
                    foreach ($plans as $plan): 
                        if ($plan['billing_cycle'] !== 'monthly') continue; // Skip non-monthly plans
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
                                <button type="button" class="btn select-plan-btn" data-plan-id="<?php echo $plan['id']; ?>" data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>" data-plan-price="<?php echo number_format($plan['price'], 2); ?>" data-plan-cycle="monthly" data-plan-members="<?php echo (int)$plan['max_team_members']; ?>">Select Plan</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quarterly Plans -->
                <div class="plans-grid cycle-plans" id="quarterly-plans">
                    <?php
                    foreach ($plans as $plan): 
                        if ($plan['billing_cycle'] !== 'quarterly') continue; // Skip non-quarterly plans
                    ?>
                        <div class="plan-card" data-plan-id="<?php echo $plan['id']; ?>">
                            <div class="plan-header">
                                <h3 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <div class="plan-price">$<?php echo number_format($plan['price'], 2); ?></div>
                                <div class="plan-billing">per quarter</div>
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
                                <button type="button" class="btn select-plan-btn" data-plan-id="<?php echo $plan['id']; ?>" data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>" data-plan-price="<?php echo number_format($plan['price'], 2); ?>" data-plan-cycle="quarterly" data-plan-members="<?php echo (int)$plan['max_team_members']; ?>">Select Plan</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Annual Plans -->
                <div class="plans-grid cycle-plans" id="annually-plans">
                    <?php
                    foreach ($plans as $plan): 
                        if ($plan['billing_cycle'] !== 'annually') continue; // Skip non-annual plans
                    ?>
                        <div class="plan-card" data-plan-id="<?php echo $plan['id']; ?>">
                            <div class="plan-header">
                                <h3 class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <div class="plan-price">$<?php echo number_format($plan['price'], 2); ?></div>
                                <div class="plan-billing">per year</div>
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
                                <button type="button" class="btn select-plan-btn" data-plan-id="<?php echo $plan['id']; ?>" data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>" data-plan-price="<?php echo number_format($plan['price'], 2); ?>" data-plan-cycle="annually" data-plan-members="<?php echo (int)$plan['max_team_members']; ?>">Select Plan</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                            <span class="value">$<span id="selected-plan-price"></span></span>
                        </div>
                        <div class="detail">
                            <span class="label">Billing:</span>
                            <span class="value"><span id="selected-plan-cycle"></span></span>
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
                    
                    <input type="hidden" name="membership_plan_id" id="modal_membership_plan_id">
                    
                    <div class="terms-privacy">
                        <div class="checkbox-group">
                            <input type="checkbox" name="terms_agree" id="modal_terms_agree" required>
                            <label for="modal_terms_agree">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="button" class="btn cancel-btn" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="register_member" class="btn submit-btn">Register Now</button>
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

.billing-cycle-tabs {
    margin-bottom: 30px;
    display: flex;
    justify-content: center;
}

.tabs-container {
    display: flex;
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 1px solid var(--border-color);
    background-color: var(--white);
    box-shadow: var(--shadow-sm);
}

.tab {
    padding: 12px 25px;
    cursor: pointer;
    font-weight: 600;
    color: var(--text-light);
    transition: var(--transition);
    position: relative;
}

.tab:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0;
    top: 20%;
    height: 60%;
    width: 1px;
    background-color: var(--border-color);
}

.tab.active {
    background-color: var(--primary-color);
    color: var(--white);
}

.cycle-plans {
    display: none;
}

.cycle-plans.active {
    display: grid;
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

@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        text-align: center;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .plans-grid {
        grid-template-columns: 1fr;
    }
    
    .tabs-container {
        width: 100%;
    }
    
    .tab {
        flex: 1;
        text-align: center;
        padding: 12px 10px;
    }
    
    .plan-details {
        flex-direction: column;
        gap: 10px;
    }
    
    .detail {
        min-width: 100%;
    }
    
    .form-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .cancel-btn, .submit-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.tab');
    const cyclePlans = document.querySelectorAll('.cycle-plans');
    
    // Debug - log the available plans
    console.log('Monthly plans:', document.querySelectorAll('#monthly-plans .plan-card').length);
    console.log('Quarterly plans:', document.querySelectorAll('#quarterly-plans .plan-card').length);
    console.log('Annually plans:', document.querySelectorAll('#annually-plans .plan-card').length);
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all plan sections
            cyclePlans.forEach(p => p.classList.remove('active'));
            
            // Show the selected plan section
            const cycle = this.getAttribute('data-cycle');
            const planSection = document.getElementById(`${cycle}-plans`);
            if (planSection) {
                planSection.classList.add('active');
            } else {
                console.error(`Could not find plan section with ID: ${cycle}-plans`);
            }
        });
    });
    
    // Modal functionality
    const modal = document.getElementById('planSelectionModal');
    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
    const selectPlanButtons = document.querySelectorAll('.select-plan-btn');
    
    // Open modal when clicking "Select Plan" button
    selectPlanButtons.forEach(button => {
        button.addEventListener('click', function() {
            const planId = this.getAttribute('data-plan-id');
            const planName = this.getAttribute('data-plan-name');
            const planPrice = this.getAttribute('data-plan-price');
            const planCycle = this.getAttribute('data-plan-cycle');
            const planMembers = this.getAttribute('data-plan-members');
            
            // Set modal values
            document.getElementById('selected-plan-name').textContent = planName;
            document.getElementById('selected-plan-price').textContent = planPrice;
            document.getElementById('selected-plan-cycle').textContent = planCycle;
            document.getElementById('selected-plan-members').textContent = planMembers;
            document.getElementById('modal_membership_plan_id').value = planId;
            
            // Show modal
            modal.style.display = 'block';
            
            // Format the billing cycle display text
            let cycleText = "";
            switch(planCycle) {
                case 'monthly':
                    cycleText = "Monthly";
                    break;
                case 'quarterly':
                    cycleText = "Quarterly (Every 3 months)";
                    break;
                case 'annually':
                    cycleText = "Annually (Yearly)";
                    break;
                default:
                    cycleText = planCycle;
            }
            document.getElementById('selected-plan-cycle').textContent = cycleText;
        });
    });
    
    // Close modal when clicking close button or outside the modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
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
