<?php
session_start(); // Start the session to store user data

// db_connect.php (or include if in a separate file)
// This part handles the database connection
$servername = "localhost:8111"; // Make sure this setting is correct for XAMPP/WAMP
$username = "root";             // Your database username
$password = "";                 // Your database password (empty if no password)
$dbname = "news_db";           // Your database name

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>");
}

// Set character set to ensure correct handling of languages
$conn->set_charset("utf8mb4");

// Variables to store alert messages
$alert_message = '';
$alert_type = ''; // 'success' or 'error'

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirect to home page if already logged in
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : ''; // Can be username or email
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $rememberMe = isset($_POST['rememberMe']);

    // Server-side validation
    if (empty($identifier) || empty($password)) {
        $alert_message = 'Please enter both username/email and password.';
        $alert_type = 'error';
    } else {
        // Prepare a SQL statement to prevent SQL injection
        // We check for both username and email in the same query
        // *** MODIFICATION HERE: Removed 'full_name' from SELECT query ***
        $stmt = $conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify the hashed password
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    // Store user data in session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    // *** MODIFICATION HERE: Remove full_name from session or set it based on username ***
                    // Since you don't have full_name, you can either remove this line:
                    // $_SESSION['full_name'] = $user['full_name']; // REMOVE THIS LINE
                    // Or, if you want something similar, use the username:
                    $_SESSION['full_name'] = $user['username']; // Using username as a substitute for display
                    $_SESSION['role'] = $user['role']; // Store user role if available

                    // If "Remember me" is checked, set a cookie
                    if ($rememberMe) {
                        // Set cookie for 30 days (adjust as needed)
                        setcookie('remember_user_id', $user['user_id'], time() + (86400 * 30), "/"); // 86400 = 1 day
                        setcookie('remember_username', $user['username'], time() + (86400 * 30), "/");
                    } else {
                        // Clear remember me cookies if they exist (in case user unchecks it)
                        setcookie('remember_user_id', '', time() - 3600, "/");
                        setcookie('remember_username', '', time() - 3600, "/");
                    }

                    // *** MODIFICATION HERE: Adjust alert message for full_name ***
                    // Use 'username' since 'full_name' doesn't exist
                    $alert_message = 'Login successful! Welcome back, ' . htmlspecialchars($user['username']) . '.';
                    $alert_type = 'success';

                    // Redirect to the home page after a short delay
                    echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'index.php';
                                }, 1500);
                              </script>";
                } else {
                    // Incorrect password
                    $alert_message = 'Invalid username/email or password. Please try again.';
                    $alert_type = 'error';
                }
            } else {
                // User not found
                $alert_message = 'Invalid username/email or password. Please try again.';
                $alert_type = 'error';
            }
            $stmt->close();
        } else {
            error_log("Error preparing login query: " . $conn->error);
            $alert_message = 'An internal error occurred. Please try again later.';
            $alert_type = 'error';
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Your CSS styles here (unchanged from original) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 15px;
        }

        .login-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .demo-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            color: #2c3e50;
            text-align: left;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }

        .demo-info code {
            background: #d6eaf8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        .login-btn {
            width: 100%;
            background: #3498db;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            background: #2980b9;
        }

        .login-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }

        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }

        .links {
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            color: #666;
            font-size: 0.85rem;
        }

        .loading {
            display: none;
            margin-top: 10px;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-user"></i>
            <h1>Welcome Back</h1>
            <p>Sign in to your Global News Network account</p>
        </div>

        <div class="demo-info">
            <strong>Demo User Accounts:</strong>
            Username: <code>user1</code> | Password: <code>password123</code><br>
            Username: <code>john_doe</code> | Password: <code>mypass456</code>
        </div>

        <div id="alert-container">
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?>">
                    <i class="fas <?php echo ($alert_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'); ?>"></i>
                    <?php echo $alert_message; ?>
                </div>
            <?php endif; ?>
        </div>

        <form id="loginForm" method="POST" action="user-login.php">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Remember me for 30 days</label>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="loading" id="loading">
                <i class="fas fa-spinner"></i> Signing you in...
            </div>
        </form>

        <div class="links">
            <a href="#" onclick="forgotPassword()">Forgot Password?</a>
            <a href="user-register.php">Create Account</a>
        </div>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="links">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Website
            </a>
            <a href="./admin/login.php">Admin Login</a>
        </div>
    </div>

    <script>
        // This JavaScript will primarily handle client-side UI feedback
        // The actual login logic is now handled by PHP

        // Auto-focus on username field
        document.getElementById('username').focus();

        // Handle form submission to show loading indicator
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');

            // Basic client-side validation for empty fields (redundant with PHP but good for UX)
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                // PHP will also catch this, but provide immediate feedback
                // Don't prevent default, let PHP handle the alert
            } else {
                loginBtn.disabled = true;
                loading.style.display = 'block';
                // No e.preventDefault() here; let the form submit normally to PHP
            }
        });

        // Function to display alerts (for client-side alerts, or if PHP dynamically displays alerts)
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            // Clear previous alerts if any were left by PHP or previous JS calls
            alertContainer.innerHTML = '';

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;

            const icon = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            alertDiv.innerHTML = `<i class="${icon}"></i> ${message}`;

            alertContainer.appendChild(alertDiv);

            // Auto-remove after 5 seconds for error messages
            if (type === 'error') {
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 5000);
            }
        }

        // Forgot password function (client-side only for demo)
        function forgotPassword() {
            const email = prompt('Please enter your email address:');
            if (email && email.includes('@')) {
                alert('Password reset instructions have been sent to ' + email + '\n\n(This is a demo - no actual email was sent)');
            } else if (email) {
                alert('Please enter a valid email address');
            }
        }

        console.log('User login page loaded with PHP backend');
    </script>
</body>
</html>