<?php
session_start();

// ---- Database connection ----
// ඔබේ db credentials දාන්න (host, dbname, user, password)
$pdo = new PDO("mysql:host=localhost;dbname=equipment_system;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = "";
$message = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid Username or Password!";
        }

    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Equipment System</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
        }

        .login-box {
            width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-box h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
        }

        .login-box p {
            color: #cbd5e1;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: white;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 13px 15px;
            border: none;
            border-radius: 10px;
            outline: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 15px;
        }

        input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.4);
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fecaca;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid #22c55e;
            color: #bbf7d0;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="login-box">

        <h1>Equipment System</h1>

        <p>Sign in to continue</p>

        <?php if (!empty($message)): ?>
            <div class="success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="username">Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    required
                >
            </div>

            <button type="submit" name="login">
                Login
            </button>

        </form>

    </div>

</body>
</html>
