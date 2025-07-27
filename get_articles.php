<?php
session_start();

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("<h1>Database connection error. Please try again later.</h1>");
} else {
    $conn->set_charset("utf8mb4");

    // --- Fetch Featured Articles ---
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
                    LIMIT 3";
    
    $result_featured = $conn->query($sql_featured);

    if ($result_featured) {
        if ($result_featured->num_rows > 0) {
            while($row = $result_featured->fetch_assoc()) {
                // تعديل مسار الصورة ليعكس الهيكل الحالي للمجلدات
                if (!empty($row['image_url'])) {
                    $row['image_url'] = 'admin/uploads/' . basename($row['image_url']);
                }
                $featured_articles[] = $row;
            }
        }
        $result_featured->free();
    } else {
        error_log("Error fetching featured articles: " . $conn->error);
    }

    // --- Fetch Latest News Articles ---
    $latest_articles = [];
    $sql_latest = "SELECT 
                        a.article_id, 
                        a.title, 
                        LEFT(a.content, 100) as content_excerpt, 
                        a.published_date,
                        a.image_url
                    FROM 
                        articles a
                    ORDER BY 
                        a.published_date DESC
                    LIMIT 4 OFFSET 3";
    
    $result_latest = $conn->query($sql_latest);

    if ($result_latest) {
        if ($result_latest->num_rows > 0) {
            while($row = $result_latest->fetch_assoc()) {
                if (!empty($row['image_url'])) {
                    $row['image_url'] = 'admin/uploads/' . basename($row['image_url']);
                }
                $latest_articles[] = $row;
            }
        }
        $result_latest->free();
    } else {
        error_log("Error fetching latest articles: " . $conn->error);
    }

    // --- Fetch Trending News ---
    $trending_articles = [];
    $sql_trending = "SELECT 
                        a.article_id, 
                        a.title, 
                        a.published_date,
                        a.image_url
                    FROM 
                        articles a
                    ORDER BY 
                        a.published_date DESC
                    LIMIT 3";
    
    $result_trending = $conn->query($sql_trending);

    if ($result_trending) {
        if ($result_trending->num_rows > 0) {
            while($row = $result_trending->fetch_assoc()) {
                if (!empty($row['image_url'])) {
                    $row['image_url'] = 'admin/uploads/' . basename($row['image_url']);
                }
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
    <style>
        /* CSS Styles remain the same as your original code */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .article-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
        }
        
        .article-image {
            height: 200px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-image i {
            font-size: 3rem;
            color: #666;
        }
        
        /* بقية الأنماط كما هي */
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
                    <li><a href="get_articles.php" class="active">Home</a></li>
                    <li><a href="category.php?cat_id=1">Politics</a></li>
                    <li><a href="category.php?cat_id=2">Technology</a></li>
                    <li><a href="category.php?cat_id=3">Sports</a></li>
                    <li><a href="category.php?cat_id=4">Entertainment</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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
                        <p>No featured articles available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="latest-section">
                <h2 class="section-title">Latest News</h2>
                <div class="latest-grid">
                    <?php if (!empty($latest_articles)): ?>
                        <?php foreach ($latest_articles as $article): ?>
                            <article class="article-card" onclick="location.href='article.php?id=<?php echo htmlspecialchars($article['article_id']); ?>'">
                                <div class="article-image">
                                    <?php if (!empty($article['image_url']) && file_exists($article['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-newspaper"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="article-content">
                                    <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                    <p class="article-excerpt"><?php echo htmlspecialchars($article['content_excerpt']); ?>...</p>
                                    <div class="article-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo date("F j, Y", strtotime($article['published_date'])); ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No latest articles available.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="newsletter-section">
                <!-- بقية قسم النشرة البريدية كما هو -->
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
        <!-- بقية الفوتر كما هو -->
    </footer>

    <script>
        // بقية السكريبتات كما هي
        function searchArticles() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm) {
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            } else {
                alert('Please enter a search term.');
            }
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            loadSavedTheme();
            
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchArticles();
                }
            });
        });

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
    </script>
</body>
</html>