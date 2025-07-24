<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "news_db"; 

// Variables to store alert messages
$success_message = "";
$error_message = "";
$article = null; // Variable to hold article data for the form

// Create database connection
$conn = new mysqli("localhost:8111", "root", "", "news_db");

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $error_message = 'Database connection failed. Please try again later.';
} else {
    $conn->set_charset("utf8mb4");

    // --- Handle GET request to fetch article for editing ---
    // This runs when you first open the page via a link from manage-articles.php
    if (isset($_GET['id'])) {
        $article_id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT article_id, title, content, image_url, category_id FROM articles WHERE article_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $article_id); // 'i' for integer
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $article = $result->fetch_assoc();
            } else {
                $error_message = "Article not found with ID: " . htmlspecialchars($article_id);
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare select statement: " . $conn->error;
            error_log("Failed to prepare select statement: " . $conn->error);
        }
    }

    // --- Handle POST request to update article ---
    // This runs when the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['article_id'])) {
        $article_id = (int)$_POST['article_id'];
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $image_url = isset($_POST['imageUrl']) ? trim($_POST['imageUrl']) : '';
        $category_id = isset($_POST['category']) ? (int)$_POST['category'] : null;

        // Basic data validation
        if (empty($title) || empty($content) || empty($category_id)) {
            $error_message = 'Please fill in all required fields (Title, Content, Category).';
            // To retain user input on validation error, re-populate $article with POST data
            $article = [
                'article_id' => $article_id,
                'title' => $title,
                'content' => $content,
                'image_url' => $image_url,
                'category_id' => $category_id
            ];
        } else {
            // Prepare the UPDATE statement
            // Note: author_id and published_date are typically not changed during an edit
            // but if you need to update them, add them to this query.
            $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, image_url = ?, category_id = ? WHERE article_id = ?");

            if ($stmt === false) {
                error_log("MySQL prepare error: " . $conn->error);
                $error_message = 'Failed to prepare database statement for update.';
            } else {
                // 'sssi' for string, string, string, integer, 'i' for the last integer (article_id)
                $stmt->bind_param("sssis", $title, $content, $image_url, $category_id, $article_id);

                if ($stmt->execute()) {
                    $success_message = 'Article updated successfully!';
                    // Update the $article variable to reflect the new data in the form
                    // This is important so the form shows the *just updated* data, not old data.
                    $article['title'] = $title;
                    $article['content'] = $content;
                    $article['image_url'] = $image_url;
                    $article['category_id'] = $category_id;
                } else {
                    error_log("Error executing update statement: " . $stmt->error);
                    $error_message = 'Failed to update article: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
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
    <title>Edit Article - Global News Network</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
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
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .required {
            color: #dc3545;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            /* Using PHP to control display initially, JS for fading */
            display: none; 
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
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-edit"></i> Edit Article</h1>
                <p>Modify existing article details.</p>
            </div>
            <div>
                <a href="manage-articles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Articles
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

        <?php if ($article): // Only show the form if an article was found ?>
            <div class="form-container">
                <form id="editArticleForm" method="POST" action="">
                    <input type="hidden" name="article_id" value="<?php echo htmlspecialchars($article['article_id']); ?>">

                    <div class="form-group">
                        <label for="title">Article Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" placeholder="Enter article title" 
                                required minlength="10" maxlength="200" value="<?php echo htmlspecialchars($article['title']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category <span class="required">*</span></label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="1" <?php echo (isset($article['category_id']) && $article['category_id'] == '1') ? 'selected' : ''; ?>>Politics</option> 
                                <option value="2" <?php echo (isset($article['category_id']) && $article['category_id'] == '2') ? 'selected' : ''; ?>>Technology</option>
                                <option value="3" <?php echo (isset($article['category_id']) && $article['category_id'] == '3') ? 'selected' : ''; ?>>Sports</option>
                                <option value="4" <?php echo (isset($article['category_id']) && $article['category_id'] == '4') ? 'selected' : ''; ?>>Entertainment</option>
                            </select>
                        </div>
                        
              
                    </div>

                    <div class="form-group">
                        <label for="content">Article Content <span class="required">*</span></label>
                        <textarea id="content" name="content" placeholder="Write your article content here..." 
                                         required minlength="200"><?php echo htmlspecialchars($article['content']); ?></textarea>
                        <small style="color: #666;">Full article content (minimum 200 characters)</small>
                    </div>

                    <div class="form-group">
                        <label for="imageUrl">Featured Image URL</label>
                        <input type="url" id="imageUrl" name="imageUrl" placeholder="https://example.com/image.jpg"
                               value="<?php echo htmlspecialchars($article['image_url']); ?>">
                        <small style="color: #666;">Optional: URL for the featured image of your article</small>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Article
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="previewArticle()">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                </form>
            </div>
        <?php else: // Message if no article is found or ID is missing ?>
            <p>No article found with the specified ID. Please go back to the <a href="manage-articles.php">manage articles page</a>.</p>
        <?php endif; ?>
    </div>

    <script>
        // Function to display success alerts
        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successAlert').style.display = 'block';
            document.getElementById('errorAlert').style.display = 'none';
            window.scrollTo(0, 0); // Scroll to top to show the alert
            setTimeout(hideMessages, 3000); // Hide after 3 seconds
        }

        // Function to display error alerts
        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorAlert').style.display = 'block';
            document.getElementById('successAlert').style.display = 'none';
            window.scrollTo(0, 0); // Scroll to top to show the alert
            setTimeout(hideMessages, 5000); // Hide after 5 seconds
        }

        // Function to hide all alert messages
        function hideMessages() {
            document.getElementById('successAlert').style.display = 'none';
            document.getElementById('errorAlert').style.display = 'none';
        }

        // Function to open a new window for article preview
        function previewArticle() {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            const category = document.getElementById('category').options[document.getElementById('category').selectedIndex].text;
            const status = document.getElementById('status').value;
            
            if (!title || !content) {
                showError('Please fill in the title and content to preview the article.');
                return;
            }
            
            const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Article Preview</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
                        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
                        .meta { color: #666; margin-bottom: 20px; }
                        .content { line-height: 1.6; }
                        .preview-note { background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="preview-note">
                        <strong>Preview Mode:</strong> This is how your article will appear to readers.
                    </div>
                    <h1>${title}</h1>
                    <div class="meta">
                        <strong>Category:</strong> ${category || 'Not Selected'} |
                        <strong>Status:</strong> ${status}
                    </div>
                    <div class="content">
                        ${content.replace(/\n/g, '<br>')}
                    </div>
                </body>
                </html>
            `);
            previewWindow.document.close(); // Close the document to ensure content is rendered
        }

        // Event listener for form submission to handle client-side validation
        document.getElementById('editArticleForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault(); // Prevent form submission if validation fails
                showError('Please fill in all required fields correctly.');
                // Note: HTML5 validation messages will also appear
            }
        });

        // Hide messages on page load if they aren't explicitly set by PHP
        window.onload = function() {
            <?php if (!$success_message && !$error_message): ?>
            hideMessages();
            <?php endif; ?>
        };
    </script>
</body>
</html>