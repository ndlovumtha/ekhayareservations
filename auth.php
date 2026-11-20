<?php
require 'config.php';

$error = '';
$success = '';

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $role      = $_POST['role'] === 'host' ? 'host' : 'guest';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $hash, $role]);
            $success = "Account created successfully! Please log in below.";
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']          = $user['user_id'];
        $_SESSION['full_name']        = $user['full_name'];
        $_SESSION['role']             = $user['role'];
        $_SESSION['is_verified_safe'] = $user['is_verified_safe'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'host') {
            header("Location: host_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login / Sign Up | Ekhaya Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-100 text-stone-800">

<nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-amber-800">Ekhaya Reservations</a>
    <a href="index.php" class="text-sm text-stone-600 hover:text-amber-800">← Back to Stays</a>
</nav>

<div class="max-w-5xl mx-auto py-12 px-4">
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded mb-6"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-8">
        <!-- Login Form -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-stone-200">
            <h2 class="text-2xl font-bold mb-6">Log In to Ekhaya</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address</label>
                    <input type="email" name="email" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <button type="submit" name="login" class="w-full bg-amber-800 hover:bg-amber-900 text-white font-semibold py-2.5 rounded-lg">
                    Log In
                </button>
            </form>
           <!-- <p class="text-xs text-stone-500 mt-4">Admin Demo: <strong>admin@ekhaya.co.zw</strong> / <strong>password123</strong></p> -->
        </div>

        <!-- Registration Form -->
        <div class="bg-white p-8 rounded-xl shadow-sm border border-stone-200">
            <h2 class="text-2xl font-bold mb-6">Create an Account</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">I am signing up as a:</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="border rounded-lg p-3 flex items-center gap-2 cursor-pointer hover:bg-amber-50">
                            <input type="radio" name="role" value="guest" checked class="accent-amber-800">
                            <div>
                                <div class="font-semibold text-sm">Guest</div>
                                <div class="text-xs text-stone-500">Book hotels, lodges & homes</div>
                            </div>
                        </label>
                        <label class="border rounded-lg p-3 flex items-center gap-2 cursor-pointer hover:bg-amber-50">
                            <input type="radio" name="role" value="host" class="accent-amber-800">
                            <div>
                                <div class="font-semibold text-sm">Host</div>
                                <div class="text-xs text-stone-500">List my property & manage stays</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Full Name / Business Name</label>
                    <input type="text" name="full_name" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address</label>
                    <input type="email" name="email" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone Number</label>
                    <input type="text" name="phone" required placeholder="+263 77..." class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <button type="submit" name="register" class="w-full bg-stone-900 hover:bg-stone-800 text-white font-semibold py-2.5 rounded-lg">
                    Register Account
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>