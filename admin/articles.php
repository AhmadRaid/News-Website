<?php


// Variables to store alert messages
$success_message = "";
$error_message = "";

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $error_message = 'Database connection failed. Please try again later.';
} else {
    $conn->set_charset("utf8mb4");

    // --- Handle Delete Request ---
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $article_id = (int)$_GET['id'];
        $stmt = $conn->prepare("DELETE FROM articles WHERE article_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $article_id);
            if ($stmt->execute()) {
                $success_message = "Article deleted successfully!";
            } else {
                $error_message = "Failed to delete article: " . $stmt->error;
                error_log("Error deleting article: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare delete statement: " . $conn->error;
            error_log("Failed to prepare delete statement: " . $conn->error);
        }
    }

    // --- Fetch Articles from Database ---
    $articles = [];
    $sql = "SELECT 
                a.article_id, 
                a.title, 
                LEFT(a.content, 150) as content_excerpt, 
                a.published_date, 
                c.category_name, 
                u.username AS author_name -- <-- Changed to 'users' table and 'username' column
            FROM 
                articles a
            LEFT JOIN 
                categories c ON a.category_id = c.category_id
            LEFT JOIN 
                users u ON a.author_id = u.user_id -- <-- Changed to 'users' table and 'user_id' column
            ORDER BY 
                a.published_date DESC";
    
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $articles[] = $row;
            }
        }
        $result->free();
    } else {
        $error_message = "Error fetching articles: " . $conn->error;
        error_log("Error fetching articles: " . $conn->error);
    }
}

// Close connection if it was opened successfully
if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 0;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-add {
            background: #28a745;
        }
        .btn-add:hover {
            background: #218838;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none; /* Hidden by default */
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .articles-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden; /* Ensures rounded corners on children */
        }
        .articles-table th, .articles-table td {
            padding: 15px;
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
        .actions {
            white-space: nowrap; /* Keep buttons on one line */
        }
        .actions .btn {
            margin: 0 5px;
            padding: 8px 12px;
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            .articles-table, .articles-table tbody, .articles-table tr, .articles-table td {
                display: block;
                width: 100%;
            }
            .articles-table thead {
                display: none; /* Hide table headers (but not essential for accessibility) */
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
            <div>
                <h1><i class="fas fa-newspaper"></i> Manage Articles</h1>
                <p>View, edit, and delete news articles.</p>
            </div>
            <div>
                <a href="add-article.php" class="btn btn-add">
                    <i class="fas fa-plus"></i> Add New Article
                </a>
            </div>
        </div>

        <?php if ($success_message): ?>
        <div id="successAlert" class="alert alert-success" style="display: block;">
            <i class="fas fa-check-circle"></i> <span id="successMessage"><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div id="errorAlert" class="alert alert-error" style="display: block;">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"><?php echo $error_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if (empty($articles)): ?>
            <p>No articles found. Why not <a href="add-article.php">add a new one</a>?</p>
        <?php else: ?>
            <table class="articles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Published Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                    <tr>
                        <td data-label="ID"><?php echo htmlspecialchars($article['article_id']); ?></td>
                        <td data-label="Title"><?php echo htmlspecialchars($article['title']); ?></td>
                        <td data-label="Excerpt"><?php echo htmlspecialchars($article['content_excerpt']); ?>...</td>
                        <td data-label="Category"><?php echo htmlspecialchars($article['category_name'] ?: 'N/A'); ?></td>
                        <td data-label="Author"><?php echo htmlspecialchars($article['author_name'] ?: 'Unknown'); ?></td>
                        <td data-label="Published Date"><?php echo date("Y-m-d", strtotime($article['published_date'])); ?></td>
                        <td data-label="Actions" class="actions">
                            <a href="edit-article.php?id=<?php echo $article['article_id']; ?>" class="btn btn-edit" title="Edit Article">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $article['article_id']; ?>, '<?php echo htmlspecialchars($article['title']); ?>')" class="btn btn-delete" title="Delete Article">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(id, title) {
            if (confirm(`Are you sure you want to delete the article titled "${title}"? This action cannot be undone.`)) {
                window.location.href = `manage-articles.php?action=delete&id=${id}`;
            }
        }

        // Hide messages after a few seconds
        window.onload = function() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');

            if (successAlert && successAlert.style.display === 'block') {
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 3000); // Hide after 3 seconds
            }

            if (errorAlert && errorAlert.style.display === 'block') {
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 5000); // Hide after 5 seconds
            }
        };
    </script>
</body>
</html>