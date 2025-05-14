<?php
// Include session management
require_once "includes/session.php";

// Check if the user is already logged in, if yes then redirect to dashboard
if(is_logged_in()) {
    header("location: dashboard.php");
    exit;
}

// Include config file
require_once "config/db_connect.php";

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Google OAuth Configuration
$google_client_id = getenv('GOOGLE_CLIENT_ID');
$google_client_secret = getenv('GOOGLE_CLIENT_SECRET');
$google_redirect_url = "https://visafy.io/google_callback.php";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT id, first_name, last_name, email, password, user_type, email_verified, status FROM users WHERE email = ? AND auth_provider = 'local'";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $first_name, $last_name, $email, $hashed_password, $user_type, $email_verified, $status);
                    if(mysqli_stmt_fetch($stmt)) {
                        if(password_verify($password, $hashed_password)) {
                            // Check if email is verified
                            if($email_verified != 1) {
                                $login_err = "Please verify your email address to login. <a href='resend_verification.php'>Resend verification email</a>";
                            } 
                            // Check if account is active
                            else if($status != "active") {
                                $login_err = "Your account is suspended. Please contact support.";
                            }
                            else {
                                // Password is correct, session is already started in the included session.php
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["email"] = $email;  
                                $_SESSION["first_name"] = $first_name;
                                $_SESSION["last_name"] = $last_name;
                                $_SESSION["user_type"] = $user_type;
                                $_SESSION["last_activity"] = time();
                                $_SESSION["created_at"] = time();
                                
                                // Redirect user to dashboard page
                                header("location: dashboard.php");
                                exit;
                            }
                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}

// Set page title and include header
$page_title = "Login - Visafy";
include('includes/header.php');
?>

<div class="wrapper">
    <h2 class="text-center mb-4">Welcome Back</h2>
    
    <?php 
    if(!empty($login_err)){
        echo '<div class="alert alert-danger">' . $login_err . '</div>';
    }        
    ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
            <span class="invalid-feedback"><?php echo $email_err; ?></span>
        </div>    
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
            <span class="invalid-feedback"><?php echo $password_err; ?></span>
        </div>
        <div class="form-group">
            <a href="forgot-password.php" class="text-decoration-none float-right">Forgot Password?</a>
        </div>
        <div class="form-group">
            <input type="submit" class="btn btn-primary btn-block" value="Login">
        </div>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="form-group">
            <a href="<?php echo 'https://accounts.google.com/o/oauth2/v2/auth?scope=email%20profile&redirect_uri='.$google_redirect_url.'&response_type=code&client_id='.$google_client_id; ?>" class="btn btn-google btn-block">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google logo"> 
                Sign in with Google
            </a>
        </div>
    </form>
    
    <div class="form-footer">
        Don't have an account? <a href="register.php">Sign up now</a>
    </div>
</div>

<?php include('includes/footer.php'); ?>
