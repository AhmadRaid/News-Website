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

    // --- Handle POST request ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $category_id = isset($_POST['category']) ? (int)$_POST['category'] : null;
        
        $author_id = 2; // Hardcoded author ID
        $published_date = date('Y-m-d H:i:s');

        // Initialize image URL
        $image_url = '';

        // Process uploaded file
        if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] == UPLOAD_ERR_OK) {
            // Create upload directory if not exists
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $error_message = 'Failed to create upload directory.';
                }
            }
            
            // Get file info
            $file_name = $_FILES['imageUpload']['name'];
            $file_tmp = $_FILES['imageUpload']['tmp_name'];
            $file_size = $_FILES['imageUpload']['size'];
            $file_error = $_FILES['imageUpload']['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Validate file
            if (in_array($file_ext, $allowed)) {
                if ($file_error === 0) {
                    if ($file_size <= 5 * 1024 * 1024) { // 5MB max
                        // Generate unique filename
                        $new_file_name = uniqid('', true) . '.' . $file_ext;
                        $file_destination = $upload_dir . $new_file_name;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $file_destination)) {
                            $image_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . 
                                         $_SERVER['HTTP_HOST'] . '/' . $file_destination;
                        } else {
                            $error_message = 'Failed to move uploaded file.';
                        }
                    } else {
                        $error_message = 'File size is too large (max 5MB).';
                    }
                } else {
                    $error_message = 'Error uploading file.';
                }
            } else {
                $error_message = 'Invalid file type. Only JPG, JPEG, PNG & GIF are allowed.';
            }
        } else {
            $error_message = 'Please select an image to upload.';
        }

        // Basic data validation
        if (empty($title) || empty($content) || empty($category_id)) {
            $error_message = 'Please fill in all required fields (Title, Content, Category).';
        }

        // If no errors, proceed with database insertion
        if (empty($error_message)) {
            $stmt = $conn->prepare("INSERT INTO articles (title, content, image_url, category_id, author_id, published_date) VALUES (?, ?, ?, ?, ?, ?)");

            if ($stmt === false) {
                error_log("MySQL prepare error: " . $conn->error);
                $error_message = 'Failed to prepare database statement.';
            } else {
                $stmt->bind_param("sssiis", $title, $content, $image_url, $category_id, $author_id, $published_date);

                if ($stmt->execute()) {
                    $success_message = 'Article added successfully!';
                    $_POST = array(); // Clear form data
                } else {
                    error_log("Error executing statement: " . $stmt->error);
                    $error_message = 'Failed to add article: ' . $stmt->error;
                }
                $stmt->close();
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
        
        #imagePreview {
            max-width: 100%;
            margin-top: 10px;
            display: none;
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

            <form id="articleForm" method="POST" action="" enctype="multipart/form-data"> 
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
                    <label for="content">Article Content <span class="required">*</span></label>
                    <textarea id="content" name="content" placeholder="Write your article content here..." 
                                     required minlength="200"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    <small style="color: #666;">Full article content (minimum 200 characters)</small>
                </div>

                <div class="form-group">
                    <label for="imageUpload">Featured Image <span class="required">*</span></label>
                    <input type="file" id="imageUpload" name="imageUpload" accept="image/jpeg, image/png, image/gif" required>
                    <small style="color: #666;">Select an image to upload (JPG, PNG, GIF - max 5MB)</small>
                    <img id="imagePreview" src="#" alt="Image Preview" style="max-width: 300px; display: none; margin-top: 10px;">
                </div>

                <input type="hidden" id="authorId" name="authorId" value="2"> 

                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Article
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview image when selected
        document.getElementById('imageUpload').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('imageUpload');
            
            if (fileInput.files.length === 0) {
                alert('Please select an image to upload');
                e.preventDefault();
                return false;
            }
            
            return true;
        });

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

        window.onload = function() {
            <?php if (!$success_message && !$error_message): ?>
            hideMessages();
            <?php endif; ?>
        };
    </script>
</body>
</html>