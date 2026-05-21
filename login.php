<?php
require_once 'db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password!";
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
    <title>BrickNexus - Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-gray-100 font-sans min-h-screen flex items-center justify-center p-4">

    <div class="bg-[#1e293b] border border-gray-700 p-8 rounded-2xl max-w-md w-full shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-block bg-[#f59e0b] text-[#0f172a] font-black px-4 py-1 rounded text-2xl tracking-wider mb-3">
                BRICKNEXUS
            </div>
            <h2 class="text-xl text-gray-300 font-semibold">Welcome back, Builder!</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm p-3 rounded-xl mb-4 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-xs uppercase tracking-wider text-gray-400 mb-2 font-semibold">Email Address</label>
                <input type="email" name="email" required class="w-full bg-[#0f172a] border border-gray-600 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#f59e0b] text-gray-200">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-gray-400 mb-2 font-semibold">Password</label>
                <input type="password" name="password" required class="w-full bg-[#0f172a] border border-gray-600 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#f59e0b] text-gray-200">
            </div>
            <button type="submit" class="w-full bg-[#f59e0b] hover:bg-amber-500 text-[#0f172a] font-bold py-3 rounded-xl transition shadow-lg text-sm uppercase tracking-widest mt-2">
                Sign In
            </button>
        </form>

        <p class="text-xs text-center text-gray-500 mt-6">
            Don't have an account? <a href="register.php" class="text-[#f59e0b] hover:underline">Create one here</a>.
        </p>
    </div>

</body>
</html>