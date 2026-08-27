<?php
ob_start();
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username AND role = 'Staff'");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $dbPassword = $user['password'] ?? $user['password_hash'] ?? '';
            $isPasswordValid = password_verify($password, $dbPassword) || ($password === $dbPassword);

            if ($isPasswordValid) {
                ob_clean();
                $_SESSION['user_id']   = $user['user_id'] ?? $user['id'];
                $_SESSION['role']      = 'Staff';
                $_SESSION['full_name'] = $user['full_name'] ?? $user['name'] ?? '';
                header("Location: staff_dashboard.php");
                exit();
            } else {
                $error = "Invalid Staff credentials.";
            }
        } else {
            $error = "Invalid Staff credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMUC Portal - Staff Login</title>
    <link rel="stylesheet" href="style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            position: relative;
            background-color: #032b43;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            font-family: Arial, sans-serif;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-image: url('nmuc_kapal.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.7;
            z-index: 1;
        }

        .login-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            width: 100%;
            max-width: 440px;
            padding: 35px 30px;
            border-radius: 6px;
            border-top: 5px solid #ff5722;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        .brand-title {
            color: #032b43;
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .brand-title span { color: #00a8e8; }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #ff5722;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.88rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h1 class="brand-title">NMUC<span>ID</span></h1>

    <h3 style="margin-bottom: 20px; color: #032b43;">Staff Portal Login</h3>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="form-group">
            <label for="username">Staff Username</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn-login">Sign in as Staff</button>
    </form>
</div>

</body>
</html>