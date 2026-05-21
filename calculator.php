<?php
// calculator.php 
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

if (isset($_GET['action']) && $_GET['action'] === 'update_inventory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $partId = isset($_POST['part_id']) ? trim($_POST['part_id']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if (empty($partId) || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid structural input data.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_inventory (user_id, part_id, quantity) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ");
        $stmt->execute([$userId, $partId, $quantity]);
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$selectedMocId = isset($_GET['moc_id']) ? trim($_GET['moc_id']) : '';

try {
    // Fetch all available sets for the selector menu
    $mocsStmt = $pdo->query("SELECT id, title, parts_count FROM mocs ORDER BY title ASC");
    $allMocs = $mocsStmt->fetchAll();

    $calculationResults = [];
    $matchingPercentage = 0;
    $totalMissingPieces = 0;
    $targetMoc = null;

    if (!empty($selectedMocId)) {
        $mocMetaStmt = $pdo->prepare("SELECT * FROM mocs WHERE id = ?");
        $mocMetaStmt->execute([$selectedMocId]);
        $targetMoc = $mocMetaStmt->fetch();

        if ($targetMoc) {
            // Fetch required parts
            $reqStmt = $pdo->prepare("
                SELECT mp.part_id, mp.quantity AS req_qty, p.name, p.color, p.image_url 
                FROM moc_parts mp 
                JOIN parts p ON mp.part_id = p.element_id 
                WHERE mp.moc_id = ?
            ");
            $reqStmt->execute([$selectedMocId]);
            $requiredParts = $reqStmt->fetchAll();

            // Fetch owned parts map vector
            $invStmt = $pdo->prepare("SELECT part_id, quantity AS owned_qty FROM user_inventory WHERE user_id = ?");
            $invStmt->execute([$userId]);
            $userInventory = $invStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $totalRequiredPieces = 0;
            $totalMatchedPieces = 0;

            foreach ($requiredParts as $part) {
                $pId = $part['part_id'];
                $reqQty = (int)$part['req_qty'];
                $ownedQty = isset($userInventory[$pId]) ? (int)$userInventory[$pId] : 0;
                
                $totalRequiredPieces += $reqQty;
                $matchedQty = min($reqQty, $ownedQty);
                $totalMatchedPieces += $matchedQty;

                $missingQty = $reqQty - $matchedQty;
                if ($missingQty > 0) {
                    $totalMissingPieces += $missingQty;
                }

                if ($ownedQty >= $reqQty) {
                    $status = 'FULLMATCH';
                } elseif ($ownedQty > 0) {
                    $status = 'PARTIAL';
                } else {
                    $status = 'MISSING';
                }

                $calculationResults[] = [
                    'id' => $pId,
                    'name' => $part['name'],
                    'color' => $part['color'],
                    'img' => $part['image_url'],
                    'req_qty' => $reqQty,
                    'owned_qty' => $ownedQty,
                    'missing_qty' => $missingQty,
                    'status' => $status
                ];
            }

            if ($totalRequiredPieces > 0) {
                $matchingPercentage = round(($totalMatchedPieces / $totalRequiredPieces) * 100);
            }
        }
    }
} catch (\PDOException $e) {
    die("Inventory systems core failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Calculator - BrickNexus</title>
    <link rel="stylesheet" href="style.css">
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
    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-brickDark text-gray-100 font-sans min-h-screen flex flex-col antialiased">

    <nav class="bg-brickCard/80 backdrop-blur-md border-b border-gray-800 px-6 py-4 flex items-center justify-between sticky top-0 z-50">
        <a href="index.php" class="bg-gradient-to-r from-amber-500 to-brickAccent text-brickDark font-black px-3 py-1 rounded-lg text-xl tracking-wider font-mono">BRICKNEXUS</a>
        <div class="flex items-center space-x-6 text-sm">
            <a href="index.php" class="text-gray-400 hover:text-brickAccent transition">&larr; Back to Dashboard</a>
        </div>
    </nav>

    <main class="flex-grow max-w-5xl w-full mx-auto px-4 py-10 space-y-8">
        
        <div>
            <h1 class="text-3xl font-black tracking-tight text-white">MOC Build Match Calculator</h1>
            <p class="text-gray-400 text-sm mt-1">Manage your private stock inventory items directly inside the blueprint breakdown table matrix list.</p>
        </div>

        <div class="bg-brickCard border border-gray-800 rounded-2xl p-6 shadow-xl">
            <form method="GET" action="calculator.php" class="flex flex-col sm:flex-row items-end gap-4">
                <div class="flex-grow w-full">
                    <label class="block text-xs uppercase tracking-wider font-mono font-bold text-gray-400 mb-2">Target LEGO Model Selection</label>
                    <select name="moc_id" class="w-full bg-brickDark border border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-brickAccent font-medium">
                        <option value="">-- Choose a LEGO Model From Live Catalog --</option>
                        <?php foreach ($allMocs as $m): ?>
                            <option value="<?= htmlspecialchars($m['id']) ?>" <?= $selectedMocId === $m['id'] ? 'selected' : '' ?>>
                                [Set <?= htmlspecialchars($m['id']) ?>] <?= htmlspecialchars($m['title']) ?> (<?= $m['parts_count'] ?> pcs)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full sm:w-auto bg-brickAccent hover:bg-amber-500 text-brickDark font-black px-8 py-3 rounded-xl text-xs uppercase tracking-widest transition shrink-0">
                    Calculate Match
                </button>
            </form>
        </div>

        <?php if ($targetMoc): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-brickCard border border-gray-800 rounded-2xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <span class="block text-xs uppercase font-mono tracking-wider text-gray-400">Inventory Match</span>
                        <span class="block text-3xl font-black text-white mt-1"><?= $matchingPercentage ?>%</span>
                    </div>
                    <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center font-mono font-bold text-sm <?= $matchingPercentage > 70 ? 'border-green-500 text-green-400' : ($matchingPercentage > 30 ? 'border-amber-500 text-amber-400' : 'border-red-500 text-red-400') ?>">
                        <?= $matchingPercentage ?>%
                    </div>
                </div>

                <div class="bg-brickCard border border-gray-800 rounded-2xl p-6 flex items-center justify-between shadow-xl">
                    <div>
                        <span class="block text-xs uppercase font-mono tracking-wider text-gray-400">Missing Elements</span>
                        <span class="block text-3xl font-black text-red-400 mt-1"><?= $totalMissingPieces ?> pcs</span>
                    </div>
                    <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                </div>

                <div class="bg-brickCard border border-gray-800 rounded-2xl p-6 flex flex-col justify-center shadow-xl">
                    <?php if ($totalMissingPieces > 0): ?>
                        <button onclick="addAllMissingToCart()" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider transition text-center shadow-md">
                            Add All Missing Parts to Cart
                        </button>
                    <?php else: ?>
                        <div class="text-center text-xs font-bold text-green-400 border border-green-500/30 bg-green-500/5 py-3 rounded-xl font-mono uppercase tracking-wider">
                            🎉 Ready to Build! 100% Owned
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-xs font-bold tracking-wide text-gray-400 uppercase font-mono">Compound Parts Cross-Reference Breakdown Table</h2>
                <div class="bg-brickCard border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-800 text-xs text-gray-400 uppercase tracking-wider bg-gray-900/40 font-mono">
                                <th class="p-4 pl-6 w-20">Image</th>
                                <th class="p-4">Element / BrickLink Code</th>
                                <th class="p-4 text-center w-28">Required</th>
                                <th class="p-4 text-center w-40">In Storage (Edit Live)</th>
                                <th class="p-4 text-center w-28">Shortage</th>
                                <th class="p-4 text-right pr-6 w-32">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800 text-sm font-medium">
                            <?php foreach ($calculationResults as $item): ?>
                                <tr class="hover:bg-gray-800/10 transition duration-100">
                                    <td class="p-4 pl-6">
                                        <div class="w-10 h-10 bg-white p-1 rounded-lg flex items-center justify-center shadow-inner">
                                            <img src="<?= htmlspecialchars($item['img']) ?>" alt="Brick" class="max-h-full max-w-full object-contain" onerror="this.src='https://placehold.co/50x50?text=Lego'">
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="block font-bold text-gray-200"><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="text-xs font-mono text-gray-500">ID: <?= htmlspecialchars($item['id']) ?> &bull; <?= htmlspecialchars($item['color']) ?></span>
                                    </td>
                                    <td class="p-4 text-center font-mono text-gray-300"><?= $item['req_qty'] ?></td>
                                    
                                    <td class="p-4 text-center">
                                        <div class="flex items-center bg-brickSecondary border border-gray-700 rounded-lg overflow-hidden h-8 w-28 mx-auto shadow-inner">
                                            <button type="button" onclick="adjustInventory('<?= $item['id'] ?>', -1)" class="px-2.5 text-gray-400 hover:bg-gray-700 hover:text-white transition font-bold text-sm">-</button>
                                            <input type="number" id="inv-<?= $item['id'] ?>" value="<?= $item['owned_qty'] ?>" onchange="saveInventory('<?= $item['id'] ?>', this.value)" class="w-full bg-transparent text-center text-xs font-mono font-bold text-brickAccent focus:outline-none" min="0">
                                            <button type="button" onclick="adjustInventory('<?= $item['id'] ?>', 1)" class="px-2.5 text-gray-400 hover:bg-gray-700 hover:text-white transition font-bold text-sm">+</button>
                                        </div>
                                    </td>

                                    <td class="p-4 text-center font-mono <?= $item['missing_qty'] > 0 ? 'text-red-400 font-bold' : 'text-gray-600' ?>"><?= $item['missing_qty'] ?></td>
                                    <td class="p-4 text-right pr-6">
                                        <?php if ($item['status'] === 'FULLMATCH'): ?>
                                            <span class="inline-block px-2.5 py-1 text-[10px] uppercase font-mono font-bold tracking-wider rounded-md border border-green-500/30 text-green-400 bg-green-500/5">Full Match</span>
                                        <?php elseif ($item['status'] === 'PARTIAL'): ?>
                                            <span class="inline-block px-2.5 py-1 text-[10px] uppercase font-mono font-bold tracking-wider rounded-md border border-amber-500/30 text-amber-400 bg-amber-500/5">Partial</span>
                                        <?php else: ?>
                                            <span class="inline-block px-2.5 py-1 text-[10px] uppercase font-mono font-bold tracking-wider rounded-md border border-red-500/30 text-red-400 bg-red-500/5">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-20 border border-dashed border-gray-800 rounded-2xl text-gray-500 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-700 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Please choose a LEGO model set layout from the upper field selector dropdown menu to run comparison operations matrices profiles.
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-brickCard border-t border-gray-800 py-6 text-center text-xs text-gray-500">
        <p>&copy; 2026 BrickNexus Engine. Built for Examination Logistics.</p>
    </footer>

    <script>
        const missingPartsList = <?php echo json_encode($calculationResults); ?> || [];

        // + -
        function adjustInventory(partId, change) {
            const input = document.getElementById('inv-' + partId);
            if (!input) return;
            let currentVal = parseInt(input.value) || 0;
            let newVal = currentVal + change;
            if (newVal < 0) newVal = 0;
            input.value = newVal;
            saveInventory(partId, newVal);
        }

    
        function saveInventory(partId, value) {
            let qty = parseInt(value);
            if (isNaN(qty) || qty < 0) qty = 0;

        
            const formData = new URLSearchParams();
            formData.append('part_id', partId);
            formData.append('quantity', qty);

            fetch('calculator.php?action=update_inventory', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error updating user inventory database: ' + data.message);
                }
            })
            .catch(error => {
                console.error('AJAX sync failed:', error);
            });
        }

        function addAllMissingToCart() {
            let localCart = JSON.parse(localStorage.getItem('bricknexus_cart')) || [];
            let addedCount = 0;

            missingPartsList.forEach(item => {
                if (item.missing_qty > 0) {
                    let existingItem = localCart.find(cartItem => String(cartItem.id).toLowerCase() === String(item.id).toLowerCase());
                    if (existingItem) {
                        existingItem.qty = parseInt(existingItem.qty) + parseInt(item.missing_qty);
                    } else {
                        localCart.push({
                            id: item.id,
                            name: item.name,
                            color: item.color,
                            qty: item.missing_qty,
                            img: item.img
                        });
                    }
                    addedCount++;
                }
            });

            if (addedCount > 0) {
                localStorage.setItem('bricknexus_cart', JSON.stringify(localCart));
                alert(`🎉 Successfully transferred all missing elements straight into your global application shopping cart list!`);
                window.location.href = 'index.php';
            } else {
                alert("No missing items found to export coordinates into the active cart.");
            }
        }
    </script>
</body>
</html>
