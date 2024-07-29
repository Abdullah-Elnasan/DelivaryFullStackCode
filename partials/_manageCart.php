<?php
include '_dbconnect.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['userId'];                
    if(isset($_POST['addToCart'])) {
        $itemId = $_POST["itemId"];
        // Check whether this item exists
        $existSql = "SELECT * FROM `viewcart` WHERE pizzaId = '$itemId' AND `userId`='$userId'";
        $result = mysqli_query($conn, $existSql);
        $numExistRows = mysqli_num_rows($result);
        if($numExistRows > 0){
            echo "<script>alert('Item Already Added.');
                    window.history.back(1);
                    </script>";
        }
        else{
            $sql = "INSERT INTO `viewcart` (`pizzaId`, `itemQuantity`, `userId`, `addedDate`) VALUES ('$itemId', '1', '$userId', current_timestamp())";   
            $result = mysqli_query($conn, $sql);
            if ($result){
                echo "<script>
                    window.history.back(1);
                    </script>";
            }
        }
    }
    if(isset($_POST['removeItem'])) {
        $itemId = $_POST["itemId"];
        $sql = "DELETE FROM `viewcart` WHERE `pizzaId`='$itemId' AND `userId`='$userId'";   
        $result = mysqli_query($conn, $sql);
        echo "<script>alert('Removed');
                window.history.back(1);
            </script>";
    }
    if(isset($_POST['removeAllItem'])) {
        $sql = "DELETE FROM `viewcart` WHERE `userId`='$userId'";   
        $result = mysqli_query($conn, $sql);
        echo "<script>alert('Removed All');
                window.history.back(1);
            </script>";
    }
    if(isset($_POST['checkout'])) {
        $amount = $_POST["amount"];
        $address1 = $_POST["address"];
        $address2 = $_POST["address1"];
        $phone = $_POST["phone"];
        $zipcode = $_POST["zipcode"];
        $password = $_POST["password"];
        $address = $address1 . ", " . $address2;
    
        // Prepare and execute SQL to fetch user details
        $passSql = "SELECT * FROM users WHERE id=:userId";
        $stmt = $pdo->prepare($passSql);
        $stmt->execute(['userId' => $userId]);
        $passRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $passRow['username'];
    
        // Verify password
        if (password_verify($password, $passRow['password'])) {
            // Insert order details into orders table
            $sql = "INSERT INTO `orders` (`userId`, `address`, `zipCode`, `phoneNo`, `amount`, `paymentMode`, `orderStatus`, `orderDate`) VALUES (:userId, :address, :zipcode, :phone, :amount, '0', '0', current_timestamp())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['userId' => $userId, 'address' => $address, 'zipcode' => $zipcode, 'phone' => $phone, 'amount' => $amount]);
            $orderId = $pdo->lastInsertId();
    
            if ($orderId) {
                // Insert order items into orderitems table
                $addSql = "SELECT * FROM `viewcart` WHERE userId=:userId";
                $stmt = $pdo->prepare($addSql);
                $stmt->execute(['userId' => $userId]);
                while ($addrow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pizzaId = $addrow['pizzaId'];
                    $itemQuantity = $addrow['itemQuantity'];
                    $itemSql = "INSERT INTO `orderitems` (`orderId`, `pizzaId`, `itemQuantity`) VALUES (:orderId, :pizzaId, :itemQuantity)";
                    $stmt = $pdo->prepare($itemSql);
                    $stmt->execute(['orderId' => $orderId, 'pizzaId' => $pizzaId, 'itemQuantity' => $itemQuantity]);
                }
    
                // Delete items from viewcart
                $deletesql = "DELETE FROM `viewcart` WHERE `userId`=:userId";
                $stmt = $pdo->prepare($deletesql);
                $stmt->execute(['userId' => $userId]);
    
                // Redirect with success message
                echo '<script>alert("Thanks for ordering with us. Your order id is ' . $orderId . '."); window.location.href="http://localhost/OnlinePizzaDelivery/index.php";</script>';
                exit();
            }
        } else {
            // Password incorrect
            echo '<script>alert("Incorrect Password! Please enter correct Password."); window.history.back(1);</script>';
            exit();
        }
    }
    
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    {
        $pizzaId = $_POST['pizzaId'];
        $qty = $_POST['quantity'];
        $updatesql = "UPDATE `viewcart` SET `itemQuantity`='$qty' WHERE `pizzaId`='$pizzaId' AND `userId`='$userId'";
        $updateresult = mysqli_query($conn, $updatesql);
    }
    
}
?>