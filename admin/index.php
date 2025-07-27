<?php
session_start(); // Start the session to manage user login status

// --- Database Configuration ---
$servername = "localhost";
$username_db = "root"; // Your database username
$password_db = "";     // Your database password
$dbname = "news_db";  // Your database name

// Create database connection
$conn = new mysqli("localhost:8111", $username_db, $password_db, $dbname);

// Check database connection
if ($conn->connect_error) {
    // Log the error for debugging
    error_log("Connection failed in admin.php: " . $conn->connect_error);
    // Display a user-friendly error (avoiding sensitive details)
    die("<div style='text-align: center; padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'><h1>Error connecting to the database. Please try again later.</h1></div>");
}

$conn->set_charset("utf8mb4");

// --- Fetch Dashboard Stats ---
$total_articles = 0;
$total_users = 0;
$total_categories = 0;
$total_comments = 0; // Initialize total comments

// Fetch total articles
$result_articles = $conn->query("SELECT COUNT(*) as count FROM articles");
if ($result_articles) {
    $total_articles = $result_articles->fetch_assoc()['count'];
    $result_articles->free();
}

// Fetch total users
$result_users = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result_users) {
    $total_users = $result_users->fetch_assoc()['count'];
    $result_users->free();
}

// Fetch total categories
$result_categories = $conn->query("SELECT COUNT(*) as count FROM categories");
if ($result_categories) {
    $total_categories = $result_categories->fetch_assoc()['count'];
    $result_categories->free();
}

// Fetch total comments (assuming you have a 'comments' table)
$result_comments = $conn->query("SELECT COUNT(*) as count FROM comments"); // Adjust table name if different
if ($result_comments) {
    $total_comments = $result_comments->fetch_assoc()['count'];
    $result_comments->free();
}


// --- Fetch Recent Articles from Database ---
$recent_articles = [];
$sql_recent_articles = "SELECT 
                            a.article_id, 
                            a.title, 
                            a.published_date, 
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
                        LIMIT 5"; // Limit to 5 recent articles for the dashboard view

$result_recent_articles = $conn->query($sql_recent_articles);

if ($result_recent_articles) {
    if ($result_recent_articles->num_rows > 0) {
        while($row = $result_recent_articles->fetch_assoc()) {
            $recent_articles[] = $row;
        }
    }
    $result_recent_articles->free();
} else {
    error_log("Error fetching recent articles for dashboard: " . $conn->error);
}

// Close connection
$conn->close();

// --- Basic Admin Session Check (Important for Security) ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php"); // Redirect to login if not authenticated as admin
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Your existing CSS for admin dashboard */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-box i {
            font-size: 40px;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 30px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        
        .menu-item {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
        }
        
        .menu-item i {
            font-size: 50px;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn:hover {
            background: #0056b3;
        }

        /* Styles for the recent articles table */
        .recent-articles-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .recent-articles-section h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .articles-table th, .articles-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .articles-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }
        .articles-table tbody tr:last-child td {
            border-bottom: none;
        }
        .articles-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        .articles-table .actions .btn {
            margin: 0 5px;
            padding: 6px 10px;
            font-size: 13px;
        }
        .articles-table .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .articles-table .btn-edit:hover {
            background: #e0a800;
        }
        .articles-table .btn-delete { /* Style for the new delete button */
            background: #dc3545;
        }
        .articles-table .btn-delete:hover {
            background: #c82333;
        }

        /* Make it work on phones */
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .menu {
                grid-template-columns: 1fr;
            }
            .articles-table, .articles-table tbody, .articles-table tr, .articles-table td {
                display: block;
                width: 100%;
            }
            .articles-table thead {
                display: none; /* Hide table headers for mobile */
            }
            .articles-table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
            }
            .articles-table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }
            .articles-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: calc(50% - 20px);
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: #555;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! Here's an overview of your news site.</p>
            <a href="../get_articles.php" class="btn">View Website</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>

    
        
        <div class="menu">
            <a href="manage-articles.php" class="menu-item">
                <i class="fas fa-newspaper"></i>
                <h3>Manage Articles</h3>
                <p>View, edit, and delete articles</p>
            </a>
            
            <a href="add-article.php" class="menu-item">
                <i class="fas fa-plus"></i>
                <h3>Add New Article</h3>
                <p>Create a new news article</p>
            </a>
        </div>

        ---

        <div class="recent-articles-section">
            <h2>Recent Articles</h2>
            <?php if (empty($recent_articles)): ?>
                <p>No recent articles found. <a href="add-article.php">Add a new one</a>!</p>
            <?php else: ?>
                <table class="articles-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Published Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_articles as $article): ?>
                        <tr>
                            <td data-label="Title"><?php echo htmlspecialchars($article['title']); ?></td>
                            <td data-label="Category"><?php echo htmlspecialchars($article['category_name'] ?: 'N/A'); ?></td>
                            <td data-label="Author"><?php echo htmlspecialchars($article['author_name'] ?: 'Unknown'); ?></td>
                            <td data-label="Published Date"><?php echo date("Y-m-d", strtotime($article['published_date'])); ?></td>
                          
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="text-align: right; margin-top: 15px;">
                    <a href="manage-articles.php" class="btn">View All Articles <i class="fas fa-arrow-right"></i></a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to confirm delete, redirecting to manage-articles.php for handling
        function confirmDelete(id, title) {
            if (confirm(`Are you sure you want to delete the article titled "${title}"? This action cannot be undone.`)) {
                // Redirect to manage-articles.php with delete action and ID
                window.location.href = `manage-articles.php?action=delete&id=${id}`;
            }
        }
        
        console.log('Admin dashboard loaded successfully!');
    </script>
</body>
</html>