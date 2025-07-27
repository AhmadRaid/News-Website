<?php
session_start(); // Start the session to manage user login status

// --- Database Configuration ---
$servername = "localhost";
$username_db = "root"; // Your database username
$password_db = "";     // Your database password
$dbname = "news_db";  // Your database name

// Create database connection
// Assuming your MySQL server is running on default port or 8111 for XAMPP
$conn = new mysqli("localhost:8111", $username_db, $password_db, $dbname);

// Check database connection
if ($conn->connect_error) {
    // Log the error for debugging, but don't show sensitive details to the user
    error_log("Database connection failed: " . $conn->connect_error);
    die("<div style='text-align: center; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'><h1>Error connecting to the database. Please try again later.</h1></div>");
}

$error_message = ''; // Initialize error message variable
$success_message = ''; // Initialize success message variable

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_identifier = trim($_POST['username']); // We'll now treat this as the identifier (email or username)
    $password = trim($_POST['password']);

    // Basic server-side validation
    if (empty($input_identifier) || empty($password)) {
        $error_message = "Please enter both username/email and password.";
    } else {
        // --- CRUCIAL CHANGE HERE: Search by 'email' column instead of 'username' ---
        // If you want to allow login by either username OR email, you can adjust the WHERE clause:
        // $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        // $stmt->bind_param("ss", $input_identifier, $input_identifier);
        
        // For now, let's assume you want to log in using the email as the identifier
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE email = ? LIMIT 1");
        
        // Bind parameters: "s" for string
        $stmt->bind_param("s", $input_identifier);
        
        // Execute the statement
        $stmt->execute();
        
        // Get the result
        $result = $stmt->get_result();
        
        // Check if a user with that identifier exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the hashed password and check if the role is 'admin'
            if (password_verify($password, $user['password']) && $user['role'] === 'admin') {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username']; // Store the actual username from DB
                $_SESSION['role'] = $user['role']; // Store the role in the session
                
                $success_message = "Login successful! Redirecting to admin panel...";
                
                // Redirect to admin dashboard after a short delay (for user to see success message)
                header("Refresh: 1.5; URL=get_articles.php"); // Adjust this path if your admin dashboard is elsewhere
                exit();
            } else {
                // Password incorrect or not an admin
                $error_message = "Invalid username or password, or you do not have admin privileges.";
            }
        } else {
            // Identifier (email) not found
            $error_message = "Invalid username or password.";
        }
        
        // Close the statement
        $stmt->close();
    }
}

// Check if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    // Redirect immediately if already logged in as admin
    header("Location: admin/index.php");
    exit();
}

// Close the database connection (only if it was opened and not already closed by exit())
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Global News Network</title>
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        rel="stylesheet"
    />
    <style>
        /* Your CSS styles here (same as before) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Arial", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 3rem;
            color: #667eea;
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
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            color: #1565c0;
            text-align: left;
        }

        .demo-info strong {
            display: block;
            margin-bottom: 8px;
        }

        .demo-info code {
            background: #bbdefb;
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-btn {
            width: 100%;
            background: #667eea;
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
            background: #5a67d8;
        }

        .login-btn:disabled {
            background: #ccc;
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

        .back-link {
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #5a67d8;
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
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
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
            <i class="fas fa-shield-alt"></i>
            <h1>Admin Login</h1>
            <p>Global News Network Admin Panel</p>
        </div>

        <div class="demo-info">
            <strong>Demo Admin Credentials:</strong>
            Username (or Email): <code>admin@example.com</code><br />
            Password: <code>123</code> (Remember this is hashed in the database)
        </div>

        <div id="alert-container">
            <?php
            // Display error or success messages from PHP
            if (!empty($error_message)) {
                echo '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error_message) . '</div>';
            } elseif (!empty($success_message)) {
                echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($success_message) . '</div>';
            }
            ?>
        </div>

        <form id="loginForm" method="POST" action="login.php">
            <div class="form-group">
                <label for="username"> <i class="fas fa-user"></i> Username or Email </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter admin username or email (e.g., admin@example.com)"
                    required
                />
            </div>

            <div class="form-group">
                <label for="password"> <i class="fas fa-lock"></i> Password </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter admin password"
                    required
                />
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
            </button>

            <div class="loading" id="loading" style="display: none;">
                <i class="fas fa-spinner"></i> Logging in...
            </div>
        </form>

        <div class="back-link">
            <a href="get_articles.php">
                <i class="fas fa-arrow-left"></i> Back to Website
            </a>
            <a href="user-login.php">User Login</a>
        </div>
    </div>

    <script>
        // Client-side JavaScript for UI interaction (e.g., clearing password on error, focusing)
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on username field
            document.getElementById("username").focus();

            // Allow Enter key to submit from password field
            document.getElementById("password").addEventListener("keypress", function (e) {
                if (e.key === "Enter") {
                    document.getElementById("loginForm").submit(); // Submit the form
                }
            });

            // If there's an error message, clear password field and focus
            <?php if (!empty($error_message)): ?>
                document.getElementById("password").value = "";
                document.getElementById("password").focus();
            <?php endif; ?>

            console.log("Admin login page loaded");
        });
    </script>
</body>
</html>