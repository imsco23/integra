<?php
require_once 'DBConnection.php';

$parent_id = $_POST['parent_id'];
$name = $_POST['name'];
$sku = $_POST['sku'];
$quantity_per_parent = $_POST['quantity_per_parent'];
$initial_quantity = $_POST['initial_quantity'];
$price = $_POST['price'];

// Get parent product details
$parent = $conn->query("SELECT * FROM product_list WHERE product_id = $parent_id")->fetch_assoc();
$category_id = $parent['category_id'];
$supplier_id = $parent['supplier_id'];
$alert = $parent['alert_restock'];

// Insert new cut product
$stmt = $conn->prepare("INSERT INTO product_list (name, sku, category_id, supplier_id, price, alert_restock, status, parent_id, quantity_per_parent)
VALUES (?, ?, ?, ?, ?, ?, '1', ?, ?)");
$stmt->bind_param("ssiidiii", $name, $sku, $category_id, $supplier_id, $price, $alert, $parent_id, $quantity_per_parent);
$stmt->execute();
$new_product_id = $stmt->insert_id;
$stmt->close();

// Add stock for cut product
$conn->query("INSERT INTO stock_list (product_id, quantity) VALUES ($new_product_id, $initial_quantity)");

// Deduct 1 from parent product stock
$conn->query("UPDATE stock_list SET quantity = quantity - 1 WHERE product_id = $parent_id");

header("Location: product_list.php");
