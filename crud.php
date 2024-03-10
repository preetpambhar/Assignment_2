<?php
class Database
{
    private $host = 'localhost';
    private $db_name = 'assignment2';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}

class Factory
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db->getConnection();
    }

    // Product CRUD methods
    public function getAllProducts($table)
    {
        $stmt = $this->conn->prepare("SELECT * FROM $table");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //for geting cart 
    public function getCartById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM cart WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //get cart by user id 
    public function getAllCartItemsByUserId($userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //get order by id 
    public function getOrderById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM order WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //get order by user id 
    public function getAllOrdersByUserId($userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM order WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //create product
    public function createProduct($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO products (description, image, price, shipping_cost) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['description'], $data['image'], $data['price'], $data['shipping_cost']]);
        return $this->conn->lastInsertId();
    }

    public function createUser($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO user (email, password, username, purchase_history, shipping_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['email'], $data['password'], $data['username'], $data['purchase_history'], $data['shipping_address']]);
        return $this->conn->lastInsertId();
    }

    //for Cart creation
    public function createCart($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO cart (product_id, quantity, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id']]);
        return $this->conn->lastInsertId();
    }

    //create and order 
    public function createOrder($data)
    {
        // Prepare the order insertion query
        $stmt = $this->conn->prepare("INSERT INTO order (product_id, quantity, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id']]);
        $orderId = $this->conn->lastInsertId();

        // Delete the corresponding product from the cart table
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$data['product_id'], $data['user_id']]);

        return $orderId;
    }


    // post 
    public function updateProduct($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE products SET description = ?, image = ?, price = ?, shipping_cost = ? WHERE id = ?");
        $stmt->execute([$data['description'], $data['image'], $data['price'], $data['shipping_cost'], $id]);
    }

    public function updateUSer($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE user SET email = ?, password = ?, username = ?, purchase_history = ?, shipping_address = ? WHERE id = ?");
        $stmt->execute([$data['email'], $data['password'], $data['username'], $data['purchase_history'], $data['shipping_address'], $id]);
    }
    public function updateCart($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE cart SET product_id = ?, quantity = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $id]);
    }

    public function updateOrder($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE order SET product_id = ?, quantity = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $id]);

        // After updating the order, delete the corresponding product from the cart table
        $stmt = $this->conn->prepare("DELETE FROM Cart WHERE product_id = ?");
        $stmt->execute([$data['product_id']]);
    }

    //delete

    public function deleteProduct($id, $table)
    {
        $stmt = $this->conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function deleteOrder($id)
    {
        // Retrieve the product ID associated with the order
        $order = $this->getOrderById($id);
        $productId = $order['product_id'];

        // Delete the order from the Order table
        $stmt = $this->conn->prepare("DELETE FROM `Order` WHERE id = ?");
        $stmt->execute([$id]);

        // Delete the corresponding product from the cart table
        $stmt = $this->conn->prepare("DELETE FROM Cart WHERE product_id = ?");
        $stmt->execute([$productId]);
    }
}

$db = new Database();
$factory = new Factory($db);

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['entity'])) {
    $entity = $_GET['entity'];
    if ($entity === 'products') {
        if (isset($_GET['id'])) {
            $productId = $_GET['id'];
            echo json_encode($factory->getProductById($productId));
        } else {
            echo json_encode($factory->getAllProducts($entity));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['entity'])) {
    $entity = $_GET['entity'];
    $postData = json_decode(file_get_contents("php://input"), true);
    if ($entity === 'products') {
        // Debug: Echo the received POST data
        var_dump($postData);

        // Check if description field exists and is not empty
        if (
            isset($postData['description']) && !empty($postData['description']) &&
            isset($postData['image']) && !empty($postData['image']) &&
            isset($postData['price']) && !empty($postData['price']) &&
            isset($postData['shipping_cost']) && !empty($postData['shipping_cost'])
        ) {
            // Create the product
            $productId = $factory->createProduct($postData);
            echo json_encode(array("id" => $productId));
        } else {
            // Return a 400 Bad Request response if any required field is missing or empty
            http_response_code(400);
            echo json_encode(array("message" => "One or more required fields are missing or empty"));
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isset($_GET['entity']) && isset($_GET['id'])) {
    $entity = $_GET['entity'];
    $id = $_GET['id'];
    $putData = json_decode(file_get_contents("php://input"), true);
    if ($entity === 'products') {
        // Check if the required fields exist in the request data
        if (isset($putData['description']) && isset($putData['image']) && isset($putData['price']) && isset($putData['shipping_cost'])) {
            // Perform the update operation
            $factory->updateProduct($id, $putData);
            echo json_encode(array("message" => "Product updated successfully"));
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['entity']) && isset($_GET['id'])) {
    $entity = $_GET['entity'];
    $id = $_GET['id'];
    if ($entity === 'products') {

        $factory->deleteProduct($id, $entity);
    }
}
?>