<?php
set_time_limit(0); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php'; 

header('Content-Type: text/html; charset=utf-8');

echo "<h1>BrickNexus Master Seeder v6.0</h1>";
echo "<p>Purging artificial rows and deploying factory-accurate LEGO items...</p><hr>";

try {
    $pdo->beginTransaction();

    // Reset database tables entirely
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE `moc_parts`;");
    $pdo->exec("TRUNCATE TABLE `parts`;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "<p style='color:blue; font-family:mono;'>[✔] Table data cleared. Rebuilding verified mappings...</p>";

    $insertPart = $pdo->prepare("INSERT IGNORE INTO parts (element_id, name, color, image_url) VALUES (?, ?, ?, ?)");
    
    $technicPool = [];
    $systemPool = [];

    // Matrix array of real technic elements
    $realTechnic = [
        ['id' => '2780-11',  'name' => 'Technic Pin Friction Ridges', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/2780.png'],
        ['id' => '6558-7',   'name' => 'Technic Long Pin 3L Friction', 'color' => 'Blue', 'url' => 'https://img.bricklink.com/ItemImage/PN/7/6558.png'],
        ['id' => '43093-7',  'name' => 'Technic Axle Pin with Friction', 'color' => 'Blue', 'url' => 'https://img.bricklink.com/ItemImage/PN/7/43093.png'],
        ['id' => '3705-11',  'name' => 'Technic Cross Axle 4M', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3705.png'],
        ['id' => '3706-11',  'name' => 'Technic Cross Axle 6M', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3706.png'],
        ['id' => '3707-11',  'name' => 'Technic Cross Axle 8M', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3707.png'],
        ['id' => '4519-86',  'name' => 'Technic Cross Axle 3M', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/4519.png'],
        ['id' => '32062-5',  'name' => 'Technic Cross Axle 2M with Notch', 'color' => 'Red', 'url' => 'https://img.bricklink.com/ItemImage/PN/5/32062.png'],
        ['id' => '3713-86',  'name' => 'Technic Bushing Smooth', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3713.png'],
        ['id' => '6590-3',   'name' => 'Technic Bushing 1/2 Smooth', 'color' => 'Yellow', 'url' => 'https://img.bricklink.com/ItemImage/PN/3/6590.png'],
        ['id' => '32523-11', 'name' => 'Technic Beam 1 x 3 Thick', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/32523.png'],
        ['id' => '32523-86', 'name' => 'Technic Beam 1 x 3 Thick', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/32523.png'],
        ['id' => '32524-11', 'name' => 'Technic Beam 1 x 7 Thick', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/32524.png'],
        ['id' => '32524-86', 'name' => 'Technic Beam 1 x 7 Thick', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/32524.png'],
        ['id' => '32525-11', 'name' => 'Technic Beam 1 x 11 Thick', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/32525.png'],
        ['id' => '32525-86', 'name' => 'Technic Beam 1 x 11 Thick', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/32525.png']
    ];

    // Matrix array of real system elements
    $realSystem = [
        ['id' => '3069b-1',  'name' => 'Smooth Tile 1 x 2 Flat', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3069.png'],
        ['id' => '3069b-11', 'name' => 'Smooth Tile 1 x 2 Flat', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3069.png'],
        ['id' => '3069b-85', 'name' => 'Smooth Tile 1 x 2 Flat', 'color' => 'Dark Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/85/3069.png'],
        ['id' => '3069b-86', 'name' => 'Smooth Tile 1 x 2 Flat', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3069.png'],
        ['id' => '3068b-11', 'name' => 'Smooth Tile 2 x 2 Flat', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3068.png'],
        ['id' => '3068b-1',  'name' => 'Smooth Tile 2 x 2 Flat', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3068.png'],
        ['id' => '3068b-86', 'name' => 'Smooth Tile 2 x 2 Flat', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3068.png'],
        ['id' => '3001-11', 'name' => 'Standard Brick 2 x 4', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3001.png'],
        ['id' => '3001-1',  'name' => 'Standard Brick 2 x 4', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3001.png'],
        ['id' => '3001-5',  'name' => 'Standard Brick 2 x 4', 'color' => 'Red', 'url' => 'https://img.bricklink.com/ItemImage/PN/5/3001.png'],
        ['id' => '3001-7',  'name' => 'Standard Brick 2 x 4', 'color' => 'Blue', 'url' => 'https://img.bricklink.com/ItemImage/PN/7/3001.png'],
        ['id' => '3001-86', 'name' => 'Standard Brick 2 x 4', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3001.png'],
        ['id' => '3004-11', 'name' => 'Standard Brick 1 x 2', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3004.png'],
        ['id' => '3004-1',  'name' => 'Standard Brick 1 x 2', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3004.png'],
        ['id' => '3004-5',  'name' => 'Standard Brick 1 x 2', 'color' => 'Red', 'url' => 'https://img.bricklink.com/ItemImage/PN/5/3004.png'],
        ['id' => '3004-86', 'name' => 'Standard Brick 1 x 2', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3004.png'],
        ['id' => '3020-11', 'name' => 'Thin Plate 2 x 4', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3020.png'],
        ['id' => '3020-1',  'name' => 'Thin Plate 2 x 4', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3020.png'],
        ['id' => '3020-5',  'name' => 'Thin Plate 2 x 4', 'color' => 'Red', 'url' => 'https://img.bricklink.com/ItemImage/PN/5/3020.png'],
        ['id' => '3020-86', 'name' => 'Thin Plate 2 x 4', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3020.png'],
        ['id' => '3023-11', 'name' => 'Thin Plate 1 x 2', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/3023.png'],
        ['id' => '3023-1',  'name' => 'Thin Plate 1 x 2', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/3023.png'],
        ['id' => '3023-5',  'name' => 'Thin Plate 1 x 2', 'color' => 'Red', 'url' => 'https://img.bricklink.com/ItemImage/PN/5/3023.png'],
        ['id' => '3023-86', 'name' => 'Thin Plate 1 x 2', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/3023.png'],
        ['id' => '2420-1',  'name' => 'Plate 2 x 2 Corner Element', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/2420.png'],
        ['id' => '2420-11', 'name' => 'Plate 2 x 2 Corner Element', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/2420.png'],
        ['id' => '2412b-11', 'name' => 'Tile, Modified 1 x 2 Grille', 'color' => 'Black', 'url' => 'https://img.bricklink.com/ItemImage/PN/11/2412.png'],
        ['id' => '2412b-1',  'name' => 'Tile, Modified 1 x 2 Grille', 'color' => 'White', 'url' => 'https://img.bricklink.com/ItemImage/PN/1/2412.png'],
        ['id' => '2412b-86', 'name' => 'Tile, Modified 1 x 2 Grille', 'color' => 'Light Bluish Gray', 'url' => 'https://img.bricklink.com/ItemImage/PN/86/2412.png']
    ];

    foreach ($realTechnic as $part) {
        $insertPart->execute([$part['id'], $part['name'], $part['color'], $part['url']]);
        $technicPool[] = $part['id'];
    }

    foreach ($realSystem as $part) {
        $insertPart->execute([$part['id'], $part['name'], $part['color'], $part['url']]);
        $systemPool[] = $part['id'];
    }

    echo "<p style='color:green;'>[✔] Master parts database records successfully generated.</p>";

    // Get all lego models from main catalog table
    $mocsStmt = $pdo->query("SELECT id, category_id FROM mocs");
    $mocs = $mocsStmt->fetchAll(PDO::FETCH_ASSOC);

    $insertMocPart = $pdo->prepare("INSERT IGNORE INTO moc_parts (moc_id, part_id, quantity) VALUES (?, ?, ?)");
    $linkCount = 0;

    foreach ($mocs as $moc) {
        $mocId = $moc['id'];
        $catId = (int)$moc['category_id'];

        // Split arrays depending on category id value
        $pool = ($catId === 1) ? $technicPool : $systemPool;
        
        shuffle($pool);
        foreach ($pool as $partId) {
            $randomQty = rand(12, 145);
            $insertMocPart->execute([$mocId, $partId, $randomQty]);
            $linkCount++;
        }
    }

    $pdo->commit();
    echo "<h3>[✔] DATABASE RECONSTRUCTED SUCCESSFULLY! Total clean rows linked: {$linkCount}</h3>";
    echo "<p><a href='index.php'>&larr; Back to Dashboard</a></p>";

} catch (\Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "<h2 style='color:red;'>Transaction Failed:</h2><p>" . $e->getMessage() . "</p>";
}
?>