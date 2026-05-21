<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$setId = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($setId)) {
    die("<h1>Error: LEGO Set ID is missing.</h1><a href='index.php'>Back</a>");
}

try {
    // Get target lego model entry information
    $stmt = $pdo->prepare("SELECT m.*, c.name AS category_name FROM mocs m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id = ?");
    $stmt->execute([$setId]);
    $moc = $stmt->fetch();

    if (!$moc) {
        die("<h1>Error: LEGO Set ID '" . htmlspecialchars($setId) . "' not found.</h1><a href='index.php'>Back</a>");
    }

    // Get parts mapped for this specific lego model
    $partsStmt = $pdo->prepare("
        SELECT mp.quantity, p.element_id, p.name, p.color, p.image_url 
        FROM moc_parts mp 
        JOIN parts p ON mp.part_id = p.element_id 
        WHERE mp.moc_id = ?
    ");
    $partsStmt->execute([$setId]);
    $mocParts = $partsStmt->fetchAll();

} catch (\PDOException $e) {
    die("Database fetch failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($moc['title']) ?> - Details - BrickNexus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brickDark: '#0b0f19',
                        brickCard: '#131c2e',
                        brickAccent: '#f59e0b',
                        brickSecondary: '#1f293d'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brickDark text-gray-100 font-sans min-h-screen flex flex-col antialiased">

    <nav class="bg-brickCard/80 backdrop-blur-md border-b border-gray-800 px-6 py-4 flex items-center justify-between sticky top-0 z-50">
        <a href="index.php" class="bg-gradient-to-r from-amber-500 to-brickAccent text-brickDark font-black px-3 py-1 rounded-lg text-xl tracking-wider font-mono">BRICKNEXUS</a>
        <div class="flex items-center space-x-6 text-sm">
            <a href="index.php" class="text-gray-400 hover:text-brickAccent transition">&larr; Back to Dashboard</a>
        </div>
    </nav>

    <main class="flex-grow max-w-5xl w-full mx-auto px-4 py-10 space-y-8">
        
        <div class="text-xs font-mono text-gray-500 tracking-wide">
            MOCs &rarr; <span class="text-gray-400"><?= htmlspecialchars($moc['category_name'] ?? 'LEGO') ?></span> &rarr; <span class="text-brickAccent"><?= htmlspecialchars($moc['title']) ?></span>
        </div>

        <div class="bg-brickCard border border-gray-800 rounded-3xl p-6 md:p-8 flex flex-col md:flex-row gap-8 shadow-2xl items-center md:items-start">
            
            <div class="w-full md:w-80 h-64 bg-gray-900 rounded-2xl overflow-hidden border border-gray-800 shrink-0 flex items-center justify-center p-2">
                <img src="<?= htmlspecialchars($moc['image_url']) ?>" alt="LEGO Set Visual" class="max-h-full max-w-full object-contain rounded-xl" onerror="this.src='https://placehold.co/400x300?text=Lego+Set'">
            </div>

            <div class="flex-grow flex flex-col justify-between h-full space-y-6">
                <div>
                    <span class="text-xs font-mono bg-brickAccent/10 text-brickAccent px-3 py-1 rounded-md font-bold uppercase tracking-wider">
                        SET CODE: <?= htmlspecialchars($moc['id']) ?>
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black text-white mt-3 tracking-tight leading-tight">
                        <?= htmlspecialchars($moc['title']) ?>
                    </h1>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-6 pt-4 border-t border-gray-800/60">
                    <div class="flex gap-4">
                        <div class="bg-brickDark border border-gray-800 px-4 py-2 rounded-xl text-center">
                            <span class="block text-[10px] text-gray-500 font-mono uppercase">Total Parts</span>
                            <span class="font-mono font-bold text-base text-brickAccent"><?= $moc['parts_count'] ?> pcs</span>
                        </div>
                        <div class="bg-brickDark border border-gray-800 px-4 py-2 rounded-xl text-center">
                            <span class="block text-[10px] text-gray-500 font-mono uppercase">Designer</span>
                            <span class="font-bold text-sm text-gray-200"><?= htmlspecialchars($moc['author']) ?></span>
                        </div>
                    </div>

                    <a href="https://www.lego.com/en-us/service/buildinginstructions/<?= urlencode($moc['id']) ?>" target="_blank" class="bg-red-500 hover:bg-red-600 text-white font-bold px-6 py-3 rounded-xl text-xs uppercase tracking-wider transition flex items-center gap-2 shadow-lg shadow-red-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download PDF Instructions
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="text-lg font-bold tracking-wide flex items-center gap-2">
                <span>Parts Inventory Breakdown</span>
                <span class="text-xs bg-brickSecondary px-2 py-0.5 rounded text-gray-400 font-mono"><?= count($mocParts) ?> unique items</span>
            </h2>

            <div class="bg-brickCard border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-800 text-xs text-gray-400 uppercase tracking-wider bg-gray-900/40 font-mono">
                            <th class="p-4 pl-6 w-24">Image</th>
                            <th class="p-4">Part Name / Element ID</th>
                            <th class="p-4">Color Spec</th>
                            <th class="p-4 text-right pr-6 w-32">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800 text-sm">
                        <?php if (empty($mocParts)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-500 text-xs italic">
                                    No compound element pieces are mapped to this model entry inside MySQL yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mocParts as $part): ?>
                                <tr class="hover:bg-gray-800/10 transition duration-100">
                                    <td class="p-4 pl-6">
                                        <div class="w-12 h-12 bg-white p-1 rounded-lg flex items-center justify-center shadow-inner">
                                            <img src="<?= htmlspecialchars($part['image_url']) ?>" alt="Part" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/50x50?text=Brick'">
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="block font-bold text-gray-200"><?= htmlspecialchars($part['name']) ?></span>
                                        <span class="text-xs font-mono text-gray-500">ID: <?= htmlspecialchars($part['element_id']) ?></span>
                                    </td>
                                    <td class="p-4 text-gray-400 font-medium"><?= htmlspecialchars($part['color']) ?></td>
                                    <td class="p-4 text-right pr-6 font-mono font-bold text-brickAccent text-base">
                                        <?= $part['quantity'] ?><span class="text-xs text-gray-600 font-sans ml-0.5">x</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="bg-brickCard border-t border-gray-800 py-6 text-center text-xs text-gray-500">
        <p>&copy; 2026 BrickNexus Engine. Built for Examination.</p>
    </footer>

</body>
</html>