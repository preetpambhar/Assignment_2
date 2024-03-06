<?php
$url = "mysql:host=localhost;dbname=assignment2";
$user = "root";
$password = "";
$db = new PDO($url, $user, $password);
?>

<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'assignment2');
?>

<?php
function getAllProducts()
{
    global $db;
    $stmt = $db->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $products;
}

// API endpoint to get all products
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(getAllProducts());
}
?>