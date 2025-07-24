<?php
// --- Database Configuration ---
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "news_db"; 

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

    // --- Handle POST request ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $image_url = isset($_POST['imageUrl']) ? trim($_POST['imageUrl']) : '';
        $category_id = isset($_POST['category']) ? (int)$_POST['category'] : null;
        
        // --- IMPORTANT CHANGE HERE ---
        // Instead of directly taking author_id from $_POST, we are hardcoding it to 2.
        // If you need it to be dynamic based on a logged-in user,
        // you'll need a proper login system that stores user ID in a session.
        $author_id = 2; // Hardcoded author ID for demonstration.
        // --- END IMPORTANT CHANGE ---

        $published_date = date('Y-m-d H:i:s');

        // Basic data validation
        if (empty($title) || empty($content) || empty($category_id)) {
            $error_message = 'Please fill in all required fields (Title, Content, Category).';
        } else {
            // Check if author_id is valid (not null and greater than 0)
            if ($author_id === null || $author_id <= 0) {
                // This error message will likely not be triggered now because author_id is hardcoded to 2.
                $error_message = 'Error: Author ID not specified correctly.';
            } else {
                $stmt = $conn->prepare("INSERT INTO articles (title, content, image_url, category_id, author_id, published_date) VALUES (?, ?, ?, ?, ?, ?)");

                if ($stmt === false) {
                    error_log("MySQL prepare error: " . $conn->error);
                    $error_message = 'Failed to prepare database statement.';
                } else {
                    $stmt->bind_param("sssiis", $title, $content, $image_url, $category_id, $author_id, $published_date);

                    if ($stmt->execute()) {
                        $success_message = 'Article added successfully!';
                        $_POST = array(); // Clears POST data to empty the form
                    } else {
                        error_log("Error executing statement: " . $stmt->error);
                        $error_message = 'Failed to add article: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}
if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Article - Global News Network</title>
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
                <h1><i class="fas fa-plus"></i> Add New Article</h1>
                <p>Create and publish a new article</p>
            </div>
            <div>
                <a href="manage-articles.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Articles
                </a>
            </div>
        </div>

        <div class="form-container">
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

            <form id="articleForm" method="POST" action=""> 
                <div class="form-group">
                    <label for="title">Article Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Enter article title" 
                                required minlength="10" maxlength="200" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="1" <?php echo (($_POST['category'] ?? '') == '1') ? 'selected' : ''; ?>>Politics</option> 
                            <option value="2" <?php echo (($_POST['category'] ?? '') == '2') ? 'selected' : ''; ?>>Technology</option>
                            <option value="3" <?php echo (($_POST['category'] ?? '') == '3') ? 'selected' : ''; ?>>Sports</option>
                            <option value="4" <?php echo (($_POST['category'] ?? '') == '4') ? 'selected' : ''; ?>>Entertainment</option>
                        </select>
                    </div>
                    
                </div>

                <div class="form-group">
                    <label for="excerpt">Article Excerpt <span class="required">*</span></label>
                    <textarea id="excerpt" name="excerpt" placeholder="Write a brief summary of your article..." 
                                     required minlength="50" maxlength="300"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                    <small style="color: #666;">Brief summary to appear in article previews (50-300 characters)</small>
                </div>

                <div class="form-group">
                    <label for="content">Article Content <span class="required">*</span></label>
                    <textarea id="content" name="content" placeholder="Write your article content here..." 
                                     required minlength="200"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    <small style="color: #666;">Full article content (minimum 200 characters)</small>
                </div>

                <div class="form-group">
                    <label for="imageUrl">Featured Image URL</label>
                    <input type="url" id="imageUrl" name="imageUrl" placeholder="https://example.com/image.jpg"
                           value="<?php echo htmlspecialchars($_POST['imageUrl'] ?? ''); ?>">
                    <small style="color: #666;">Optional: URL for the featured image of your article</small>
                </div>

                <input type="hidden" id="authorId" name="authorId" value="2"> 

                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Article
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="previewArticle()">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // The checkLoginStatus function is now largely irrelevant because author_id is hardcoded in PHP.
        // It's kept here for completeness if you decide to revert to dynamic author IDs later.
        function checkLoginStatus() {
            const userSession = localStorage.getItem('userSession') || sessionStorage.getItem('userSession');
            if (userSession) {
                try {
                    const parsedSession = JSON.parse(userSession);
                    // This line will still try to set the value, but PHP will override it.
                    document.getElementById('authorId').value = parsedSession.userId || ''; 
                } catch (e) {
                    console.error("Error parsing userSession:", e);
                    document.getElementById('authorId').value = '';
                }
            }
            return true;
        }

        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successAlert').style.display = 'block';
            document.getElementById('errorAlert').style.display = 'none';
            window.scrollTo(0, 0);
            setTimeout(hideMessages, 3000); 
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorAlert').style.display = 'block';
            document.getElementById('successAlert').style.display = 'none';
            window.scrollTo(0, 0);
            setTimeout(hideMessages, 5000);
        }

        function hideMessages() {
            document.getElementById('successAlert').style.display = 'none';
            document.getElementById('errorAlert').style.display = 'none';
        }

        function previewArticle() {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();
            
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
                        <strong>Category:</strong> ${document.getElementById('category').options[document.getElementById('category').selectedIndex].text || 'Not Selected'} |
                        <strong>Status:</strong> ${document.getElementById('status').value}
                    </div>
                    <div class="content">
                        ${content.replace(/\n/g, '<br>')}
                    </div>
                </body>
                </html>
            `);
        }

        document.getElementById('articleForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                showError('Please fill in all required fields correctly.');
                return;
            }
        });

        window.onload = function() {
            checkLoginStatus(); // Still calls this, but its effect on authorId is nullified by PHP hardcoding.
            <?php if (!$success_message && !$error_message): ?>
            hideMessages();
            <?php endif; ?>
        };
    </script>
</body>
</html>