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

try {
    // Get all lego models from database
    $stmt = $pdo->query("SELECT m.*, c.name AS category_name FROM mocs m LEFT JOIN categories c ON m.category_id = c.id");
    $mocs = $stmt->fetchAll();

    // Get parts catalog list
    $partsStmt = $pdo->query("SELECT * FROM parts");
    $db_parts = $partsStmt->fetchAll();

    // Get all mapped parts for lego models
    $mocPartsStmt = $pdo->query("SELECT mp.moc_id, mp.part_id, mp.quantity, p.name, p.color, p.image_url FROM moc_parts mp JOIN parts p ON mp.part_id = p.element_id");
    $moc_parts_all = $mocPartsStmt->fetchAll();

    $moc_parts_grouped = [];
    foreach ($moc_parts_all as $row) {
        $moc_parts_grouped[$row['moc_id']][] = [
            'id' => $row['part_id'],
            'name' => $row['name'],
            'color' => $row['color'],
            'qty' => (int)$row['quantity'],
            'img' => $row['image_url']
        ];
    }
} catch (\PDOException $e) {
    die("Database connectivity error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrickNexus - Premium LEGO Platform</title>
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
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-brickDark text-gray-100 font-sans min-h-screen flex flex-col antialiased selection:bg-brickAccent selection:text-brickDark">

    <nav class="bg-brickCard/80 backdrop-blur-md border-b border-gray-800 px-6 py-4 flex items-center justify-between sticky top-0 z-40 transition-all">
        <div class="flex items-center space-x-3">
            <div class="bg-gradient-to-r from-amber-500 to-brickAccent text-brickDark font-black px-3 py-1 rounded-lg text-xl tracking-wider shadow-lg shadow-amber-500/10 font-mono">
                BRICKNEXUS
            </div>
        </div>
        
        <div class="flex items-center space-x-8 text-sm font-medium">
            <a href="index.php" class="text-brickAccent flex items-center gap-1 border-b-2 border-brickAccent pb-1">Dashboard</a>
            <a href="calculator.php" class="text-gray-400 hover:text-brickAccent transition duration-200">Build Calculator</a>
            
            <button onclick="toggleCart(true)" class="relative p-2 text-gray-400 hover:text-white transition group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 group-hover:scale-105 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span id="cartBadge" class="absolute -top-1 -right-1 bg-red-500 text-white font-mono font-bold text-[10px] w-5 h-5 flex items-center justify-center rounded-full border border-brickDark shadow-md">0</span>
            </button>

            <div class="relative">
                <button onclick="toggleProfileMenu()" id="profileBtn" class="flex items-center gap-2 bg-brickSecondary hover:bg-gray-700/50 px-4 py-2 rounded-xl border border-gray-700/50 transition text-xs font-semibold tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    <?= htmlspecialchars($_SESSION['username'] ?? 'User Profile') ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-brickCard border border-gray-800 rounded-xl shadow-2xl py-2 z-50 fade-in">
                    <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-800">
                        Logged in as <br><strong class="text-gray-300"><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    </div>
                    <a href="calculator.php" class="block px-4 py-2 text-xs text-gray-300 hover:bg-brickSecondary hover:text-brickAccent transition mt-1">My Lego Inventory</a>
                    <a href="orders.php" class="block px-4 py-2 text-xs text-gray-300 hover:bg-brickSecondary hover:text-brickAccent transition">My Orders</a>
                    <a href="logout.php" class="block px-4 py-2 text-xs text-red-400 hover:bg-red-500/10 font-medium border-t border-gray-800 transition mt-2">
                        Sign Out / Switch Account
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div id="cartPanel" class="fixed inset-0 overflow-hidden z-50 hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <div onclick="toggleCart(false)" class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md bg-brickCard border-l border-gray-800 p-6 flex flex-col h-full shadow-2xl">
                    
                    <div class="flex items-center justify-between pb-4 border-b border-gray-800">
                        <div class="flex items-center gap-4">
                            <h2 class="text-lg font-bold tracking-wide flex items-center gap-2">
                                <span>Parts Management</span>
                                <span id="cartCountBadge" class="text-xs bg-brickSecondary px-2 py-0.5 rounded text-gray-400">0 Items</span>
                            </h2>
                            <button onclick="clearCart()" class="text-xs text-red-400 hover:text-red-500 hover:underline font-mono transition">Clear All</button>
                        </div>
                        <button onclick="toggleCart(false)" class="text-gray-400 hover:text-white p-1 rounded-lg hover:bg-brickSecondary transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="mt-4 p-4 bg-brickDark/50 border border-gray-800 rounded-xl">
                        <label class="block text-[10px] uppercase tracking-wider text-gray-400 mb-2 font-bold">Quick Add Part by BrickLink ID</label>
                        <div class="flex gap-2">
                            <input type="text" id="partIdInput" placeholder="Enter ANY real BrickLink ID (e.g. 4274, 32054)..." class="flex-grow bg-brickCard border border-gray-700 rounded-lg px-3 py-2 text-xs text-gray-200 focus:outline-none focus:border-brickAccent placeholder-gray-600 font-mono">
                            <button onclick="addPartById()" class="bg-brickAccent hover:bg-amber-500 text-brickDark font-bold text-xs px-4 rounded-lg transition shrink-0 uppercase tracking-wider">Add</button>
                        </div>
                    </div>

                    <div id="cartItemsList" class="flex-grow overflow-y-auto py-4 space-y-4 font-sans"></div>

                    <div class="p-4 bg-brickDark/50 border border-gray-800 rounded-xl mb-2">
                        <label class="block text-[10px] uppercase tracking-wider text-gray-400 mb-2 font-bold">Select Shipping Courier</label>
                        <select id="deliveryCompanyInput" class="w-full bg-brickCard border border-gray-700 rounded-lg px-3 py-2 text-xs text-gray-200 focus:outline-none focus:border-brickAccent font-medium">
                            <option value="">-- Choose Courier Company --</option>
                            <option value="FanCourier">FanCourier (Express Delivery)</option>
                            <option value="Sameday">Sameday (Easybox Locker System)</option>
                            <option value="PostRomania">PostRomania (National Post Office)</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-gray-800 space-y-3">
                        <div class="flex justify-between text-sm text-gray-400 font-mono">
                            <span>Total Bricks Requested:</span>
                            <span id="cartTotalSum" class="text-brickAccent font-bold text-base">0 pcs</span>
                        </div>
                        <button onclick="sendOrderToBackend()" class="w-full bg-brickAccent hover:bg-amber-500 text-brickDark font-bold py-3 rounded-xl shadow-lg shadow-amber-500/10 text-xs uppercase tracking-widest transition">
                            Checkout Order via BrickLink
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-4xl md:text-5xl font-black mb-4 tracking-tight leading-none">
                Build Anything From Your <span class="bg-gradient-to-r from-amber-400 to-brickAccent bg-clip-text text-transparent">LEGO Bricks</span>
            </h1>
            <p class="text-gray-400 text-sm max-w-xl mx-auto mb-8">
                Discover alternative builds, calculate missing elements, and pull detailed inventory parameters instantly.
            </p>
            
            <div class="relative max-w-xl mx-auto group">
                <input type="text" id="searchInput" onkeyup="filterMocs()" placeholder="Search by Set Title, Category or ID Number (e.g. 75192)..." class="w-full bg-brickCard border border-gray-800 rounded-2xl px-5 py-4 pl-12 focus:outline-none focus:border-brickAccent focus:ring-4 focus:ring-brickAccent/5 transition-all text-gray-200 placeholder-gray-500 shadow-xl">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-brickAccent transition duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
        </div>

        <div class="fade-in">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
                <h2 class="text-xl font-bold tracking-wide flex items-center gap-2">
                    <span>Popular LEGO Database Sets</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-brickAccent"></span>
                </h2>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select id="sortInput" onchange="sortMocs()" class="bg-brickCard border border-gray-800 text-xs font-semibold text-gray-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-brickAccent transition duration-150">
                        <option value="default">Default Catalog Sort</option>
                        <option value="name-az">Name: A to Z</option>
                        <option value="name-za">Name: Z to A</option>
                        <option value="parts-high">Parts: High to Low</option>
                        <option value="parts-low">Parts: Low to High</option>
                        <option value="id-asc">Set ID: Ascending</option>
                    </select>

                    <span class="text-xs font-mono text-gray-500 bg-brickCard px-4 py-2.5 rounded-xl border border-gray-800 whitespace-nowrap" id="counter">Showing <?= count($mocs) ?> models</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="mocsContainer">
                <?php foreach ($mocs as $moc): ?>
                    <a href="set-details.php?id=<?= urlencode($moc['id']) ?>" class="moc-card block bg-brickCard rounded-2xl overflow-hidden border border-gray-800 hover:border-gray-700 hover:shadow-2xl hover:shadow-amber-500/[0.02] transition-all duration-300 transform hover:-translate-y-1" data-id="<?= strtolower($moc['id']) ?>" data-title="<?= strtolower($moc['title']) ?>" data-category="<?= strtolower($moc['category_name'] ?? '') ?>" data-parts="<?= $moc['parts_count'] ?>">
                        <div class="h-48 w-full bg-gray-900 relative overflow-hidden">
                            <img src="<?= htmlspecialchars($moc['image_url']) ?>" alt="LEGO" class="w-full h-full object-cover transform hover:scale-102 transition duration-500">
                            <span class="absolute top-3 right-3 bg-brickDark/90 border border-gray-800 text-brickAccent text-[10px] font-bold tracking-wider uppercase px-2 py-1 rounded-md backdrop-blur-sm"><?= htmlspecialchars($moc['category_name'] ?? 'LEGO') ?></span>
                        </div>
                        <div class="p-5 font-sans flex flex-col justify-between h-44">
                            <div>
                                <span class="text-[10px] text-brickAccent font-mono bg-brickAccent/10 px-2 py-0.5 rounded-md font-bold">SET: <?= htmlspecialchars($moc['id']) ?></span>
                                <h3 class="text-base font-bold text-white mt-2 mb-1 truncate hover:text-brickAccent transition duration-150"><?= htmlspecialchars($moc['title']) ?></h3>
                                <div class="text-xs text-gray-400 font-mono">Parts: <?= $moc['parts_count'] ?> pcs</div>
                            </div>
                            <button onclick="addSetPartsToCart(event, '<?= htmlspecialchars($moc['id']) ?>')" class="mt-3 w-full bg-brickAccent hover:bg-amber-500 text-brickDark font-black py-2.5 rounded-xl text-xs uppercase tracking-wider transition duration-150 shadow-md">
                                Add Set to Cart
                            </button>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div id="noResults" class="hidden text-center py-16 text-gray-500 border border-dashed border-gray-800 rounded-2xl"><p class="text-sm font-medium">No official LEGO sets matching your query were found.</p></div>
        </div>
    </main>

    <footer class="bg-brickCard border-t border-gray-800 py-6 text-center text-xs text-gray-500"><p>&copy; 2026 BrickNexus Premium Engine. All Rights Reserved.</p></footer>

    <script>
        function toggleCart(open) {
            const panel = document.getElementById('cartPanel');
            if (panel) {
                if(open) panel.classList.remove('hidden');
                else panel.classList.add('hidden');
            }
        }

        function toggleProfileMenu() {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        const dbPartsCatalog = <?php echo json_encode($db_parts); ?> || [];
        const mocPartsLookup = <?php echo json_encode($moc_parts_grouped); ?> || {};
        let cart = [];

        try {
            const savedCart = localStorage.getItem('bricknexus_cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                if (!Array.isArray(cart)) cart = [];
            }
        } catch (e) {
            cart = [];
            localStorage.setItem('bricknexus_cart', JSON.stringify([]));
        }

        function saveCartState() {
            localStorage.setItem('bricknexus_cart', JSON.stringify(cart));
        }

        function renderCart() {
            const listContainer = document.getElementById('cartItemsList');
            const badge = document.getElementById('cartBadge');
            const countBadge = document.getElementById('cartCountBadge');
            const totalSumField = document.getElementById('cartTotalSum');
            
            if (!listContainer) return;

            if (cart.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center py-16 text-gray-500 text-xs border border-dashed border-gray-800 rounded-xl mt-4">
                        <p>Your parts inventory list is currently empty.</p>
                    </div>`;
                if(badge) badge.innerText = '0';
                if(countBadge) countBadge.innerText = '0 Items';
                if(totalSumField) totalSumField.innerText = '0 pcs';
                return;
            }

            let html = '';
            let totalBricks = 0;

            cart.forEach((item, index) => {
                totalBricks += parseInt(item.qty) || 0;
                html += `
                <div class="flex items-center justify-between p-3 bg-brickDark/40 rounded-xl border border-gray-800 fade-in">
                    <div class="flex items-center gap-3 max-w-[50%]">
                        <div class="w-10 h-10 bg-white p-1 rounded-lg flex items-center justify-center shrink-0 shadow-inner">
                            <img src="${item.img}" class="max-h-full" onerror="this.src='https://placehold.co/50x50?text=Lego'">
                        </div>
                        <div class="truncate">
                            <span class="block font-bold text-xs text-gray-200 truncate">${item.name}</span>
                            <span class="text-[10px] text-gray-500 font-mono">ID: ${item.id} &bull; ${item.color}</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-brickSecondary border border-gray-700 rounded-lg overflow-hidden h-8">
                            <button onclick="changeQty(${index}, -1)" class="px-2 text-gray-400 hover:bg-gray-700 hover:text-white transition font-bold text-sm">-</button>
                            <input type="number" value="${item.qty}" onchange="inputQty(${index}, this.value)" class="w-12 bg-transparent text-center text-xs font-mono font-bold text-brickAccent focus:outline-none" min="1">
                            <button onclick="changeQty(${index}, 1)" class="px-2 text-gray-400 hover:bg-gray-700 hover:text-white transition font-bold text-sm">+</button>
                        </div>
                        <button onclick="deleteItem(${index})" class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>`;
            });

            listContainer.innerHTML = html;
            if(badge) badge.innerText = cart.length;
            if(countBadge) countBadge.innerText = `${cart.length} Items`;
            if(totalSumField) totalSumField.innerText = `${totalBricks} pcs`;
        }

        function addSetPartsToCart(event, mocId) {
            event.preventDefault();
            const partsToAdd = mocPartsLookup[mocId];
            if (!partsToAdd || partsToAdd.length === 0) {
                alert(`This set (#${mocId}) has no parts registered in the database yet!`);
                return;
            }

            partsToAdd.forEach(newPart => {
                const existingItem = cart.find(item => String(item.id).toLowerCase() === String(newPart.id).toLowerCase());
                if (existingItem) {
                    existingItem.qty = parseInt(existingItem.qty) + parseInt(newPart.qty);
                } else {
                    cart.push({ id: newPart.id, name: newPart.name, color: newPart.color, qty: newPart.qty, img: newPart.img });
                }
            });

            saveCartState();
            renderCart();
            toggleCart(true);
        }

        function addPartById() {
            const inputField = document.getElementById('partIdInput');
            if (!inputField) return;
            const partId = inputField.value.trim().toLowerCase();
            if (!partId) return;

            let matchedPart = dbPartsCatalog.find(p => 
                String(p.element_id).toLowerCase() === partId ||
                String(p.element_id).toLowerCase().startsWith(partId + "-")
            );

            if (!matchedPart) {
                matchedPart = {
                    element_id: partId, 
                    name: "BrickLink Part No. " + partId,
                    color: "Black",
                    image_url: "https://img.bricklink.com/ItemImage/PN/11/" + partId + ".png"
                };
            }

            const existingCartItem = cart.find(item => String(item.id).toLowerCase() === String(matchedPart.element_id).toLowerCase());
            if (existingCartItem) {
                existingCartItem.qty = parseInt(existingCartItem.qty) + 1;
            } else {
                cart.push({ 
                    id: matchedPart.element_id, 
                    name: matchedPart.name, 
                    color: matchedPart.color, 
                    qty: 1, 
                    img: matchedPart.image_url 
                });
            }
            inputField.value = '';
            saveCartState();
            renderCart();
        }

        function changeQty(index, change) {
            let newQty = parseInt(cart[index].qty) + change;
            if (newQty < 1) newQty = 1;
            cart[index].qty = newQty;
            saveCartState();
            renderCart();
        }

        function inputQty(index, value) {
            let newQty = parseInt(value);
            if (isNaN(newQty) || newQty < 1) newQty = 1;
            cart[index].qty = newQty;
            saveCartState();
            renderCart();
        }

        function deleteItem(index) {
            cart.splice(index, 1);
            saveCartState();
            renderCart();
        }

        function clearCart() {
            if (cart.length === 0) return;
            if (confirm("Are you sure you want to completely clear your parts list?")) {
                cart = [];
                saveCartState();
                renderCart();
            }
        }

        function sendOrderToBackend() {
            if (cart.length === 0) {
                alert("Your cart is empty!");
                return;
            }
            
            const deliveryCompany = document.getElementById('deliveryCompanyInput').value;
            if (!deliveryCompany) {
                alert("⚠️ Please select a shipping courier company before checking out!");
                return;
            }
            
            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    delivery_company: deliveryCompany,
                    cart: cart
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(`🎉 Success! Order #${data.order_id} has been registered via ${deliveryCompany} delivery path!`);
                    cart = [];
                    document.getElementById('deliveryCompanyInput').value = '';
                    saveCartState();
                    renderCart();
                    toggleCart(false);
                } else { 
                    alert(`Database Restraint Error:\n${data.message}`); 
                }
            })
            .catch(error => { 
                alert('Critical Server Failure Log:\n' + error.message); 
            });
        }

        function filterMocs() {
            const input = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.getElementsByClassName('moc-card');
            let visibleCount = 0;
            for (let i = 0; i < cards.length; i++) {
                if (cards[i].getAttribute('data-id').includes(input) || cards[i].getAttribute('data-title').includes(input) || cards[i].getAttribute('data-category').includes(input)) {
                    cards[i].style.display = ""; visibleCount++;
                } else { cards[i].style.display = "none"; }
            }
            const counter = document.getElementById('counter');
            if (counter) counter.innerText = `Showing ${visibleCount} models`;
            const noResults = document.getElementById('noResults');
            if (noResults) noResults.style.display = visibleCount === 0 ? "block" : "none";
        }

        function sortMocs() {
            const container = document.getElementById('mocsContainer');
            const cards = Array.from(container.getElementsByClassName('moc-card'));
            const criteria = document.getElementById('sortInput').value;

            cards.sort((a, b) => {
                if (criteria === 'name-az') {
                    return a.getAttribute('data-title').localeCompare(b.getAttribute('data-title'));
                } else if (criteria === 'name-za') {
                    return b.getAttribute('data-title').localeCompare(a.getAttribute('data-title'));
                } else if (criteria === 'parts-high') {
                    return parseInt(b.getAttribute('data-parts')) - parseInt(a.getAttribute('data-parts'));
                } else if (criteria === 'parts-low') {
                    return parseInt(a.getAttribute('data-parts')) - parseInt(b.getAttribute('data-parts'));
                } else if (criteria === 'id-asc') {
                    return a.getAttribute('data-id').localeCompare(b.getAttribute('data-id'), undefined, {numeric: true});
                } else {
                    return 0;
                }
            });

            cards.forEach(card => container.appendChild(card));
        }

        window.addEventListener('click', function(e) {
            const btn = document.getElementById('profileBtn');
            const dropdown = document.getElementById('profileDropdown');
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', renderCart);
    </script>
</body>
</html>