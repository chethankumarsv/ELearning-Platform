<?php
session_start();
require_once("includes/config.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check_sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        $insert_sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            header("Location: index.php");
            exit();
        } else {
            $error = "Something went wrong!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Signup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .signup-container { max-width: 400px; margin: 80px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0px 0px 10px #ccc; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 12px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 12px; background: #007bff; border: none; border-radius: 5px; color: white; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Create Account</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign Up</button>
        </form>
        <p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
