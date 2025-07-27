<?php
// db_connect.php (or include if in a separate file)

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

// Set character set to ensure correct handling of Arabic and other languages
$conn->set_charset("utf8mb4");

// Variables to store alert messages
$alert_message = '';
$alert_type = ''; // 'success' or 'error'

// Check if the request is POST (i.e., the form was submitted)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Extract data from $_POST - only the fields you're sending from the form
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    // $confirmPassword is still received but not validated in PHP
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : ''; 
    // $agreeTerms is still received but not validated in PHP
    $agreeTerms = isset($_POST['agreeTerms']);
    
    // 'role' field is a hidden input in your HTML, so it will always be sent
    $role = isset($_POST['role']) ? $_POST['role'] : 'user'; // Default to 'user' if not sent for any reason

    // Server-side validation (simplified as per request)
    if (empty($username) || empty($email) || empty($password)) {
        $alert_message = 'Please fill in all required fields.';
        $alert_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = 'Invalid email format.';
        $alert_type = 'error';
    } else {
        // Hash the password (always do this for security)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $alert_message = 'Username or email already exists. Please choose different ones.';
                $alert_type = 'error';
            } else {
                $stmt->close(); // Close the first statement before opening a new one

                // Insert new user data into the database
                // Ensure your 'users' table has 'user_id' (AUTO_INCREMENT PRIMARY KEY), 'username', 'email', 'password', 'role' columns
                $sql_insert_user = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_user);

                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $role);

                    if ($stmt_insert->execute()) {
                        $alert_message = 'Account created successfully! You can now log in.';
                        $alert_type = 'success';
                        // Optionally, clear form fields after successful registration
                        $username = $email = $password = $confirmPassword = '';
                        $agreeTerms = false; // Reset checkbox state
                    } else {
                        error_log("Error inserting user: " . $stmt_insert->error);
                        $alert_message = 'Failed to register user. Please try again. Database error: ' . $stmt_insert->error; // Add error for debugging
                        $alert_type = 'error';
                    }
                    $stmt_insert->close();
                } else {
                    error_log("Error preparing user insert query: " . $conn->error);
                    $alert_message = 'An internal error occurred during registration. Please try again later. Query preparation error: ' . $conn->error; // Add error for debugging
                    $alert_type = 'error';
                }
            }
        } else {
            error_log("Error preparing username/email check query: " . $conn->error);
            $alert_message = 'An internal database error occurred.';
            $alert_type = 'error';
        }
    }
}

// Close database connection at the end of the script
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Global News Network</title>
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
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .register-header {
            margin-bottom: 30px;
        }
        
        .register-header i {
            font-size: 3rem;
            color: #27ae60;
            margin-bottom: 15px;
        }
        
        .register-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .register-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        /* Remove input validation styles for ease of use */
        .form-group input:invalid {
            border-color: #e1e5e9; /* Set default border color instead of red */
        }
        
        .form-group input:valid {
            border-color: #e1e5e9; /* Set default border color instead of green */
        }
        
        .password-strength {
            display: none; /* Hide password strength indicator */
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: left;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            margin-top: 2px;
        }
        
        .register-btn {
            width: 100%;
            background: #27ae60;
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
        
        .register-btn:hover {
            background: #229954;
        }
        
        .register-btn:disabled {
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
            color: #27ae60;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        
        .links a:hover {
            color: #229954;
            text-decoration: underline;
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
        
        @media (max-width: 600px) {
            .register-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .register-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h1>Create Your Account</h1>
            <p>Join the Global News Network and stay informed</p>
        </div>

        <div id="alert-container">
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?>">
                    <i class="fas <?php echo ($alert_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'); ?>"></i> 
                    <?php echo $alert_message; ?>
                </div>
            <?php endif; ?>
        </div>

        <form id="registerForm" method="POST" action="user-register.php"> 
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username <span class="required">*</span>
                </label>
                <input type="text" id="username" name="username" placeholder="Choose a unique username" 
                        required minlength="1" maxlength="20" value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address <span class="required">*</span>
                </label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password <span class="required">*</span>
                    </label>
                    <input type="password" id="password" name="password" placeholder="Create your password" 
                            required minlength="1">
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-lock"></i> Confirm Password <span class="required">*</span>
                    </label>
                    <input type="password" id="confirmPassword" name="confirmPassword" 
                            placeholder="Confirm your password" required minlength="1">
                </div>
            </div>

            <input type="hidden" id="role" name="role" value="user">

            <button type="submit" class="register-btn" id="registerBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
            
            <div class="loading" id="loading" style="display:none;"> 
                <i class="fas fa-spinner"></i> Creating your account...
            </div>
        </form>

        <div class="links">
            <a href="user-login.php">Already have an account? Log In</a>
            <a href="get_articles.php">Back to Website</a>
        </div>
    </div>

    <script>
        // This part of JavaScript handles client-side interaction, such as password matching
        // and no longer handles data storage in localStorage.

        // Client-side password matching check
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            // Change border color only if there's input
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    this.style.borderColor = '#27ae60'; // Match color
                } else {
                    this.style.borderColor = '#e74c3c'; // Mismatch color
                }
            } else {
                this.style.borderColor = '#e1e5e9'; // Default color if empty
            }
        });

        // Handle form submission (via PHP)
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            // No e.preventDefault() here because we want the form to submit naturally to PHP
            const registerBtn = document.getElementById('registerBtn');
            const loading = document.getElementById('loading');
            
            // Simple client-side password matching check before submitting to the server
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                e.preventDefault(); // Prevent form submission if passwords don't match
                return;
            }

            // Show loading indicator immediately upon form submission
            registerBtn.disabled = true;
            loading.style.display = 'block';
        });
        
        // Function to display alerts (for client-side alerts, or if PHP displays dynamic alerts)
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            
            const icon = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            alertDiv.innerHTML = `<i class="${icon}"></i> ${message}`;
            
            alertContainer.innerHTML = ''; // Clear previous alerts
            alertContainer.appendChild(alertDiv);
            
            // Auto-remove after 8 seconds for error messages
            if (type === 'error') {
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 8000);
            }
        }
        
        // Display Terms of Service
        function showTerms() {
            alert('Terms of Service\n\n1. You must be 18 years or older to use this service.\n2. Do not post spam or inappropriate content.\n3. Respect other users and their opinions.\n4. We reserve the right to suspend accounts for violations.\n5. Content you post may be moderated.\n\n(This is an illustrative example - full terms would be on a separate page)');
        }
        
        // Display Privacy Policy
        function showPrivacy() {
            alert('Privacy Policy\n\n1. We collect basic account information for registration.\n2. We do not sell your personal data to third parties.\n3. We use cookies for authentication and user experience.\n4. You can delete your account and data at any time.\n5. We may send newsletters if you opt-in.\n\n(This is an illustrative example - full privacy policy would be on a separate page)');
        }
        
        // Auto-focus on the username field
        document.getElementById('username').focus();
        
        console.log('User registration page loaded');
    </script>
</body>
</html>