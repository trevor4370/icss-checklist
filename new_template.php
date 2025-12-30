<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Here you would handle form submission and save the new template to the database.
    // For now, just a placeholder action.
    $templateName = $_POST['template_name'];
    echo "Template '$templateName' created!";
    // In a real scenario, you'd redirect back to the templates list after saving.
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Template</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <h2>Create a New Template</h2>
    <nav>
        <a href="templates.php">Back to Templates</a>
    </nav>

    <form method="post" action="new_template.php">
        <label for="template_name">Template Name:</label>
        <input type="text" id="template_name" name="template_name" required>
        <button type="submit">Create Template</button>
    </form>
</body>
</html>
