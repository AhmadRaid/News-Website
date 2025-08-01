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
                    // تعديل مسار الصورة ليعكس الهيكل الحالي للمجلدات
                    if (!empty($row['image_url'])) {
                        $row['image_url'] = 'admin/uploads/' . basename($row['image_url']);
                    }
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
                    <a href="get_articles.php">Global News Network</a>
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
                    <li><a href="get_articles.php">Home</a></li>
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
            <section class="search-results-container">
                <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <div class="search-results-list">
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $article): ?>
                            <article class="article-card" onclick="location.href='article.php?id=<?php echo htmlspecialchars($article['article_id']); ?>'">
                                <div class="article-image">
                                    <?php if (!empty($article['image_url']) && file_exists($article['image_url'])): ?>
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
                        <li><a href="get_articles.php">Home</a></li>
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

    <script>
        // Check user login status and update navigation
        function checkUserLoginStatus() {
            const userSession = localStorage.getItem('userSession') || sessionStorage.getItem('userSession');
            const adminSession = localStorage.getItem('adminLoggedIn');
            const authButtons = document.getElementById('authButtons');
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
                localStorage.removeItem('adminLoggedIn');
                checkUserLoginStatus();
                window.location.href = 'get_articles.php';
            }
        }

        // Newsletter subscription
        function quickSubscribe(event) {
            event.preventDefault();
            const form = event.target;
            const email = form.querySelector('input[type="email"]').value;
            
            if (email && email.includes('@')) {
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