<?php

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>");
} else {
    $conn->set_charset("utf8mb4");

    $article = null;
    $comments = [];
    $article_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Get article ID from URL

    if ($article_id > 0) {
        // --- Fetch Article Details ---
        // Removed `u.username AS author_name` from SELECT statement and the JOIN
        $sql_article = "SELECT
                            a.article_id,
                            a.title,
                            a.content,
                            a.published_date,
                            a.image_url,
                            c.category_name
                        FROM
                            articles a
                        LEFT JOIN
                            categories c ON a.category_id = c.category_id
                        WHERE
                            a.article_id = ?";

        $stmt = $conn->prepare($sql_article);
        if ($stmt) {
            $stmt->bind_param("i", $article_id);
            $stmt->execute();
            $result_article = $stmt->get_result();
            if ($result_article->num_rows > 0) {
                $article = $result_article->fetch_assoc();
            } else {
                // Article not found
                header("Location: index.php"); // Redirect to home or 404 page
                exit();
            }
            $stmt->close();
        } else {
            error_log("Error preparing article query: " . $conn->error);
        }

        // --- Handle Comment Submission ---
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
            $comment_text = trim($_POST['comment_text']);
            $user_id = 2; // Default to null for non-logged-in users

            // Check if a user is logged in (using cookie for userSession)
            if (isset($_COOKIE['userSession'])) {
                $userSession = json_decode($_COOKIE['userSession'], true);
                if ($userSession && isset($userSession['user_id'])) {
                    $user_id = $userSession['user_id'];
                }
            }
            // Fallback for PHP session if used (e.g., if you switched from localStorage/cookie to server-side sessions)
            elseif (isset($_SESSION['user_id'])) {
                $user_id = 2;
            }

            if (!empty($comment_text) && $article_id > 0) {
                // Modified INSERT statement: removed `author_name` field and its corresponding value
                $sql_insert_comment = "INSERT INTO comments (article_id, user_id, comment_text) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_comment);
                if ($stmt_insert) {
                    // Note the binding parameters are now "iis" (integer, integer, string)
                    $stmt_insert->bind_param("iis", $article_id, $user_id, $comment_text);

                    if ($stmt_insert->execute()) {
                        // Comment added successfully, redirect to prevent resubmission on refresh
                        header("Location: article.php?id=" . $article_id . "#comments");
                        exit();
                    } else {
                        error_log("Error inserting comment: " . $stmt_insert->error);
                        $comment_error = "Failed to post comment. Please try again.";
                    }
                    $stmt_insert->close();
                } else {
                    error_log("Error preparing comment insert query: " . $conn->error);
                    $comment_error = "An internal error occurred. Please try again later.";
                }
            } else {
                $comment_error = "Comment content cannot be empty.";
            }
        }

        // --- Fetch Comments for the Article ---
        // Modified to display username from 'users' table or 'Anonymous' if no user_id
        $sql_comments = "SELECT
                            com.comment_id,
                            com.comment_text,
                            com.timestamp,
                            IFNULL(u.username, 'Anonymous') AS comment_author_name
                        FROM
                            comments com
                        LEFT JOIN
                            users u ON com.user_id = u.user_id
                        WHERE
                            com.article_id = ?
                        ORDER BY
                            com.timestamp DESC";
        $stmt_comments = $conn->prepare($sql_comments);
        if ($stmt_comments) {
            $stmt_comments->bind_param("i", $article_id);
            $stmt_comments->execute();
            $result_comments = $stmt_comments->get_result();
            if ($result_comments->num_rows > 0) {
                while($row = $result_comments->fetch_assoc()) {
                    $comments[] = $row;
                }
            }
            $stmt_comments->close();
        } else {
            error_log("Error fetching comments: " . $conn->error);
        }
    } else {
        header("Location: index.php"); // No article ID provided, redirect to home
        exit();
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title'] ?? 'Article Not Found'); ?> - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Add specific styles for article.php if needed */
        .article-detail-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .article-detail-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .article-detail-header h1 {
            color: var(--text-color-primary);
            margin-bottom: 10px;
        }

        .article-meta-detail {
            font-size: 0.9em;
            color: var(--text-color-secondary);
            margin-bottom: 20px;
        }

        .article-meta-detail span {
            margin-right: 15px;
        }

        .article-content-full {
            color: var(--text-color-primary);
            line-height: 1.8;
            font-size: 1.1em;
            text-align: justify;
        }

        .article-content-full p {
            margin-bottom: 1em;
        }

        .comments-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .comments-section h2 {
            color: var(--text-color-primary);
            margin-bottom: 20px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--input-bg);
            color: var(--text-color-primary);
            resize: vertical;
        }

        /* Removed author_name input styling since it's removed from HTML */

        .comment-form button {
            background-color: var(--accent-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .comment-form button:hover {
            background-color: var(--accent-color-hover);
        }

        .comment-list {
            margin-top: 20px;
        }

        .comment-item {
            background-color: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .comment-item h4 {
            margin-top: 0;
            margin-bottom: 5px;
            color: var(--text-color-primary);
        }

        .comment-item .comment-date {
            font-size: 0.8em;
            color: var(--text-color-secondary);
            margin-bottom: 10px;
            display: block;
        }

        .comment-item p {
            color: var(--text-color-primary);
            line-height: 1.6;
        }
        .error-message {
            color: #d9534f;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: <?php echo isset($comment_error) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <i class="fas fa-globe"></i>
                    <a href="index.php">Global News Network</a>
                </div>
                <div class="header-controls">
                    <div class="search-container">
                        <input type="text" placeholder="Search news articles..." id="searchInput">
                        <button type="submit" onclick="searchArticles()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <div class="auth-buttons" id="authButtons">
                        </div>
                </div>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="category.php?cat_id=1">Politics</a></li>
                    <li><a href="category.php?cat_id=2">Technology</a></li>
                    <li><a href="category.php?cat_id=3">Sports</a></li>
                    <li><a href="category.php?cat_id=4">Entertainment</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li id="adminNavLink" style="display: none;">
                        <a href="admin-login.php" class="admin-nav-link">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-container">
        <main class="content-area">
            <?php if ($article): ?>
                <article class="article-detail-container">
                    <div class="article-detail-header">
                        <h1><?php echo htmlspecialchars($article['title']); ?></h1>
                        <div class="article-meta-detail">
                            <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($article['category_name'] ?: 'General'); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date("F j, Y, g:i a", strtotime($article['published_date'])); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($article['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                    <?php endif; ?>
                    <div class="article-content-full">
                        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                    </div>
                </article>

                <section class="comments-section" id="comments">
                    <h2>Comments</h2>

                    <?php if (isset($comment_error)): ?>
                        <div class="error-message" style="display: block;"><?php echo htmlspecialchars($comment_error); ?></div>
                    <?php endif; ?>

                    <form class="comment-form" method="POST" action="article.php?id=<?php echo $article_id; ?>">
                        <textarea name="comment_text" placeholder="Write your comment here..." rows="5" required></textarea>
                        <button type="submit" name="submit_comment">Post Comment</button>
                    </form>

                    <div class="comment-list">
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <h4><?php echo htmlspecialchars($comment['comment_author_name']); ?></h4>
                                    <span class="comment-date"><?php echo date("F j, Y, g:i a", strtotime($comment['timestamp'])); ?></span>
                                    <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No comments yet. Be the first to comment!</p>
                        <?php endif; ?>
                    </div>
                </section>

            <?php else: ?>
                <div class="article-detail-container">
                    <h1>Article Not Found</h1>
                    <p>The article you are looking for does not exist or has been removed.</p>
                    <p><a href="index.php">Go back to Homepage</a></p>
                </div>
            <?php endif; ?>
        </main>

        <aside class="sidebar">
            <section class="sidebar-section">
                <h3 class="section-title">Trending Now</h3>
                <div class="trending-list">
                    <p>Trending articles will appear here.</p>
                </div>
            </section>

            <section class="sidebar-section" id="userProfileSection" style="display: none;">
                <h3 class="section-title">My Profile</h3>
                <div class="user-profile-card">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-info">
                        <h4 id="userDisplayName">User Name</h4>
                        <p id="userEmail">user@example.com</p>
                        <div class="user-actions">
                            <button onclick="viewProfile()" class="profile-btn">View Profile</button>
                            <button onclick="userLogout()" class="logout-btn">Sign Out</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ad-section">
                <h3>Advertisement</h3>
                <p>Your ad could be here!</p>
                <i class="fas fa-ad" style="font-size: 3rem; margin: 1rem 0;"></i>
                <p>Contact us for advertising opportunities</p>
            </section>

            <section class="sidebar-section">
                <h3 class="section-title">Quick Subscribe</h3>
                <p>Get daily news updates delivered to your inbox.</p>
                <div style="margin-top: 1rem;">
                    <form onsubmit="quickSubscribe(event)">
                        <input type="email" placeholder="Enter your email" class="sidebar-input" required>
                        <button type="submit" class="sidebar-btn">Subscribe</button>
                    </form>
                </div>
            </section>
        </aside>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Global News Network</h3>
                    <p>Your trusted source for breaking news, in-depth analysis, and comprehensive coverage of global events.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="category.php?cat_id=1">Politics</a></li>
                        <li><a href="category.php?cat_id=2">Technology</a></li>
                        <li><a href="category.php?cat_id=3">Sports</a></li>
                        <li><a href="category.php?cat_id=4">Entertainment</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>About</h3>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#careers">Careers</a></li>
                        <li><a href="#privacy">Privacy Policy</a></li>
                        <li><a href="#terms">Terms of Service</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-envelope"></i> news@globalnews.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 News Street, Media City</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Global News Network. All rights reserved. | Designed with ❤️ for informed citizens worldwide.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // This JavaScript section should ideally be in a separate script.js file
        // but is included here for completeness as per your original code structure.

        // Check user login status and update navigation
        function checkUserLoginStatus() {
            const userSession = localStorage.getItem('userSession') || sessionStorage.getItem('userSession');
            const adminSession = localStorage.getItem('adminLoggedIn');
            const authButtons = document.getElementById('authButtons');
            const userProfileSection = document.getElementById('userProfileSection');
            const adminNavLink = document.getElementById('adminNavLink');

            if (userSession) {
                // User is logged in
                const user = JSON.parse(userSession);
                authButtons.innerHTML = `
                    <span class="welcome-text">Welcome, ${user.fullName}</span>
                    <button onclick="userLogout()" class="auth-btn logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Sign Out
                    </button>
                `;

                // Show user profile in sidebar
                userProfileSection.style.display = 'block';
                document.getElementById('userDisplayName').textContent = user.fullName;
                document.getElementById('userEmail').textContent = user.email;

            } else {
                // User is not logged in
                authButtons.innerHTML = `
                    <button onclick="showLogin()" class="auth-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    <button onclick="showRegister()" class="auth-btn register-btn">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                `;

                userProfileSection.style.display = 'none';
            }

            // Show admin link if admin is logged in
            if (adminSession === 'true') {
                adminNavLink.style.display = 'block';
            } else {
                adminNavLink.style.display = 'none';
            }
        }

        // User authentication functions
        function showLogin() {
            window.location.href = 'user-login.php';
        }

        function showRegister() {
            window.location.href = 'user-register.php';
        }

        function userLogout() {
            if (confirm('Are you sure you want to sign out?')) {
                localStorage.removeItem('userSession');
                sessionStorage.removeItem('userSession');
                localStorage.removeItem('adminLoggedIn'); // Also log out admin if admin
                alert('You have been signed out successfully!');
                checkUserLoginStatus(); // Update UI after logout
                window.location.href = 'index.php'; // Redirect to homepage
            }
        }

        function viewProfile() {
            alert('Profile page would be implemented here');
        }

        // Newsletter subscription
        function subscribeNewsletter(event) {
            event.preventDefault();
            const email = document.getElementById('newsletterEmail').value;
            const successMsg = document.getElementById('newsletterSuccess');

            // Simple validation
            if (email && email.includes('@')) {
                // In a real application, you'd send this email to a server-side script
                // to save it to your database or mailing list service.
                successMsg.style.display = 'block';
                document.getElementById('newsletterEmail').value = '';

                setTimeout(() => {
                    successMsg.style.display = 'none';
                }, 5000); // Hide after 5 seconds
            } else {
                alert('Please enter a valid email address');
            }
        }

        function quickSubscribe(event) {
            event.preventDefault();
            const form = event.target;
            const email = form.querySelector('input[type="email"]').value;

            if (email && email.includes('@')) {
                // In a real application, you'd send this email to a server-side script
                // to save it to your database or mailing list service.
                alert('Thank you for subscribing!');
                form.reset();
            } else {
                alert('Please enter a valid email address');
            }
        }

        // Search functionality
        function searchArticles() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.trim();

            if (searchTerm) {
                // In a real application, search.php would query the database
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            } else {
                alert('Please enter a search term');
            }
        }

        // Theme toggle
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('themeIcon');

            if (body.getAttribute('data-theme') === 'dark') {
                body.removeAttribute('data-theme');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Load saved theme
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('themeIcon');

            if (savedTheme === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
                if (themeIcon) {
                    themeIcon.className = 'fas fa-sun';
                }
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            checkUserLoginStatus();
            loadSavedTheme();

            // Allow search on Enter key
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchArticles();
                }
            });
        });
    </script>
</body>
</html>