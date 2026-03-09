<?php
session_start();
require_once("includes/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    // ✅ Basic validation
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // ✅ Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // ✅ Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // ✅ Insert new user (always student)
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?, 'student')");
            if (!$stmt) {
                die("SQL error: " . $conn->error);
            }
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                // ✅ Auto login after registration
                $_SESSION['user_id']   = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role']      = "student";

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
            color: #fff;
        }
        .register-container h2 {
            margin-bottom: 20px;
        }
        .register-container label {
            display: block;
            text-align: left;
            margin-bottom: 6px;
            font-size: 14px;
            color: #f1f1f1;
        }
        .register-container input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: none;
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 14px;
        }
        .register-container input::placeholder {
            color: #ddd;
        }
        .register-container button {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
        }
        .register-container button:hover {
            background: #219150;
        }
        .error {
            color: #ffbaba;
            background: #ff4d4d33;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register as Student</h2>
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST">
            <label>Full Name:</label>
            <input type="text" name="name" placeholder="Enter full name" required>

            <label>Email:</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>

            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
