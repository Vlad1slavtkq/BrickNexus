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

$userId = $_SESSION['user_id'];

try {
    // Fetch all active order rows for current user
    $ordersStmt = $pdo->prepare("SELECT id, created_at, delivery_company FROM orders WHERE user_id = ? ORDER BY id DESC");
    $ordersStmt->execute([$userId]);
    $orders = $ordersStmt->fetchAll();

    // Fetch items belonging to these orders
    $itemsStmt = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, p.name, p.color, p.image_url, p.element_id
        FROM order_items oi
        JOIN parts p ON oi.part_id = p.element_id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ?
    ");
    $itemsStmt->execute([$userId]);
    $itemsAll = $itemsStmt->fetchAll();

    $groupedItems = [];
    foreach ($itemsAll as $row) {
        $groupedItems[$row['order_id']][] = [
            'id' => $row['element_id'],
            'name' => $row['name'],
            'color' => $row['color'],
            'qty' => (int)$row['quantity'],
            'img' => $row['image_url']
        ];
    }
} catch (\PDOException $e) {
    die("Orders database system error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - BrickNexus</title>
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

    <main class="flex-grow max-w-4xl w-full mx-auto px-4 py-10 space-y-8">
        
        <div>
            <h1 class="text-3xl font-black tracking-tight text-white">My Parts Orders History</h1>
            <p class="text-gray-400 text-sm mt-1">Review your submitted BrickLink supply pipeline requests and active logistics status tracks.</p>
        </div>

        <div class="space-y-6">
            <?php if (empty($orders)): ?>
                <div class="text-center py-20 border border-dashed border-gray-800 rounded-2xl text-gray-500 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-700 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    You have not placed any element orders yet. Build a parts list inside your dashboard cart to begin.
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $orderId = $order['id'];
                    $courier = htmlspecialchars($order['delivery_company']);
                    $items = isset($groupedItems[$orderId]) ? $groupedItems[$orderId] : [];
                    
                    $badgeStyle = "border-gray-700 text-gray-400 bg-gray-800/20";
                    if ($courier === 'FanCourier') {
                        $badgeStyle = "border-green-500/30 text-green-400 bg-green-500/5";
                    } elseif ($courier === 'Sameday') {
                        $badgeStyle = "border-amber-500/30 text-amber-400 bg-amber-500/5";
                    } elseif ($courier === 'PostRomania') {
                        $badgeStyle = "border-blue-500/30 text-blue-400 bg-blue-500/5";
                    }
                ?>
                    <div class="bg-brickCard border border-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        
                        <div class="bg-gray-900/40 border-b border-gray-800/80 px-6 py-4 flex flex-wrap items-center justify-between gap-4 font-mono text-xs">
                            <div class="flex items-center gap-6">
                                <div>
                                    <span class="text-gray-500 uppercase block tracking-wider">Order Reference</span>
                                    <span class="text-white font-bold text-sm">#BRK-00<?= $orderId ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 uppercase block tracking-wider">Timestamp Date</span>
                                    <span class="text-gray-300 font-medium"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="text-gray-500 uppercase block tracking-wider mb-1">Logistics Carrier</span>
                                <span class="inline-block px-3 py-1 rounded-md text-[11px] font-bold uppercase tracking-widest border <?= $badgeStyle ?>">
                                    <?= $courier ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="text-xs uppercase tracking-wider text-gray-500 font-mono font-bold mb-4">Included Element Manifest (<?= count($items) ?> rows)</div>
                            
                            <div class="space-y-3">
                                <?php if (empty($items)): ?>
                                    <p class="text-xs text-gray-600 italic">No parts rows cataloged inside this transaction entry record.</p>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <div class="flex items-center justify-between p-2.5 bg-brickDark/30 rounded-xl border border-gray-800/40 text-xs">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-white p-0.5 rounded-lg flex items-center justify-center shrink-0 shadow-inner">
                                                    <img src="<?= htmlspecialchars($item['img']) ?>" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/50x50?text=Brick'">
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-300 block"><?= htmlspecialchars($item['name']) ?></span>
                                                    <span class="font-mono text-gray-500 text-[10px]">ID: <?= htmlspecialchars($item['id']) ?> &bull; Color: <?= htmlspecialchars($item['color']) ?></span>
                                                </div>
                                            </div>
                                            <div class="font-mono text-brickAccent font-bold text-sm bg-brickSecondary px-3 py-1 rounded-md border border-gray-700/30">
                                                <?= $item['qty'] ?><span class="text-[10px] text-gray-500 font-sans ml-0.5">x</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <footer class="bg-brickCard border-t border-gray-800 py-6 text-center text-xs text-gray-500">
        <p>&copy; 2026 BrickNexus Engine. Logistical Supply Infrastructure Maps.</p>
    </footer>

</body>
</html>