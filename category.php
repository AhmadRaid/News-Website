<?php
session_start();

// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "news_db"; 

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>");
} else {
    $conn->set_charset("utf8mb4");

    $category_id = null;
    $category_name = "All Articles";

    // Check if a category ID is provided in the URL
    if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id'])) {
        $category_id = intval($_GET['cat_id']);

        // Fetch category name
        $stmt_cat = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $stmt_cat->bind_param("i", $category_id);
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();
        if ($row_cat = $result_cat->fetch_assoc()) {
            $category_name = htmlspecialchars($row_cat['category_name']);
        } else {
            $category_name = "Category Not Found";
        }
        $stmt_cat->close();
    }

    // --- Fetch Articles for the Selected Category ---
    $articles = [];
    $sql_articles = "SELECT 
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
                        users u ON a.author_id = u.user_id";
    
    if ($category_id !== null) {
        $sql_articles .= " WHERE a.category_id = ?";
    }
    
    $sql_articles .= " ORDER BY a.published_date DESC";

    $stmt_articles = $conn->prepare($sql_articles);

    if ($category_id !== null) {
        $stmt_articles->bind_param("i", $category_id);
    }

    $stmt_articles->execute();
    $result_articles = $stmt_articles->get_result();

    if ($result_articles) {
        if ($result_articles->num_rows > 0) {
            while($row = $result_articles->fetch_assoc()) {
                // تعديل مسار الصورة ليعكس الهيكل الحالي للمجلدات
                if (!empty($row['image_url'])) {
                    $row['image_url'] = 'admin/uploads/' . basename($row['image_url']);
                }
                $articles[] = $row;
            }
        }
        $result_articles->free();
    } else {
        error_log("Error fetching articles: " . $conn->error);
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
    <title>Global News Network - <?php echo $category_name; ?> Articles</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .category-articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .category-articles-grid .article-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            background-color: var(--card-bg);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        
        .category-articles-grid .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .category-articles-grid .article-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-bg);
        }
        
        .category-articles-grid .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-articles-grid .article-image .fas {
            font-size: 4rem;
            color: var(--text-muted);
        }
        
        .category-articles-grid .article-content {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .category-articles-grid .article-category {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        
        .category-articles-grid .article-title {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .category-articles-grid .article-excerpt {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .category-articles-grid .article-meta {
            font-size: 0.8em;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }
        
        .main-category-heading {
            text-align: center;
            margin: 30px 0;
            color: var(--heading-color);
            font-size: 2.5em;
            padding: 0 20px;
        }
        
        .no-articles {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 50px;
            font-size: 1.2em;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .category-articles-grid {
                grid-template-columns: 1fr;
            }
            
            .main-category-heading {
                font-size: 2em;
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
                        <input type="text" placeholder="Search news articles..." id="searchInput">
                        <button type="submit" onclick="searchArticles()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    
                    <div class="auth-buttons" id="authButtons">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <button onclick="userLogout()" class="auth-btn logout-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        <?php else: ?>
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
                    <li><a href="get_articles.php">Home</a></li>
                    <li><a href="category.php?cat_id=1" <?php if ($category_id == 1) echo 'class="active"'; ?>>Politics</a></li>
                    <li><a href="category.php?cat_id=2" <?php if ($category_id == 2) echo 'class="active"'; ?>>Technology</a></li>
                    <li><a href="category.php?cat_id=3" <?php if ($category_id == 3) echo 'class="active"'; ?>>Sports</a></li>
                    <li><a href="category.php?cat_id=4" <?php if ($category_id == 4) echo 'class="active"'; ?>>Entertainment</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li id="adminNavLink">
                        <a href="./admin/get_articles.php" class="admin-nav-link">
                             <i class="fas fa-cog"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-container">
        <h1 class="main-category-heading"><?php echo $category_name; ?> Articles</h1>

        <section class="category-articles-grid">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $article): ?>
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
                <p class="no-articles">No articles found for this category.</p>
            <?php endif; ?>
        </section>
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

    <script>
        // User authentication functions
        function showLogin() {
            window.location.href = 'user-login.php';
        }

        function showRegister() {
            window.location.href = 'user-register.php';
        }

        function userLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'logout.php'; 
            }
        }

        // Search functionality
        function searchArticles() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm) {
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
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
            loadSavedTheme();
            
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchArticles();
                }
            });
        });
    </script>
</body>
</html>