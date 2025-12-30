<?php
// Start the session at the top of the file
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Here you would normally fetch user data from the database and verify the password
    // For example: $user = fetch_user_from_db($_POST['username']);
    // and then verify with password_verify($_POST['password'], $user['password_hash']);

    // For now, let's just do a placeholder check:
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Placeholder: if username is "admin" and password is "password", we log them in
    if ($username === 'admin' && $password === 'password') {
        $_SESSION['user'] = $username;
        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <h2>Login</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">Log in</button>
    </form>
</body>
</html>
