<?php
// File: consultant-registration.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent 'headers already sent' errors
ob_start();

// Add debugging function
function debug_log($message, $data = null, $is_payment_info = false) {
    echo '<div class="debug-log" style="background: #f8d7da; padding: 10px; margin: 5px 0; border: 1px solid #f5c6cb; border-radius: 5px;">';
    echo '<strong>DEBUG:</strong> ' . htmlspecialchars($message);
    if ($data !== null) {
        echo '<pre style="margin: 5px 0 0 0; padding: 10px; background: #f1f1f1; border-radius: 3px; overflow: auto;">';
        if ($is_payment_info) {
            print_r($data);
        } else {
            print_r($data);
        }
        echo '</pre>';
    }
    echo '</div>';
}

// Debug step: Session check
debug_log("Starting registration process");

// Include session management
try {
    require_once "includes/session.php";
    debug_log("Session included successfully");
} catch (Exception $e) {
    debug_log("Error loading session file", $e->getMessage());
}

// Include config files
try {
    require_once "config/db_connect.php";
    debug_log("Database connection established");
} catch (Exception $e) {
    debug_log("Error connecting to database", $e->getMessage());
}

try {
    require_once "config/email_config.php";
    debug_log("Email configuration loaded");
} catch (Exception $e) {
    debug_log("Error loading email configuration", $e->getMessage());
}

try {
    require_once "includes/email_function.php";
    debug_log("Email functions loaded");
} catch (Exception $e) {
    debug_log("Error loading email functions", $e->getMessage());
}

// Load Composer's autoloader
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        debug_log("Composer autoloader loaded successfully");
        
        // Add this check right after we load the Composer autoloader
        if (!class_exists('\Stripe\Stripe')) {
            debug_log("ERROR: Stripe PHP SDK not found. Please run 'composer require stripe/stripe-php'");
            
            // We can set an error message to display to the user
            $error_message = "Payment processing is currently unavailable. Please contact support.";
        }
    } else {
        debug_log("ERROR: Composer autoloader not found. Please run 'composer install'");
    }
} catch (Exception $e) {
    debug_log("Error loading Composer autoloader", $e->getMessage());
}

// Load environment variables from .env file
try {
    if (file_exists(__DIR__ . '/config/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/config');
        $dotenv->load();
        debug_log("Environment variables loaded from .env file");
    } else {
        debug_log("ERROR: .env file not found in config directory");
    }
} catch (Exception $e) {
    debug_log("Error loading .env file", $e->getMessage());
}

// Get Stripe API keys from environment
$stripe_publishable_key = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
$stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'] ?? '';

// Check if Stripe keys are available
if (empty($stripe_publishable_key) || empty($stripe_secret_key)) {
    debug_log("ERROR: Stripe API keys are not configured properly", [
        'publishable_key_exists' => !empty($stripe_publishable_key),
        'secret_key_exists' => !empty($stripe_secret_key)
    ]);
} else {
    debug_log("Stripe API keys loaded", [
        'publishable_key' => substr($stripe_publishable_key, 0, 8) . '...',
        'secret_key' => substr($stripe_secret_key, 0, 8) . '...'
    ]);
}

// Initialize Stripe with API key from environment variable
try {
    if (class_exists('\Stripe\Stripe')) {
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        debug_log("Stripe initialized with API key");
    } else {
        debug_log("ERROR: Stripe class not found. Please install stripe/stripe-php via Composer");
    }
} catch (Exception $e) {
    debug_log("Error initializing Stripe", $e->getMessage());
}

$page_title = "Consultant Registration";
try {
    require_once 'includes/header.php';
    debug_log("Header included successfully");
} catch (Exception $e) {
    debug_log("Error including header", $e->getMessage());
}

try {
    require_once 'includes/functions.php';
    debug_log("Functions included successfully");
} catch (Exception $e) {
    debug_log("Error including functions", $e->getMessage());
}

// Get membership plans
try {
    $query = "SELECT * FROM membership_plans WHERE billing_cycle = 'monthly' ORDER BY price ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        debug_log("Database error when fetching membership plans", $conn->error);
    }
    
    $plans = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }
        debug_log("Membership plans loaded successfully", ['count' => count($plans)]);
    } else {
        debug_log("No membership plans found in database");
    }
} catch (Exception $e) {
    debug_log("Error fetching membership plans", $e->getMessage());
}

// Get selected plan from URL if available
$selected_plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
$selected_plan = null;

if ($selected_plan_id > 0) {
    foreach ($plans as $plan) {
        if ($plan['id'] == $selected_plan_id) {
            $selected_plan = $plan;
            debug_log("Selected plan found", ['id' => $selected_plan_id, 'name' => $plan['name']]);
            break;
        }
    }
    
    if ($selected_plan === null) {
        debug_log("Warning: Selected plan ID {$selected_plan_id} not found in available plans");
    }
}

// If no plan is selected and we have plans, select the first one
if (!$selected_plan && !empty($plans)) {
    $selected_plan = $plans[0];
    $selected_plan_id = $selected_plan['id'];
    debug_log("No plan selected, defaulting to first plan", ['id' => $selected_plan_id, 'name' => $selected_plan['name']]);
}

// Initialize form variables
$first_name = $last_name = $email = $phone = $company_name = $address_line1 = $address_line2 = $city = $state = $postal_code = $country = '';

// Check if form is submitted
$success_message = "";
$error_message = "";

// Special debug for POST request detection
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
debug_log("Current HTTP request method", $request_method);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debug_log("POST detected, checking for register_member", isset($_POST['register_member']) ? "present" : "missing");
    debug_log("POST vs GET values", [
        'post_count' => count($_POST),
        'get_count' => count($_GET),
        'post_keys' => array_keys($_POST),
        'js_submitted' => isset($_POST['js_submitted']) ? 'yes' : 'no'
    ]);
    
    // If the js_submitted flag is present, ensure register_member is set
    if (isset($_POST['js_submitted']) && $_POST['js_submitted'] === 'true' && !isset($_POST['register_member'])) {
        debug_log("JavaScript submission detected but register_member is missing - adding it now");
        $_POST['register_member'] = '1';
    }
    
    // Alternative detection: check if we have all the registration fields even if register_member is missing
    $required_registration_fields = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password', 'address_line1', 'city', 'state', 'postal_code', 'country', 'membership_plan_id', 'payment_method_id'];
    $has_all_required_fields = true;
    $missing_fields = [];
    
    foreach ($required_registration_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $has_all_required_fields = false;
            $missing_fields[] = $field;
        }
    }
    
    debug_log("Registration field check", [
        'has_all_required_fields' => $has_all_required_fields,
        'missing_fields' => $missing_fields
    ]);
    
    // If form has all fields but register_member is missing, treat it as a registration attempt
    if ($has_all_required_fields && !isset($_POST['register_member'])) {
        debug_log("Found all registration fields but register_member is missing - treating as registration");
        $_POST['register_member'] = '1'; // Add it manually
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_member'])) {
    debug_log("POST data structure", ['post_data' => $_POST]);
    debug_log("Membership plan ID", $_POST['membership_plan_id'] ?? 'not provided');
    debug_log("Payment method ID format", [
        'raw' => $_POST['payment_method_id'] ?? '',
        'length' => strlen($_POST['payment_method_id'] ?? ''),
        'starts_with' => substr($_POST['payment_method_id'] ?? '', 0, 3)
    ]);
    
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
    $payment_method_id = isset($_POST['payment_method_id']) ? $_POST['payment_method_id'] : '';
    
    debug_log("PAYMENT INFO", [
        'payment_method_id' => $payment_method_id ? substr($payment_method_id, 0, 10) . '...' : 'MISSING',
        'length' => strlen($payment_method_id),
        'valid_format' => preg_match('/^pm_/', $payment_method_id) ? 'Yes' : 'No'
    ], true);

    debug_log("Parsed form data", [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone,
        'company_name' => $company_name,
        'membership_plan_id' => $membership_plan_id,
        'payment_method_id' => $payment_method_id ? 'present (length: ' . strlen($payment_method_id) . ')' : 'missing'
    ]);
    
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
    if (empty($payment_method_id)) $errors[] = "Payment information is required";
    
    // Validate address fields
    if (empty($address_line1)) $errors[] = "Address Line 1 is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State/Province is required";
    if (empty($postal_code)) $errors[] = "Postal Code is required";
    if (empty($country)) $errors[] = "Country is required";
    
    if (!empty($errors)) {
        debug_log("Validation errors found", $errors);
    }
    
    // Check if email already exists
    try {
        $check_email_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email_query);
        
        if (!$stmt) {
            debug_log("Database error preparing email check query", $conn->error);
            $errors[] = "Database error. Please try again later.";
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                debug_log("Email already exists in database", ['email' => $email]);
                $errors[] = "Email is already registered. Please use a different email or login.";
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        debug_log("Error checking email existence", $e->getMessage());
        $errors[] = "Error checking email: " . $e->getMessage();
    }
    
    if (empty($errors)) {
        debug_log("Validation passed, proceeding with registration");
        
        // Get the selected plan details from the database
        try {
            $plan_query = "SELECT * FROM membership_plans WHERE id = ?";
            $stmt = $conn->prepare($plan_query);
            
            if (!$stmt) {
                debug_log("Database error preparing plan query", $conn->error);
                $errors[] = "Database error. Please try again later.";
            } else {
                $stmt->bind_param('i', $membership_plan_id);
                $stmt->execute();
                $plan_result = $stmt->get_result();
                $plan = $plan_result->fetch_assoc();
                $stmt->close();
                
                if (!$plan) {
                    debug_log("Selected plan not found in database", ['id' => $membership_plan_id]);
                    $errors[] = "Selected plan not found.";
                } else {
                    debug_log("Plan details retrieved", $plan);
                }
            }
        } catch (Exception $e) {
            debug_log("Error fetching plan details", $e->getMessage());
            $errors[] = "Error fetching plan details: " . $e->getMessage();
        }
        
        if (empty($errors)) {
            // Start transaction
            try {
                debug_log("Starting database transaction");
                
                // Begin transaction
                $conn->begin_transaction();
                
                // Stripe payment processing
                try {
                    debug_log("Processing with payment method ID", substr($payment_method_id, 0, 10) . '...');

                    // Display detailed debug info about the payment method ID
                    debug_log("Payment method ID details", [
                        'payment_method_id' => $payment_method_id,
                        'length' => strlen($payment_method_id),
                        'starts_with' => substr($payment_method_id, 0, 8),
                        'is_valid_format' => (bool)(preg_match('/^pm_/i', $payment_method_id))
                    ]);

                    // We don't need to create a new payment method since we already have the ID from the client
                    // We can either retrieve it or just use it directly
                    try {
                        $payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
                        debug_log("Payment method retrieved", [
                            'id' => $payment_method->id,
                            'type' => $payment_method->type,
                            'card_brand' => $payment_method->card->brand,
                            'card_last4' => $payment_method->card->last4
                        ]);
                    } catch (\Exception $e) {
                        debug_log("Error retrieving payment method", $e->getMessage());
                        throw new \Exception("Invalid payment method ID. Please check your payment information and try again.");
                    }
                    
                    debug_log("About to create Stripe customer");
                    try {
                        // Test if Stripe can be used by making a simple API call
                        $test_api = \Stripe\Balance::retrieve();
                        debug_log("Stripe API test succeeded", ['available' => $test_api->available[0]->amount ?? 'N/A']);
                    } catch (\Exception $e) {
                        debug_log("Stripe API test failed", ['error' => $e->getMessage()]);
                    }
                    
                    // Check if we already have a customer ID from 3DS authentication
                    $has_3ds_customer = isset($_POST['customer_id']) && !empty($_POST['customer_id']);
                    
                    if ($has_3ds_customer) {
                        debug_log("Using existing customer from 3DS authentication", ['customer_id' => $_POST['customer_id']]);
                        
                        // Retrieve the existing customer
                        try {
                            $stripe_customer = \Stripe\Customer::retrieve($_POST['customer_id']);
                            
                            // Update with any missing information
                            \Stripe\Customer::update($stripe_customer->id, [
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
                                    'temp_for_3ds' => 'false'
                                ]
                            ]);
                            
                            debug_log("Existing customer updated", ['customer_id' => $stripe_customer->id]);
                        } catch (\Exception $e) {
                            debug_log("Error retrieving or updating customer from 3DS process", $e->getMessage());
                            // If there's an error, we'll fall back to creating a new customer
                            $has_3ds_customer = false;
                        }
                    }
                    
                    // Create a new customer if we don't have one from 3DS
                    if (!$has_3ds_customer) {
                        debug_log("Creating new Stripe customer");
                        
                        // 1. Create a Stripe Customer first
                        $stripe_customer = \Stripe\Customer::create([
                            'email' => $email,
                            'name' => $first_name . ' ' . $last_name,
                            'phone' => $phone,
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
                    }
                    
                    // For Indian customers, the payment method should already be attached via the SetupIntent
                    $is_indian_customer = $country === 'IN';
                    
                    if (!$is_indian_customer) {
                        debug_log("Non-Indian customer - creating SetupIntent for 3DS authentication", [
                            'customer_id' => $stripe_customer->id
                        ]);
                        
                        // 2. Create a SetupIntent to securely save the card with 3DS if required
                        $setup_intent = \Stripe\SetupIntent::create([
                            'customer' => $stripe_customer->id,
                            'payment_method' => $payment_method->id,
                            'payment_method_types' => ['card'],
                            'usage' => 'off_session', // Allow future off-session payments
                            'metadata' => [
                                'user_email' => $email,
                                'registration_flow' => 'consultant'
                            ],
                            'confirm' => true, // Confirm the SetupIntent right away
                            'return_url' => 'https://visafy.io/registration-complete.php' // In case of 3DS redirect
                        ]);
                        
                        debug_log("SetupIntent created and confirmed", [
                            'setup_intent_id' => $setup_intent->id,
                            'status' => $setup_intent->status,
                            'next_action' => $setup_intent->next_action ? 'Required' : 'None'
                        ]);
                        
                        // 3. Check if 3DS authentication is required
                        if ($setup_intent->status === 'requires_action' && $setup_intent->next_action) {
                            // We need to handle 3DS authentication which requires redirecting the user
                            // For now, we'll throw an exception with instructions
                            debug_log("3DS authentication required", [
                                'redirect_url' => $setup_intent->next_action->redirect_to_url->url ?? 'None'
                            ]);
                            
                            $error_message = "3D Secure authentication is required for this card. Please try again and complete the authentication when prompted.";
                            throw new \Exception($error_message);
                        }
                        
                        if ($setup_intent->status !== 'succeeded') {
                            debug_log("SetupIntent did not succeed", ['status' => $setup_intent->status]);
                            throw new \Exception("Failed to save payment method. Status: " . $setup_intent->status);
                        }
                        
                        debug_log("Payment method setup successful - card is ready for billing");
                    } else {
                        debug_log("Indian customer - payment method already attached via 3DS");
                    }
                    
                    // 4. Set as default payment method
                    debug_log("Setting payment method as default for customer");
                    \Stripe\Customer::update(
                        $stripe_customer->id,
                        ['invoice_settings' => ['default_payment_method' => $payment_method->id]]
                    );
                    
                } catch (\Stripe\Exception\CardException $e) {
                    debug_log("Stripe Card Exception", [
                        'message' => $e->getMessage(),
                        'code' => $e->getStripeCode(),
                        'decline_code' => $e->getDeclineCode()
                    ]);
                    throw $e;
                } catch (\Stripe\Exception\RateLimitException $e) {
                    debug_log("Stripe Rate Limit Exception", ['message' => $e->getMessage()]);
                    throw $e;
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    debug_log("Stripe Invalid Request Exception", [
                        'message' => $e->getMessage(),
                        'param' => $e->getStripeParam()
                    ]);
                    throw $e;
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    debug_log("Stripe Authentication Exception", ['message' => $e->getMessage()]);
                    throw $e;
                } catch (\Stripe\Exception\ApiConnectionException $e) {
                    debug_log("Stripe API Connection Exception", ['message' => $e->getMessage()]);
                    throw $e;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    debug_log("Stripe API Error Exception", ['message' => $e->getMessage()]);
                    throw $e;
                }
                
                // Database operations
                try {
                    // 3. Create organization first using company name or user name
                    debug_log("Creating organization record");
                    $org_name = !empty($company_name) ? $company_name : $first_name . ' ' . $last_name . "'s Organization";
                    $org_description = "Organization for " . $first_name . " " . $last_name;
                    
                    $insert_org_query = "INSERT INTO organizations (name, description) VALUES (?, ?)";
                    $stmt = $conn->prepare($insert_org_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing organization insert", $conn->error);
                        throw new Exception("Database error preparing organization insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('ss', $org_name, $org_description);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting organization", $stmt->error);
                        throw new Exception("Error inserting organization: " . $stmt->error);
                    }
                    
                    // Get organization ID
                    $organization_id = $conn->insert_id;
                    debug_log("Database operation completed", [
                        'last_query' => $insert_org_query,
                        'affected_rows' => $conn->affected_rows,
                        'last_insert_id' => $organization_id
                    ]);
                    
                    // 4. Hash password
                    debug_log("Hashing password");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // 5. Insert user with organization_id
                    debug_log("Creating user record");
                    $insert_user_query = "INSERT INTO users (first_name, last_name, email, phone, password, user_type, email_verified, organization_id) 
                                         VALUES (?, ?, ?, ?, ?, 'consultant', 0, ?)";
                    $stmt = $conn->prepare($insert_user_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing user insert", $conn->error);
                        throw new Exception("Database error preparing user insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('sssssi', $first_name, $last_name, $email, $phone, $hashed_password, $organization_id);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting user", $stmt->error);
                        throw new Exception("Error inserting user: " . $stmt->error);
                    }
                    
                    // Get user ID
                    $user_id = $conn->insert_id;
                    debug_log("User created", ['id' => $user_id, 'email' => $email]);
                    
                    // 6. Insert consultant
                    debug_log("Creating consultant record");
                    $insert_consultant_query = "INSERT INTO consultants (user_id, membership_plan_id, company_name) 
                                               VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($insert_consultant_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing consultant insert", $conn->error);
                        throw new Exception("Database error preparing consultant insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('iis', $user_id, $membership_plan_id, $company_name);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting consultant", $stmt->error);
                        throw new Exception("Error inserting consultant: " . $stmt->error);
                    }
                    
                    debug_log("Consultant record created");
                    
                    // 7. Add an entry in consultant_profiles table with default values
                    debug_log("Creating consultant profile record");
                    $insert_profile_query = "INSERT INTO consultant_profiles (consultant_id) VALUES (?)";
                    $stmt = $conn->prepare($insert_profile_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing profile insert", $conn->error);
                        throw new Exception("Database error preparing profile insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('i', $user_id);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting consultant profile", $stmt->error);
                        throw new Exception("Error inserting consultant profile: " . $stmt->error);
                    }
                    
                    debug_log("Consultant profile created");
                    
                    // 8. Insert payment method
                    debug_log("Creating payment method record");
                    $insert_payment_query = "INSERT INTO payment_methods (user_id, method_type, provider, account_number, token, billing_address_line1, billing_address_line2, billing_city, billing_state, billing_postal_code, billing_country, is_default) 
                                            VALUES (?, 'credit_card', 'stripe', ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                    $last_four = $payment_method->card->last4 ?? substr($payment_method_id, -4); // Get last4 from Stripe or fallback
                    $stmt = $conn->prepare($insert_payment_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing payment method insert", $conn->error);
                        throw new Exception("Database error preparing payment method insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('issssssss', $user_id, $last_four, $stripe_customer->id, $address_line1, $address_line2, $city, $state, $postal_code, $country);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting payment method", $stmt->error);
                        throw new Exception("Error inserting payment method: " . $stmt->error);
                    }
                    
                    $payment_method_id_db = $conn->insert_id;
                    debug_log("Payment method record created", ['id' => $payment_method_id_db]);
                    
                    // 9. Insert subscription
                    debug_log("Creating subscription record");
                    $insert_subscription_query = "INSERT INTO subscriptions (user_id, membership_plan_id, payment_method_id, status, start_date, end_date, auto_renew) 
                                                 VALUES (?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 1)";
                    $stmt = $conn->prepare($insert_subscription_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing subscription insert", $conn->error);
                        throw new Exception("Database error preparing subscription insert: " . $conn->error);
                    }
                    
                    $stmt->bind_param('iii', $user_id, $membership_plan_id, $payment_method_id_db);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error inserting subscription", $stmt->error);
                        throw new Exception("Error inserting subscription: " . $stmt->error);
                    }
                    
                    $subscription_id = $conn->insert_id;
                    debug_log("Subscription record created", ['id' => $subscription_id]);
                    
                    // 10. Generate verification token
                    debug_log("Generating email verification token");
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    // Update user with verification token
                    $update_token_query = "UPDATE users SET email_verification_token = ?, email_verification_expires = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_token_query);
                    
                    if (!$stmt) {
                        debug_log("Database error preparing token update", $conn->error);
                        throw new Exception("Database error preparing token update: " . $conn->error);
                    }
                    
                    $stmt->bind_param('ssi', $token, $expires, $user_id);
                    $result = $stmt->execute();
                    
                    if (!$result) {
                        debug_log("Error updating verification token", $stmt->error);
                        throw new Exception("Error updating verification token: " . $stmt->error);
                    }
                    
                    debug_log("Email verification token set", ['expires' => $expires]);
                    
                } catch (Exception $e) {
                    debug_log("Error in database operations", $e->getMessage());
                    throw $e; // Re-throw to be caught by outer catch
                }
                
                // All operations successful, commit transaction
                debug_log("All operations successful, committing transaction");
                $conn->commit();
                
                // Send verification email
                try {
                    debug_log("Sending verification email");
                    $verification_link = "https://visafy.io/verify_email.php?token=" . $token;
                    $email_subject = "Verify Your Email Address";
                    $email_body = "Hi $first_name,\n\nPlease click the following link to verify your email address:\n$verification_link\n\nThis link will expire in 24 hours.\n\nThank you,\nThe Visafy Team";
                    
                    // Use your email function to send verification email
                    if (function_exists('send_email')) {
                        $email_result = send_email($email, $email_subject, $email_body);
                        debug_log("Verification email sent", ['result' => $email_result]);
                    } else {
                        debug_log("Warning: send_email function not found");
                    }
                } catch (Exception $e) {
                    debug_log("Error sending verification email", $e->getMessage());
                    // Don't throw here - registration completed successfully even if email fails
                }
                
                $success_message = "Registration successful! Please check your email to verify your account.";
                debug_log("Registration completed successfully");
                
                // Clear form data
                $first_name = $last_name = $email = $phone = $company_name = $address_line1 = $address_line2 = $city = $state = $postal_code = $country = '';
                
            } catch (\Stripe\Exception\CardException $e) {
                // Rollback transaction on error
                debug_log("Stripe card error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Payment error: " . $e->getMessage();
            } catch (\Stripe\Exception\RateLimitException $e) {
                debug_log("Stripe rate limit error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Too many requests to Stripe. Please try again later.";
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                debug_log("Stripe invalid request error, rolling back transaction", [
                    'message' => $e->getMessage(),
                    'param' => $e->getStripeParam() 
                ]);
                $conn->rollback();
                $error_message = "Invalid payment information: " . $e->getMessage();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                debug_log("Stripe authentication error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Authentication with Stripe failed. Please contact support.";
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                debug_log("Stripe API connection error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Network error. Please try again later.";
            } catch (\Stripe\Exception\ApiErrorException $e) {
                debug_log("Stripe API error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Payment error: " . $e->getMessage();
            } catch (Exception $e) {
                // Rollback transaction on error
                debug_log("General error, rolling back transaction", $e->getMessage());
                $conn->rollback();
                $error_message = "Registration failed: " . $e->getMessage();
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Add debugging information for JavaScript
debug_log("JavaScript variables", [
    'stripe_publishable_key_set' => !empty($stripe_publishable_key),
    'has_selected_plan' => !empty($selected_plan),
    'selected_plan_id' => $selected_plan_id
]);

// Function to generate Stripe client secret for 3DS authentication
function generate_setup_intent($email, $name, $phone) {
    global $stripe_secret_key;
    
    try {
        // Make sure Stripe is initialized with the API key
        \Stripe\Stripe::setApiKey($stripe_secret_key);
        
        // Create temporary customer for the setup intent
        $customer = \Stripe\Customer::create([
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'metadata' => [
                'temp_for_3ds' => 'true',
                'registration_flow' => 'consultant'
            ]
        ]);
        
        // Create a SetupIntent
        $intent = \Stripe\SetupIntent::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'usage' => 'off_session',
        ]);
        
        return [
            'success' => true,
            'clientSecret' => $intent->client_secret,
            'customer' => $customer->id
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Check if the request is for a setup intent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_setup_intent') {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    if (empty($email) || empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $result = generate_setup_intent($email, $name, $phone);
    echo json_encode($result);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?plan_id=' . $selected_plan_id; ?>" method="POST" id="registrationForm" enctype="multipart/form-data">
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
                            <input type="hidden" name="payment_method_id" id="payment_method_id" value="">
                            
                            <div class="terms-privacy">
                                <div class="checkbox-group">
                                    <input type="checkbox" name="terms_agree" id="terms_agree" required>
                                    <label for="terms_agree">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <a href="become-member.php" class="btn cancel-btn">Back to Plans</a>
                                <button type="submit" name="register_member" id="register_member_btn" class="btn submit-btn" value="1">Register Now</button>
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
// Add console debug function that also shows in the UI for better debugging
function debug(message, data) {
    // Create debug container if it doesn't exist
    let debugContainer = document.getElementById('js-debug-container');
    if (!debugContainer) {
        debugContainer = document.createElement('div');
        debugContainer.id = 'js-debug-container';
        debugContainer.style.margin = '20px 0';
        debugContainer.style.padding = '10px';
        debugContainer.style.backgroundColor = '#f8f9fa';
        debugContainer.style.border = '1px solid #ddd';
        debugContainer.style.borderRadius = '5px';
        
        // Add a title
        const title = document.createElement('h3');
        title.textContent = 'JavaScript Debug Log';
        title.style.marginTop = '0';
        title.style.color = '#721c24';
        debugContainer.appendChild(title);
        
        // Add to the page
        document.querySelector('.content').appendChild(debugContainer);
    }
    
    // Log to console
    if (data) {
        console.log(message, data);
    } else {
        console.log(message);
    }
    
    // Add to UI
    const debugEntry = document.createElement('div');
    debugEntry.className = 'debug-log';
    debugEntry.innerHTML = `<strong>JS DEBUG:</strong> ${message}`;
    
    if (data) {
        const pre = document.createElement('pre');
        pre.textContent = typeof data === 'object' ? JSON.stringify(data, null, 2) : data;
        debugEntry.appendChild(pre);
    }
    
    debugContainer.appendChild(debugEntry);
}

// Function to handle 3D Secure (3DS) authentication if needed
function handle3dsAuthentication(stripe, clientSecret, card, submitButton) {
    debug("Handling 3DS authentication with client secret");
    
    return stripe.confirmCardSetup(clientSecret, {
        payment_method: {
            card: card
        }
    }).then(function(result) {
        if (result.error) {
            debug("3DS authentication failed", result.error);
            throw result.error;
        } else {
            debug("3DS authentication succeeded", {
                setupIntent: result.setupIntent.id,
                status: result.setupIntent.status
            });
            return result.setupIntent;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if the form exists before doing anything else
    const registrationForm = document.getElementById('registrationForm');
    if (!registrationForm) {
        debug("CRITICAL ERROR: Registration form not found in the DOM");
        return;
    } else {
        debug("Registration form found with action:", registrationForm.action);
    }
    
    debug("Registration form found, proceeding with Stripe setup");
    
    // Get Stripe publishable key
    const stripePublishableKey = '<?php echo $stripe_publishable_key; ?>';
    debug("Stripe publishable key", stripePublishableKey ? "Found (starts with: " + stripePublishableKey.substring(0, 8) + "...)" : "Not found");
    
    // Only initialize Stripe if key exists
    if (!stripePublishableKey) {
        debug("ERROR: Stripe publishable key not available - stripe.js cannot be initialized");
        
        // Add visual warning to the payment section
        const cardElement = document.getElementById('card-element');
        if (cardElement) {
            cardElement.innerHTML = '<div style="color: #721c24; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">Stripe API key not configured. Please check server configuration.</div>';
        }
        return;
    }
    
    // Check for card element before initializing Stripe
    const cardElement = document.getElementById('card-element');
    if (!cardElement) {
        debug("ERROR: Card element not found - unable to initialize Stripe");
        return;
    }
    
    try {
        // Initialize Stripe using the publishable key
        debug("Initializing Stripe with publishable key");
        const stripe = Stripe(stripePublishableKey);
        const elements = stripe.elements();
        
        // Create card Element
        debug("Creating Stripe card element");
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
        
        // Mount the card element
        debug("Mounting card element to DOM");
        card.mount(cardElement);
        
        // Handle real-time validation errors
        card.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (displayError) {
                if (event.error) {
                    debug("Card validation error", event.error.message);
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            }
        });
        
        // Check if all required form elements exist
        const submitButton = document.getElementById('register_member_btn');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const hiddenInput = document.getElementById('payment_method_id');
        
        debug("Checking required form elements", {
            submitButton: !!submitButton,
            firstNameInput: !!firstNameInput,
            lastNameInput: !!lastNameInput,
            hiddenInput: !!hiddenInput
        });
        
        if (!submitButton || !firstNameInput || !lastNameInput || !hiddenInput) {
            debug("ERROR: Critical form elements missing");
            return;
        }
        
        debug("All required elements found, setting up form submission handler");
        
        // Handle form submission - directly attach to the form
        if (registrationForm) {
            // Add a global variable to track if the form is already being submitted
            let isSubmitting = false;
            
            registrationForm.addEventListener('submit', function(event) {
                debug("Form submission detected");
                
                // Prevent double submission
                if (isSubmitting) {
                    debug("Form already submitting, preventing double submission");
                    event.preventDefault();
                    return false;
                }
                
                event.preventDefault();
                isSubmitting = true;
                
                // Disable the submit button to prevent multiple submissions
                submitButton.disabled = true;
                submitButton.classList.add('disabled');
                submitButton.innerHTML = 'Processing Payment... <span class="processing-payment"><i class="fas fa-spinner fa-spin"></i></span>';
                
                const cardholderName = firstNameInput.value + ' ' + lastNameInput.value;
                
                // Get billing address details
                const addressLine1Input = document.getElementById('address_line1');
                const addressLine2Input = document.getElementById('address_line2');
                const cityInput = document.getElementById('city');
                const stateInput = document.getElementById('state');
                const postalCodeInput = document.getElementById('postal_code');
                const countryInput = document.getElementById('country');
                
                const addressLine1 = addressLine1Input ? addressLine1Input.value : '';
                const addressLine2 = addressLine2Input ? addressLine2Input.value : '';
                const city = cityInput ? cityInput.value : '';
                const state = stateInput ? stateInput.value : '';
                const postalCode = postalCodeInput ? postalCodeInput.value : '';
                const country = countryInput ? countryInput.value : '';
                
                debug("Getting card details and billing information");
                
                // For India or other countries that may require 3DS, we need to handle it differently
                const isIndiaCard = country === 'IN';
                
                // If it's an Indian card, we need to use SetupIntent with 3DS
                if (isIndiaCard) {
                    debug("Indian card detected - using 3DS flow");
                    
                    // First, create a setup intent on the server
                    const formData = new FormData();
                    formData.append('action', 'create_setup_intent');
                    formData.append('email', document.getElementById('email').value);
                    formData.append('name', cardholderName);
                    formData.append('phone', document.getElementById('phone').value);
                    
                    debug("Requesting setup intent");
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            debug("Error creating setup intent", data.error);
                            const errorElement = document.getElementById('card-errors');
                            if (errorElement) {
                                errorElement.textContent = data.error || 'An error occurred. Please try again.';
                            }
                            
                            // Re-enable the submit button
                            submitButton.disabled = false;
                            submitButton.classList.remove('disabled');
                            submitButton.innerHTML = 'Register Now';
                            return;
                        }
                        
                        debug("Setup intent created, handling 3DS authentication", {
                            clientSecret: data.clientSecret ? 'Present (hidden)' : 'Missing'
                        });
                        
                        // We have the client secret, now handle 3DS authentication
                        handle3dsAuthentication(stripe, data.clientSecret, card, submitButton)
                            .then(setupIntent => {
                                // 3DS authentication succeeded, now get the payment method ID
                                debug("3DS completed successfully, creating payment method", {
                                    setupIntentId: setupIntent.id
                                });
                                
                                // Get payment method ID from the SetupIntent
                                const paymentMethodId = setupIntent.payment_method;
                                
                                // Send the payment method ID to the server
                                hiddenInput.value = paymentMethodId;
                                document.getElementById('payment_method_id').value = paymentMethodId;
                                
                                // Add the customer ID from the setup intent
                                const customerIdInput = document.createElement('input');
                                customerIdInput.type = 'hidden';
                                customerIdInput.name = 'customer_id';
                                customerIdInput.value = data.customer;
                                registrationForm.appendChild(customerIdInput);
                                
                                debug("Form data being submitted with authenticated payment method", {
                                    paymentMethodId: paymentMethodId,
                                    customerIdFromSetupIntent: data.customer
                                });
                                
                                // Complete form submission
                                submitFormWithExtras();
                            })
                            .catch(error => {
                                debug("3DS authentication failed", error);
                                const errorElement = document.getElementById('card-errors');
                                if (errorElement) {
                                    errorElement.textContent = error.message || 'Authentication failed. Please try again.';
                                }
                                
                                // Re-enable the submit button
                                submitButton.disabled = false;
                                submitButton.classList.remove('disabled');
                                submitButton.innerHTML = 'Register Now';
                            });
                    })
                    .catch(error => {
                        debug("Error in fetch request", error);
                        const errorElement = document.getElementById('card-errors');
                        if (errorElement) {
                            errorElement.textContent = 'Network error. Please try again.';
                        }
                        
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.classList.remove('disabled');
                        submitButton.innerHTML = 'Register Now';
                    });
                } else {
                    // For non-Indian cards, proceed with the regular flow
                    debug("Non-Indian card - using standard flow");
                    
                    // Create a payment method directly
                    stripe.createPaymentMethod({
                        type: 'card',
                        card: card,
                        billing_details: {
                            name: cardholderName,
                            address: {
                                line1: addressLine1,
                                line2: addressLine2,
                                city: city,
                                state: state,
                                postal_code: postalCode,
                                country: country
                            }
                        }
                    }).then(function(result) {
                        const errorElement = document.getElementById('card-errors');
                        
                        if (result.error) {
                            debug("Stripe payment method creation failed:", result.error.message);
                            // Inform the user if there was an error
                            if (errorElement) {
                                errorElement.textContent = result.error.message;
                            }
                            
                            // Re-enable the submit button
                            submitButton.disabled = false;
                            submitButton.classList.remove('disabled');
                            submitButton.innerHTML = 'Register Now';
                        } else {
                            debug("Stripe payment method created successfully:", {
                                id: result.paymentMethod.id,
                                type: result.paymentMethod.type,
                                card: {
                                    brand: result.paymentMethod.card.brand,
                                    last4: result.paymentMethod.card.last4,
                                    exp_month: result.paymentMethod.card.exp_month,
                                    exp_year: result.paymentMethod.card.exp_year
                                }
                            });
                            
                            // Send the payment method ID to the server - make sure to update BOTH the hidden inputs
                            hiddenInput.value = result.paymentMethod.id;
                            document.getElementById('payment_method_id').value = result.paymentMethod.id;
                            
                            // Submit the form
                            debug("Form data being submitted", {
                                paymentMethodId: hiddenInput.value,
                                membershipPlanId: document.getElementById('membership_plan_id').value,
                                firstName: firstNameInput.value,
                                email: document.getElementById('email').value
                            });
                            
                            submitFormWithExtras();
                        }
                    }).catch(function(error) {
                        debug("Unexpected Stripe error:", error.message);
                        
                        const errorElement = document.getElementById('card-errors');
                        if (errorElement) {
                            errorElement.textContent = 'An error occurred with the payment processor. Please try again later.';
                        }
                        
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.classList.remove('disabled');
                        submitButton.innerHTML = 'Register Now';
                    });
                }
                
                // Function to submit the form with additional hidden fields
                function submitFormWithExtras() {
                    try {
                        debug("Submitting form now...");
                        // Before submitting the form, ensure the register_member field is included
                        if (!document.querySelector('input[name="register_member"]')) {
                            // Create a hidden input for register_member if it doesn't exist
                            const hiddenRegisterField = document.createElement('input');
                            hiddenRegisterField.type = 'hidden';
                            hiddenRegisterField.name = 'register_member';
                            hiddenRegisterField.value = '1';
                            registrationForm.appendChild(hiddenRegisterField);
                            debug("Added missing register_member field to form");
                        } else {
                            debug("register_member field already exists");
                        }
                        
                        // Make sure submit button gets included in form data too
                        const submitBtn = document.getElementById('register_member_btn');
                        if (submitBtn) {
                            // Create a copy of the submit button as a hidden field
                            // This ensures the button's name/value is included in the form data
                            const hiddenBtnField = document.createElement('input');
                            hiddenBtnField.type = 'hidden';
                            hiddenBtnField.name = submitBtn.name;
                            hiddenBtnField.value = submitBtn.value || '1';
                            registrationForm.appendChild(hiddenBtnField);
                            debug("Added hidden copy of submit button");
                        }
                        
                        // Add a debug field to track submission
                        const debugField = document.createElement('input');
                        debugField.type = 'hidden';
                        debugField.name = 'js_submitted';
                        debugField.value = 'true';
                        registrationForm.appendChild(debugField);
                        
                        registrationForm.submit();
                        debug("Form submit called successfully");
                    } catch (err) {
                        debug("Error during form submission:", err.message);
                        isSubmitting = false;
                        
                        // Re-enable the submit button
                        submitButton.disabled = false;
                        submitButton.classList.remove('disabled');
                        submitButton.innerHTML = 'Register Now';
                    }
                }
            });
            debug("Form submission handler attached successfully");
        } else {
            debug("Registration form disappeared after initial check");
        }
    } catch (error) {
        debug("Error during Stripe setup:", error);
    }
});

// Function to update selected plan
function updateSelectedPlan(planId) {
    const planIdInput = document.getElementById('membership_plan_id');
    if (planIdInput) {
        planIdInput.value = planId;
    }
    
    // Redirect to same page with plan_id parameter to refresh the view
    window.location.href = 'consultant-registration.php?plan_id=' + planId;
}
</script>

<?php
// End output buffering and send content to browser
ob_end_flush();
?>

<?php require_once 'includes/footer.php'; ?>
