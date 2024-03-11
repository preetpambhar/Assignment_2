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
    public function getAllData($table)
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
        $stmt = $this->conn->prepare("SELECT * FROM `order` WHERE id = ?");
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
    //get comments by id 
    public function getCommentById($id, $table)
    {
        $stmt = $this->conn->prepare("SELECT * FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
    public function createCart($data)
    {
        $productId = $data['product_id'];
        $quantity = $data['quantity'];
        $userId = $data['user_id'];

        // Check if the product exists
        $productExists = $this->isProductExists($productId);

        // Check if the user exists
        $userExists = $this->isUserExists($userId);

        // If product and user exist, proceed with creating the cart
        if ($productExists && $userExists) {
            $stmt = $this->conn->prepare("INSERT INTO cart (product_id, quantity, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$productId, $quantity, $userId]);
            return $this->conn->lastInsertId();
        } else {
            // Return false and show error message
            echo json_encode(array("message" => "Invalid product_id or user_id"));
            return false;
        }
    }
    public function isProductExists($productId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function isUserExists($userId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM user WHERE id = ?");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    public function isUserWithCart($userId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    public function isProductInCart($productId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM cart WHERE product_id = ?");
            $stmt->execute([$productId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }


    //create and order 
    public function createOrder($data)
    {
        // Prepare the order insertion query
        $stmt = $this->conn->prepare("INSERT INTO `order` (product_id, quantity, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id']]);
        $orderId = $this->conn->lastInsertId();

        // Delete the corresponding product from the cart table
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$data['product_id'], $data['user_id']]);

        return $orderId;
    }
    //create an comment
    public function createComment($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO comments (product_id, user_id, rating, images, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['product_id'], $data['user_id'], $data['rating'], $data['images'], $data['comment']]);
        return $this->conn->lastInsertId();
    }

    // post 
    public function updateProduct($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE products SET description = ?, image = ?, price = ?, shipping_cost = ? WHERE id = ?");
        $stmt->execute([$data['description'], $data['image'], $data['price'], $data['shipping_cost'], $id]);
    }

    public function updateUser($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE user SET email = ?, password = ?, username = ?, purchase_history = ?, shipping_address = ? WHERE id = ?");
        $stmt->execute([$data['email'], $data['password'], $data['username'], $data['purchase_history'], $data['shipping_address'], $id]);
    }
    public function updateCart($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE cart SET product_id = ?, quantity = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $id]);
    }
    public function isCartExists($cartId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM cart WHERE id = ?");
            $stmt->execute([$cartId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            return false;
        }
    }


    public function updateOrder($id, $data, $productid, $userid)
    {

        $stmt = $this->conn->prepare("UPDATE `order` SET product_id = ?, quantity = ?, user_id = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['quantity'], $data['user_id'], $id]);

        // After updating the order, delete the corresponding product from the cart table
        $stmt = $this->conn->prepare("DELETE FROM Cart WHERE product_id = ?");
        $stmt->execute([$data['product_id']]);
    }
    public function isOrderExists($orderId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM `order` WHERE id = ? ");
            $stmt->execute([$orderId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            // Handle the error
            return false;
        }
    }
    public function isUserIdInOrder($userId, $id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT user_id FROM `order` WHERE id = ? ");
            $stmt->execute([$id]);
            $fetchedUserId = $stmt->fetchColumn();

            // Compare the fetched user_id with the provided $userId
            return $fetchedUserId == $userId;
        } catch (PDOException $e) {
            return false;
        }
    }
    public function isProductIdInOrder($productId, $id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT product_id FROM `order` WHERE id = ?");
            $stmt->execute([$id]);
            $fetchedProductId = $stmt->fetchColumn();

            // Compare the fetched product_id with the provided $productId
            return $fetchedProductId == $productId;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateComment($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE comments SET product_id = ?, user_id = ?, rating = ?, images = ?, comment = ? WHERE id = ?");
        $stmt->execute([$data['product_id'], $data['user_id'], $data['rating'], $data['images'], $data['comment'], $id]);
    }

    public function isCommentExists($orderId)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM comments WHERE id = ? ");
            $stmt->execute([$orderId]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            // Handle the error
            return false;
        }
    }
    public function isUserIdInComment($userId, $id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT user_id FROM comments WHERE id = ? ");
            $stmt->execute([$id]);
            $fetchedUserId = $stmt->fetchColumn();

            // Compare the fetched user_id with the provided $userId
            return $fetchedUserId == $userId;
        } catch (PDOException $e) {
            return false;
        }
    }
    public function isProductIdInComment($productId, $id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT product_id FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            $fetchedProductId = $stmt->fetchColumn();

            // Compare the fetched product_id with the provided $productId
            return $fetchedProductId == $productId;
        } catch (PDOException $e) {
            return false;
        }
    }
    //delete

    public function deleteData($id, $table)
    {
        $stmt = $this->conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
    }
    public function deleteUser($userId)
    {
        try {
            // 1. Retrieve all related orders associated with the user
            $stmt = $this->conn->prepare("SELECT id FROM `order` WHERE user_id = ?");
            $stmt->execute([$userId]);
            $orderIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // 2. Delete related orders
            foreach ($orderIds as $orderId) {
                $this->deleteOrder($orderId); // Assuming you have a deleteOrder function
            }

            // 3. Delete the user
            $stmt = $this->conn->prepare("DELETE FROM `user` WHERE id = ?");
            $stmt->execute([$userId]);

            echo json_encode(array("message" => "User and related orders deleted successfully"));
        } catch (PDOException $e) {
            // Handle the error
            echo json_encode(array("message" => "Failed to delete user and related orders"));
        }
    }

    public function isRecordExists($entity, $id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM $entity WHERE id = ?");
            $stmt->execute([$id]); // Pass the record ID as a parameter
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            // Handle the error
            return false;
        }
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
    public function deleteCart($id)
    {
        // Delete the order from the Order table
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$id]);
    }
}

$db = new Database();
$factory = new Factory($db);

// Handle API requests (Get)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['entity'])) {
    $entity = $_GET['entity'];
    if ($entity === 'products') {
        if (isset($_GET['id'])) {
            $productId = $_GET['id'];
            $product = $factory->getProductById($productId);
            if ($product) {
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Product not found"));
            }
        } else {
            echo json_encode($factory->getAllData($entity));
        }
    } elseif ($entity === 'user') {
        if (isset($_GET['id'])) {
            $userId = $_GET['id'];
            $user = $factory->getUserById($userId);
            if ($user) {
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
            }
        } else {
            echo json_encode($factory->getAllData($entity));
        }
    } elseif ($entity === 'cart') {
        if (isset($_GET['id'])) {
            $cartId = $_GET['id'];
            $cart = $factory->getCartById($cartId);
            if ($cart) {
                echo json_encode($cart);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Cart not found"));
            }
        } elseif (isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
            echo json_encode($factory->getAllCartItemsByUserId($userId));
        } else {
            echo json_encode($factory->getAllData($entity));
        }
    } elseif ($entity === 'order') {
        if (isset($_GET['id'])) {
            $orderId = $_GET['id'];
            $order = $factory->getOrderById($orderId);
            if ($order) {
                echo json_encode($order);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Order not found"));
            }
        } elseif (isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
            echo json_encode($factory->getAllOrdersByUserId($userId));
        } else {
            echo json_encode($factory->getAllData('`order`'));
        }
    } elseif ($entity === 'comments') {
        if (isset($_GET['id'])) {
            $commentId = $_GET['id'];
            $comment = $factory->getCommentById($commentId, $entity);
            if ($comment) {
                echo json_encode($comment);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Comment not found"));
            }
        } else {
            echo json_encode($factory->getAllData('`comments`'));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Table not found"));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['entity'])) {
    $entity = $_GET['entity'];
    $postData = json_decode(file_get_contents("php://input"), true);

    // Define an array of valid entities
    $validEntities = array('products', 'user', 'cart', 'order', 'comments');

    // Check if the specified entity is valid
    if (!in_array($entity, $validEntities)) {
        // Return a 400 Bad Request response if the entity is invalid
        echo json_encode(array("message" => "Invalid entity specified"));
        exit;
    }
    if ($entity === 'products') {
        // Check if the required fields for creating a product exist and are not empty
        if (
            isset($postData['description']) && !empty($postData['description']) &&
            isset($postData['image']) && !empty($postData['image']) &&
            isset($postData['price']) && !empty($postData['price']) &&
            isset($postData['shipping_cost']) && !empty($postData['shipping_cost'])
        ) {
            // Create the product
            $productId = $factory->createProduct($postData);
            echo json_encode(array("id" => $productId));
            echo json_encode(array("Product Added successfully at" => $productId));
        } else {
            // Return a 400 Bad Request response if any required field is missing or empty
            echo json_encode(array("message" => "One or more required fields are missing or empty"));
        }
    } elseif ($entity === 'user') {
        // Check if the required fields for creating a user exist and are not empty
        if (
            isset($postData['email']) && !empty($postData['email']) &&
            isset($postData['password']) && !empty($postData['password']) &&
            isset($postData['username']) && !empty($postData['username']) &&
            isset($postData['purchase_history']) && !empty($postData['purchase_history']) &&
            isset($postData['shipping_address']) && !empty($postData['shipping_address'])
        ) {
            // Create the user
            $userId = $factory->createUser($postData);
            echo json_encode(array("User Added successfully at" => $userId));
        } else {
            // Return a 400 Bad Request response if any required field is missing or empty
            echo json_encode(array("message" => "One or more required fields are missing or empty"));
        }
    } elseif ($entity === 'cart') {
        // Check if the required fields for creating a cart exist and are not empty
        if (
            isset($postData['product_id']) && !empty($postData['product_id']) &&
            isset($postData['quantity']) && !empty($postData['quantity']) &&
            isset($postData['user_id']) && !empty($postData['user_id'])
        ) {
            // Check if the product exists
            $productId = $postData['product_id'];
            $productExists = $factory->isProductExists($productId);

            // Check if the user exists
            $userId = $postData['user_id'];
            $userExists = $factory->isUserExists($userId);

            // Check if the product exists
            if (!$productExists) {
                http_response_code(400);
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit;
            }

            // Check if the user exists
            if (!$userExists) {
                http_response_code(400);
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit;
            }

            // Create the cart
            $orderId = $factory->createCart($postData);
            echo json_encode(array("Cart created successfully at" => $orderId));
        } else {
            // Return a 400 Bad Request response if any required field is missing or empty
            echo json_encode(array("message" => "One or more required fields are missing or empty"));
        }
    } elseif ($entity === 'order') {
        // Check if the required fields for creating an order exist and are not empty
        if (
            isset($postData['product_id']) && !empty($postData['product_id']) &&
            isset($postData['quantity']) && !empty($postData['quantity']) &&
            isset($postData['user_id']) && !empty($postData['user_id'])
        ) {
            $productId = $postData['product_id'];
            $productExists = $factory->isProductInCart($productId);

            // Check if the user exists
            $userId = $postData['user_id'];
            $userExists = $factory->isUserWithCart($userId);

            // Check if the product exists
            if (!$productExists) {
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit;
            }

            // Check if the user exists
            if (!$userExists) {
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit; // Stop further execution
            }

            // Create the order and delete the corresponding product from the cart
            $orderId = $factory->createOrder($postData);
            echo json_encode(array("Order Added successfully at" => $orderId));
        } else {
            // Return a 400 Bad Request response if any required field is missing or empty
            http_response_code(400);
            echo json_encode(array("message" => "One or more required fields are missing or empty"));
        }
    } elseif ($entity === 'comments') {
        // Check if the required fields for creating a comment exist and are not empty
        if (
            isset($postData['product_id']) && !empty($postData['product_id']) &&
            isset($postData['user_id']) && !empty($postData['user_id']) &&
            isset($postData['rating']) && !empty($postData['rating']) &&
            isset($postData['images']) && !empty($postData['images']) &&
            isset($postData['comment']) && !empty($postData['comment'])
        ) {

            $productId = $postData['product_id'];
            $productExists = $factory->isProductExists($productId);

            // Check if the user exists
            $userId = $postData['user_id'];
            $userExists = $factory->isUserExists($userId);

            // Check if the product exists
            if (!$productExists) {
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit; // Stop further execution
            }

            // Check if the user exists
            if (!$userExists) {
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit; // Stop further execution
            }
            // Create the comment
            $commentId = $factory->createComment($postData);
            echo json_encode(array("Comment Added successfully at" => $commentId));
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
        if (
            isset($putData['description']) &&
            isset($putData['image']) &&
            isset($putData['price']) &&
            isset($putData['shipping_cost']) &&
            isset($_GET['id']) &&
            !empty($_GET['id'])
        ) {
            $productId = $_GET['id'];
            // Check if the product exists
            if ($factory->isProductExists($productId)) {
                $factory->updateProduct($productId, $putData);
                echo json_encode(array("message" => "Product updated successfully"));
            } else {
                echo json_encode(array("message" => "Product not found"));
            }
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            echo json_encode(array("message" => "Missing required fields"));
        }
    }
    if ($entity === 'user') {
        // Check if the required fields exist in the request data
        if (
            isset($putData['email']) &&
            isset($putData['password']) &&
            isset($putData['username']) &&
            isset($putData['purchase_history']) &&
            isset($putData['shipping_address']) &&
            isset($_GET['id']) && // Ensure the ID parameter is present in the URL
            !empty($_GET['id']) // Ensure the ID parameter is not empty
        ) {
            $userId = $_GET['id'];
            // Check if the user exists
            if ($factory->isUserExists($userId)) {
                // Perform the update operation for users
                $factory->updateUser($userId, $putData);
                echo json_encode(array("message" => "User updated successfully"));
            } else {
                // Return a 404 Not Found response if the user does not exist
                http_response_code(404);
                echo json_encode(array("message" => "User not found"));
            }
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } elseif ($entity === 'cart') {
        // Check if the required fields exist in the request data
        if (isset($putData['product_id']) && isset($putData['quantity']) && isset($putData['user_id'])) {

            if (!$factory->isCartExists($id)) {
                http_response_code(404);
                echo json_encode(array("message" => "Cart with ID $id does not exist"));
                exit; // Stop further execution
            }

            $productId = $putData['product_id'];
            $productExists = $factory->isProductInCart($productId);

            // Check if the user exists
            $userId = $putData['user_id'];
            $userExists = $factory->isUserWithCart($userId);

            // Check if the product exists
            if (!$productExists) {
                http_response_code(400);
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit;
            }

            // Check if the user exists
            if (!$userExists) {
                http_response_code(400);
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit; // Stop further execution
            }

            // Perform the update operation for cart
            $factory->updateCart($id, $putData);
            echo json_encode(array("message" => "Cart updated successfully"));
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
        }
    } elseif ($entity === 'order') {
        // Check if the required fields exist in the request data
        if (isset($putData['product_id']) && isset($putData['quantity']) && isset($putData['user_id'])) {

            if (!$factory->isOrderExists($id)) {
                echo json_encode(array("message" => "Order with ID $id does not exist"));
                exit;
            }

            $productId = $putData['product_id'];
            $orderProductIdExists = $factory->isProductIdInOrder($productId, $id);
            $userId = $putData['user_id'];
            $orderUserIdExists = $factory->isUserIdInOrder($userId, $id);


            if (!$orderProductIdExists) {
                http_response_code(400);
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit;
            }

            // Check if the user exists
            if (!$orderUserIdExists) {
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit; // Stop further execution
            }

            // Perform the update operation for order
            $factory->updateOrder($id, $putData, $productId, $userId);
            echo json_encode(array("message" => "Order updated successfully"));
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            echo json_encode(array("message" => "Missing required fields"));
        }
    } elseif ($entity === 'comments') {
        // Check if the required fields exist in the request data
        if (isset($putData['product_id']) && isset($putData['user_id']) && isset($putData['rating']) && isset($putData['images']) && isset($putData['comment'])) {
            // Perform the update operation for comments
            if (!$factory->isCommentExists($id)) {
                echo json_encode(array("message" => "Comment with ID $id does not exist"));
                exit;
            }

            $productId = $putData['product_id'];
            $orderProductIdExists = $factory->isProductIdInComment($productId, $id);
            $userId = $putData['user_id'];
            $orderUserIdExists = $factory->isUserIdInComment($userId, $id);

            if (!$orderProductIdExists) {
                echo json_encode(array("message" => "Product with ID $productId does not exist"));
                exit;
            }

            // Check if the user exists
            if (!$orderUserIdExists) {
                echo json_encode(array("message" => "User with ID $userId does not exist"));
                exit; // Stop further execution
            }

            $factory->updateComment($id, $putData);
            echo json_encode(array("message" => "Comment updated successfully"));
        } else {
            // Return a 400 Bad Request response if any required fields are missing
            echo json_encode(array("message" => "Missing required fields"));
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['entity']) && isset($_GET['id'])) {
    $entity = $_GET['entity'];
    $id = $_GET['id'];

    if ($entity === "order") {
        if (!$factory->isOrderExists($id)) {
            echo json_encode(array("message" => "Order with ID $id does not exist"));
            exit;
        }
    }
    if ($entity === "cart") {
        if (!$factory->isCartExists($id)) {
            http_response_code(404);
            echo json_encode(array("message" => "Cart with ID $id does not exist"));
            exit; // Stop further execution
        }
    }
    if ($entity === "comments") {
        if (!$factory->isCommentExists($id)) {
            echo json_encode(array("message" => "Comment with ID $id does not exist"));
            exit;
        }
    }
    if ($entity === 'products' || $entity === 'comments') {
        $factory->deleteData($id, $entity);
        echo json_encode(array("message" => "Deleted successfully in $entity at id: $id"));
    } elseif (($entity === 'user')) {
        $factory->deleteUser($id);
        echo json_encode(array("message" => "user Deleted successfully"));
    } elseif (($entity === 'order')) {
        $factory->deleteOrder($id);
        echo json_encode(array("message" => "Order Deleted successfully"));
    } elseif (($entity === 'cart')) {
        $factory->deleteCart($id);
        echo json_encode(array("message" => "Order Deleted successfully"));
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Tabel is not found"));
    }
}
?>