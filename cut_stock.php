<?php
require_once 'DBConnection.php';

$id = $_GET['stock_id'] ?? null;

if (!$id) {
    die("Invalid product ID");
}

$product = $conn->query("SELECT * FROM product_list WHERE product_id = $id")->fetch_assoc();
?>

<h3>Cut Product: <?= $product['name'] ?></h3>

<form method="POST" action="save_cut_product.php">
    <input type="hidden" name="parent_id" value="<?= $product['product_id'] ?>">

    <label>New Product Name</label>
    <input type="text" name="name" required class="form-control">

    <label>New SKU</label>
    <input type="text" name="sku" required class="form-control">

    <label>Quantity to Create (from 1 parent)</label>
    <input type="number" name="quantity_per_parent" step="0.01" min="1" required class="form-control">

    <label>Initial Stock Quantity</label>
    <input type="number" name="initial_quantity" min="1" required class="form-control">

    <label>Price</label>
    <input type="number" name="price" step="0.01" required class="form-control">
</form>
