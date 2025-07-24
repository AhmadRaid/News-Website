<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Simple Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Very simple CSS for students to understand */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1000px;
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
        
        .add-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .categories-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 80px;
            resize: vertical;
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
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .edit-form {
            background: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        
        /* Make it work on phones */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 5px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php
    // STUDENT NOTE: This is where you would handle all category operations
    // For now, we use simple arrays that students can understand
    
    $success_message = '';
    $error_message = '';
    $edit_category = null;
    
    // Fake categories data (students will replace with database later)
    $categories = array(
        array('id' => 1, 'name' => 'Politics', 'description' => 'Political news and government updates'),
        array('id' => 2, 'name' => 'Technology', 'description' => 'Latest tech news and innovations'),
        array('id' => 3, 'name' => 'Sports', 'description' => 'Sports news, scores, and updates'),
        array('id' => 4, 'name' => 'Entertainment', 'description' => 'Movies, music, and celebrity news'),
        array('id' => 5, 'name' => 'Business', 'description' => 'Business and financial news'),
        array('id' => 6, 'name' => 'Health', 'description' => 'Health and medical news'),
        array('id' => 7, 'name' => 'Science', 'description' => 'Scientific discoveries and research'),
        array('id' => 8, 'name' => 'World', 'description' => 'International news and events')
    );
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // ADD NEW CATEGORY
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['category_name']);
            $description = trim($_POST['category_description']);
            
            if (empty($name)) {
                $error_message = 'Category name is required';
            } else {
                // STUDENT NOTE: Here you would insert into database
                // INSERT INTO categories (category_name, description) VALUES (?, ?)
                $success_message = 'Category "' . $name . '" would be added! (Connect to database)';
            }
        }
        
        // UPDATE CATEGORY
        if (isset($_POST['update_category'])) {
            $id = (int)$_POST['category_id'];
            $name = trim($_POST['category_name']);
            $description = trim($_POST['category_description']);
            
            if (empty($name)) {
                $error_message = 'Category name is required';
            } else {
                // STUDENT NOTE: Here you would update database
                // UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?
                $success_message = 'Category "' . $name . '" would be updated! (Connect to database)';
            }
        }
        
        // DELETE CATEGORY
        if (isset($_POST['delete_category'])) {
            $id = (int)$_POST['category_id'];
            
            // STUDENT NOTE: Here you would delete from database
            // DELETE FROM categories WHERE category_id = ?
            $success_message = 'Category would be deleted! (Connect to database)';
        }
    }
    
    // Handle edit request
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $edit_id = (int)$_GET['edit'];
        
        // Find category to edit (in real app, get from database)
        for ($i = 0; $i < count($categories); $i++) {
            if ($categories[$i]['id'] == $edit_id) {
                $edit_category = $categories[$i];
                break;
            }
        }
    }
    ?>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-tags"></i> Manage Categories</h1>
            <p>Add, edit, and delete news categories</p>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Show messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form (only show when editing) -->
        <?php if ($edit_category): ?>
            <div class="edit-form">
                <h3><i class="fas fa-edit"></i> Edit Category</h3>
                <form method="POST">
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    
                    <div class="form-group">
                        <label for="edit_name">Category Name *</label>
                        <input type="text" id="edit_name" name="category_name" value="<?php echo htmlspecialchars($edit_category['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="category_description" placeholder="Optional description"><?php echo htmlspecialchars($edit_category['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="update_category" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Category
                        </button>
                        <a href="categories.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Add New Category Form -->
        <div class="add-form">
            <h3><i class="fas fa-plus"></i> Add New Category</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" placeholder="Enter category name..." required>
                </div>
                
                <div class="form-group">
                    <label for="category_description">Description</label>
                    <textarea id="category_description" name="category_description" placeholder="Optional description of this category"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_category" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <button type="button" onclick="clearForm()" class="btn btn-secondary">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="categories-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Articles Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Simple loop to show each category
                    for ($i = 0; $i < count($categories); $i++) {
                        $category = $categories[$i];
                        
                        // Fake article count (students would get real count from database)
                        $article_count = rand(5, 25);
                        
                        echo '<tr>';
                        echo '<td>' . $category['id'] . '</td>';
                        echo '<td><strong>' . htmlspecialchars($category['name']) . '</strong></td>';
                        echo '<td>' . htmlspecialchars($category['description']) . '</td>';
                        echo '<td>' . $article_count . ' articles</td>';
                        echo '<td>';
                        echo '<a href="categories.php?edit=' . $category['id'] . '" class="btn btn-warning">Edit</a>';
                        echo '<button onclick="deleteCategory(' . $category['id'] . ', \'' . htmlspecialchars($category['name']) . '\')" class="btn btn-danger">Delete</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Category Statistics -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3><i class="fas fa-chart-bar"></i> Category Statistics</h3>
            <p><strong>Total Categories:</strong> <?php echo count($categories); ?></p>
            <p><strong>Most Popular:</strong> Technology (25 articles)</p>
            <p><strong>Least Popular:</strong> Science (5 articles)</p>
        </div>
    </div>

    <script>
        // Simple function to clear the add form
        function clearForm() {
            document.getElementById('category_name').value = '';
            document.getElementById('category_description').value = '';
        }
        
        // Simple delete function
        function deleteCategory(id, name) {
            if (confirm('Are you sure you want to delete the category "' + name + '"?\n\nThis will also affect all articles in this category!')) {
                // Create a hidden form to submit delete request
                var form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'category_id';
                input.value = id;
                form.appendChild(input);
                
                var deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_category';
                deleteInput.value = '1';
                form.appendChild(deleteInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Simple form validation
        function validateForm() {
            var name = document.getElementById('category_name').value;
            
            if (name.trim() === '') {
                alert('Please enter a category name');
                return false;
            }
            
            if (name.length < 2) {
                alert('Category name must be at least 2 characters long');
                return false;
            }
            
            return true;
        }
        
        // Add validation to add form
        document.querySelector('form').addEventListener('submit', function(e) {
            if (e.target.querySelector('input[name="add_category"]')) {
                if (!validateForm()) {
                    e.preventDefault();
                }
            }
        });
        
        // Show success message when page loads
        console.log('Categories page loaded with <?php echo count($categories); ?> categories');
        
        // Auto-focus on category name input
        document.getElementById('category_name').focus();
    </script>
</body>
</html>
