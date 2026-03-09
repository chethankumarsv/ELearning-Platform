<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Navbar */
        header {
            background: linear-gradient(90deg, #4facfe, #00f2fe);
            padding: 15px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #fff;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        header h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
        }
        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 30px;
        }
        nav ul li {
            display: inline;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        nav ul li a:hover {
            color: #ffeb3b;
        }
    </style>
</head>
<body>

<header>
    <h1>E-Learning Admin</h1>
    <nav>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="course.php">Courses</a></li>
            <li><a href="quize.php">Quizzes</a></li>
            <li><a href="certificate.php">Certificates</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>
