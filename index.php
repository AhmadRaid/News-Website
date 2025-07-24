<?php
session_start(); // Start the session to track user login status

// --- Database Configuration (Same as manage-articles.php) ---
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "news_db"; 

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

// Check connection
if ($conn->connect_error) {
    // Log the error but don't display sensitive info directly on the front-end
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>"); // A user-friendly message
} else {
    $conn->set_charset("utf8mb4");

    // --- Fetch Featured Articles (e.g., top 3 or recent 3) ---
    // You might add a 'featured' column to your articles table for real featured articles
    // For now, let's get the 3 most recent articles to serve as "featured"
    $featured_articles = [];
    $sql_featured = "SELECT 
                        a.article_id, 
                        a.title, 
                        LEFT(a.content, 150) as content_excerpt, 
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
                    ORDER BY 
                        a.published_date DESC
                    LIMIT 3"; // Get the 3 most recent
    
    $result_featured = $conn->query($sql_featured);

    if ($result_featured) {
        if ($result_featured->num_rows > 0) {
            while($row = $result_featured->fetch_assoc()) {
                $featured_articles[] = $row;
            }
        }
        $result_featured->free();
    } else {
        error_log("Error fetching featured articles: " . $conn->error);
    }

    // --- Fetch Latest News Articles (e.g., next 4 after featured) ---
    $latest_articles = [];
    $sql_latest = "SELECT 
                        a.article_id, 
                        a.title, 
                        LEFT(a.content, 100) as content_excerpt, 
                        a.published_date
                    FROM 
                        articles a
                    ORDER BY 
                        a.published_date DESC
                    LIMIT 4 OFFSET 3"; // Get the next 4 articles after the first 3
    
    $result_latest = $conn->query($sql_latest);

    if ($result_latest) {
        if ($result_latest->num_rows > 0) {
            while($row = $result_latest->fetch_assoc()) {
                $latest_articles[] = $row;
            }
        }
        $result_latest->free();
    } else {
        error_log("Error fetching latest articles: " . $conn->error);
    }

    // --- Fetch Trending News (e.g., top 3 based on some criteria, here just recent again) ---
    // In a real app, 'trending' would be based on views, shares, etc.
    // For simplicity, let's use the most recent 3 (or you can use different ones)
    $trending_articles = [];
    $sql_trending = "SELECT 
                        a.article_id, 
                        a.title, 
                        a.published_date
                    FROM 
                        articles a
                    ORDER BY 
                        a.published_date DESC
                    LIMIT 3"; // Fetch the 3 most recent for trending
    
    $result_trending = $conn->query($sql_trending);

    if ($result_trending) {
        if ($result_trending->num_rows > 0) {
            while($row = $result_trending->fetch_assoc()) {
                $trending_articles[] = $row;
            }
        }
        $result_trending->free();
    } else {
        error_log("Error fetching trending articles: " . $conn->error);
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
    <title>Global News Network - Breaking News & Updates</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
                        <?php if (isset($_SESSION['user_id'])): // If a user is logged in ?>
                            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); // Assuming 'username' is in session ?></span>
                            <button onclick="userLogout()" class="auth-btn logout-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        <?php else: // If no user is logged in ?>
                            <button onclick="showLogin()" class="auth-btn">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                            <button onclick="showRegister()" class="auth-btn register-btn">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="category.php?cat_id=1">Politics</a></li>
                    <li><a href="category.php?cat_id=2">Technology</a></li>
                    <li><a href="category.php?cat_id=3">Sports</a></li>
                    <li><a href="category.php?cat_id=4">Entertainment</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): // Show admin link only if role is admin ?>
                    <li id="adminNavLink">
                        <a href="./admin/index.php" class="admin-nav-link">
                             <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section class="breaking-news">
        <div class="breaking-container">
            <div class="breaking-label">
                <i class="fas fa-bolt"></i> Breaking
            </div>
            <div class="breaking-text">
                Major Breakthrough in Renewable Energy Tech • Global Climate Summit Reaches Historic Agreement • Tech Giants Announce New AI Safety Initiatives
            </div>
        </div>
    </section>

    <div class="main-container">
        <main class="content-area">
            <section class="featured-section">
                <h2 class="section-title">Featured Articles</h2>
                <div class="featured-grid">
                    <?php if (!empty($featured_articles)): ?>
                        <?php foreach ($featured_articles as $article): ?>
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
                        <p>No featured articles available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="newsletter-section">
                <div class="newsletter-container">
                    <h2 class="newsletter-title">Stay Informed</h2>
                    <p class="newsletter-subtitle">Get the latest news directly to your inbox</p>
                    
                    <div id="newsletterSuccess" class="success-message" style="display: none;">
                        Thank you for subscribing to our newsletter!
                    </div>
                    
                    <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                        <input type="email" class="newsletter-input" placeholder="Enter your email address" id="newsletterEmail" required>
                        <button type="submit" class="newsletter-btn">Subscribe Now</button>
                    </form>

                    <div class="newsletter-features">
                        <div class="newsletter-feature">
                            <i class="fas fa-newspaper"></i>
                            <h4>Daily Updates</h4>
                            <p>Receive top stories daily</p>
                        </div>
                        <div class="newsletter-feature">
                            <i class="fas fa-bolt"></i>
                            <h4>Breaking News</h4>
                            <p>Be the first to know about breaking news</p>
                        </div>
                        <div class="newsletter-feature">
                            <i class="fas fa-chart-line"></i>
                            <h4>Weekly Analysis</h4>
                            <p>In-depth analysis and trending topics</p>
                        </div>
                    </div>
                </div>
            </section>

        </main>

        <aside class="sidebar">
            <section class="sidebar-section">
                <h3 class="section-title">Trending Now</h3>
                <div class="trending-list">
                    <?php if (!empty($trending_articles)): ?>
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
                        <p>No trending articles available.</p>
                    <?php endif; ?>
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
                        <li><a href="#contact">Contact Us</a></li>
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
                <p>&copy; 2024 Global News Network. All Rights Reserved. | Designed with love for informed citizens worldwide.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Function to update login status on the interface
        function checkUserLoginStatus() {
            // Note: Since we are now relying on PHP Sessions, this JavaScript part
            // will be updated based on the initial PHP output when the page loads.
            // We won't need localStorage or sessionStorage here for basic login status check.

            const authButtons = document.getElementById('authButtons');
            const userProfileSection = document.getElementById('userProfileSection');
            const adminNavLink = document.getElementById('adminNavLink');

            // PHP determines whether the user is logged in or not when the page loads,
            // then JavaScript can interact with elements that were displayed or hidden by PHP.

            // Hide the profile section if the user is not logged in (by default it will be hidden by CSS unless shown by PHP)
            // Since we're using PHP for conditional content rendering, we can simplify this part.
            <?php if (!isset($_SESSION['user_id'])): ?>
                userProfileSection.style.display = 'none';
            <?php else: ?>
                userProfileSection.style.display = 'block';
                // You can update the name and email here if you want to ensure they are updated
                // but it's better to populate them directly from PHP in HTML.
                // document.getElementById('userDisplayName').textContent = "<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>";
                // document.getElementById('userEmail').textContent = "<?php echo htmlspecialchars($_SESSION['email'] ?? 'user@example.com'); ?>";
            <?php endif; ?>

            // The admin link is handled by PHP based on the role, so JavaScript logic can be removed here
            // unless there's dynamic showing/hiding based on client-side logic after initial load.
            // For robust admin link display, rely primarily on PHP session check for 'role'.
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                if (adminNavLink) {
                    adminNavLink.style.display = 'none';
                }
            <?php endif; ?>

            // Here, we won't modify authButtons via JavaScript anymore because PHP handles them
            // but we'll leave the showLogin, showRegister, and userLogout functions to redirect the user
        }

        // User authentication functions
        function showLogin() {
            window.location.href = 'user-login.php';
        }

        function showRegister() {
            window.location.href = 'user-register.php';
        }

        function userLogout() {
            if (confirm('Are you sure you want to log out?')) {
                // Send request to logout.php
                window.location.href = 'logout.php'; 
            }
        }

        function viewProfile() {
            alert('Profile page will be added here later.');
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
                alert('Please enter a valid email address.');
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
                alert('Please enter a valid email address.');
            }
        }

        // Search functionality
        function searchArticles() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm) {
                // In a real application, search.php would query the database
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`; // Changed to search.php
            } else {
                alert('Please enter a search term.');
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
            checkUserLoginStatus(); // Still important for updating some JS elements related to the profile
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