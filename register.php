<?php
require_once 'db.php';
$message = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Hash password securely before database entry
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $message = "Account created successfully! You can now login.";
        } catch (\PDOException $e) {
            $error = "Username or Email already exists!";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BrickNexus - Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f172a] text-gray-100 font-sans min-h-screen flex items-center justify-center p-4">

    <div class="bg-[#1e293b] border border-gray-700/60 p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <h2 class="text-2xl font-black text-center text-white mb-2 tracking-tight">Create Account</h2>
        <p class="text-xs text-center text-gray-400 mb-6 uppercase tracking-widest font-semibold">Join BrickNexus platform</p>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm p-3 rounded-xl mb-4 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 text-sm p-3 rounded-xl mb-4 text-center">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs uppercase text-gray-400 mb-1 font-semibold">Username</label>
                <input type="text" name="username" required class="w-full bg-[#0f172a] border border-gray-600 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#f59e0b]">
            </div>
            <div>
                <label class="block text-xs uppercase text-gray-400 mb-1 font-semibold">Email</label>
                <input type="email" name="email" required class="w-full bg-[#0f172a] border border-gray-600 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#f59e0b]">
            </div>
            <div>
                <label class="block text-xs uppercase text-gray-400 mb-1 font-semibold">Password</label>
                <input type="password" name="password" required class="w-full bg-[#0f172a] border border-gray-600 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#f59e0b]">
            </div>
            <button type="submit" class="w-full bg-[#f59e0b] hover:bg-amber-500 text-[#0f172a] font-bold py-2.5 rounded-xl transition mt-2">Register Account</button>
        </form>

        <p class="text-sm text-center text-gray-400 mt-6">
            Already have an account? <a href="login.php" class="text-[#f59e0b] hover:underline font-semibold">Login here</a>
        </p>
    </div>

</body>
</html>