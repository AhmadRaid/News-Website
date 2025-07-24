<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "news_db";

// Create database connection
$conn = new mysqli("localhost:8111", $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>");
} else {
    $conn->set_charset("utf8mb4");

    $search_results = [];
    $search_query = "";

    // Check if a search term is provided in the URL
    if (isset($_GET['q']) && !empty($_GET['q'])) {
        $search_query = $_GET['q'];

        // Prepare the SQL statement to prevent SQL injection
        // Search for articles where the title contains the search term (case-insensitive)
        $sql_search = "SELECT
                            a.article_id,
                            a.title,
                            LEFT(a.content, 200) as content_excerpt,
                            a.published_date,
                            a.image_url,
                            c.category_name,
                            u.username AS author_name
                        FROM
                            articles a
                        LEFT JOIN
                            categories c ON a.category_id = c.category_id
                        LEFT JOIN
                            users u ON a.author_id = u.user_id
                        WHERE
                            a.title LIKE ?
                        ORDER BY
                            a.published_date DESC";

        // Use prepared statements for security
        $stmt = $conn->prepare($sql_search);
        if ($stmt) {
            $search_term_param = '%' . $search_query . '%';
            $stmt->bind_param("s", $search_term_param);
            $stmt->execute();
            $result_search = $stmt->get_result();

            if ($result_search->num_rows > 0) {
                while($row = $result_search->fetch_assoc()) {
                    $search_results[] = $row;
                }
            }
            $stmt->close();
        } else {
            error_log("Error preparing search statement: " . $conn->error);
        }
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
    <title>Search Results for "<?php echo htmlspecialchars($search_query); ?>" - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Add some basic styling for search results if not already in styles.css */
        .search-results-container {
            width: 80%;
            margin: 2rem auto;
            padding: 20px;
            background-color: var(--card-bg-color);
            border-radius: 8px;
            box-shadow: var(--shadow-small);
        }

        .search-results-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .search-results-list .article-card {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease-in-out;
            cursor: pointer;
            background-color: var(--background-color);
        }

        .search-results-list .article-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .search-results-list .article-image {
            width: 200px;
            height: 150px;
            flex-shrink: 0;
            overflow: hidden;
            background-color: var(--placeholder-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-results-list .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .search-results-list .article-image .fas {
            font-size: 3rem;
            color: var(--text-color-light);
        }

        .search-results-list .article-content {
            padding: 1rem;
            flex-grow: 1;
        }

        .search-results-list .article-category {
            background-color: var(--accent-color);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8em;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .search-results-list .article-title {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.3em;
            color: var(--text-color);
        }

        .search-results-list .article-excerpt {
            font-size: 0.9em;
            color: var(--text-color-light);
            line-height: 1.5;
            margin-bottom: 0.8rem;
        }

        .search-results-list .article-meta {
            font-size: 0.8em;
            color: var(--text-color-light);
            display: flex;
            gap: 1rem;
        }

        .search-results-list .article-meta i {
            margin-right: 0.3rem;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            font-size: 1.2em;
            color: var(--text-color-light);
        }

        @media (max-width: 768px) {
            .search-results-container {
                width: 95%;
            }
            .search-results-list .article-card {
                flex-direction: column;
                align-items: center;
            }
            .search-results-list .article-image {
                width: 100%;
                height: 200px;
                border-bottom-left-radius: 0;
                border-top-right-radius: 8px;
            }
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
                        <input type="text" placeholder="Search news articles..." id="searchInput" value="<?php echo htmlspecialchars($search_query); ?>">
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
                        <a href="admin-login.html" class="admin-nav-link">
                            <i class="fas fa-cog"></i> Admin
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-container">
        <main class="content-area">
            <section class="search-results-container">
                <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <div class="search-results-list">
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $article): ?>
                            <article class="article-card" onclick="location.href='article.php?id=<?php echo htmlspecialchars($article['article_id']); ?>'">
                                <div class="article-image">
                                    <?php if (!empty($article['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="article-content">
                                    <span class="article-category"><?php echo htmlspecialchars($article['category_name'] ?: 'General'); ?></span>
                                    <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                    <p class="article-excerpt"><?php echo htmlspecialchars($article['content_excerpt']); ?>...</p>
                                    <div class="article-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article['author_name'] ?: 'Unknown'); ?></span>
                                        <span><i class="fas fa-clock"></i> <?php echo date("F j, Y", strtotime($article['published_date'])); ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-results">No articles found matching "<?php echo htmlspecialchars($search_query); ?>".</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
        
        <aside class="sidebar">
            <section class="sidebar-section">
                <h3 class="section-title">Trending Now</h3>
                <div class="trending-list">
                    <?php
                    // Re-fetch trending articles for the sidebar if needed, or pass them from index.php if search.php is included
                    // For simplicity, we'll assume a basic inclusion or re-fetch for this example.
                    // In a more complex setup, you might have a shared header/footer file.
                    // For now, let's just make sure it's available or remove it if not critical to search.php
                    // For this example, I'll omit the dynamic trending data to keep search.php focused,
                    // but you can add similar PHP logic here as in index.php if you want it populated.
                    ?>
                    <?php if (isset($trending_articles) && !empty($trending_articles)): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($trending_articles as $article): ?>
                            <article class="trending-item" onclick="location.href='article.php?id=<?php echo htmlspecialchars($article['article_id']); ?>'">
                                <div class="trending-number"><?php echo $i++; ?></div>
                                <div>
                                    <h4><?php echo htmlspecialchars($article['title']); ?></h4>
                                    <span class="news-time"><?php echo date("F j, Y", strtotime($article['published_date'])); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No trending articles found.</p>
                    <?php endif; ?>
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