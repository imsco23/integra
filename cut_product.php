<?php
require_once('DBConnection.php');
if (!isset($_GET['stock_id']) || empty($_GET['stock_id'])) {
    echo "<div class='text-danger'>Invalid stock ID.</div>";
    exit;
}

$stock_id = $_GET['stock_id'];
$qry = $conn->query("SELECT s.*, p.name as pname, p.sku  
                     FROM stock_list s 
                     INNER JOIN product_list p ON s.product_id = p.product_id 
                     WHERE s.stock_id = '{$stock_id}'");

if ($qry->num_rows == 0) {
    echo "<div class='text-danger'>Stock record not found.</div>";
    exit;
}

$stock = $qry->fetch_assoc();
?>

<div class="container-fluid">
    <form id="cut-stock-form">
        <input type="hidden" name="stock_id" value="<?= $stock_id ?>">
        
        <div class="mb-3">
            <label class="form-label">Original Product</label>
            <input type="text" class="form-control" value="<?= $stock['sku'] . ' - ' . $stock['pname'] ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Available Quantity</label>
            <input type="text" class="form-control" value="<?= $stock['quantity'] ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Quantity to Cut</label>
            <input type="number" step="any" name="cut_quantity" class="form-control" required max="<?= $stock['quantity'] ?>">
        </div>

        <hr>

        <div class="mb-3">
            <label class="form-label">New Product SKU</label>
            <input type="text" name="new_sku" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">New Product Name</label>
            <input type="text" name="new_name" class="form-control" required>
        </div>
    </form>
</div>

<script>
    $('#cut-stock-form').submit(function(e){
        e.preventDefault();
        var _this = $(this);
        var formData = _this.serialize();
        _this.find('button').attr('disabled', true);

        $.ajax({
            url: './Actions.php?a=cut_stock',
            method: 'POST',
            data: formData,
            dataType: 'json',
            error: function(err){
                console.log(err);
                alert("An error occurred.");
                _this.find('button').attr('disabled', false);
            },
            success: function(resp){
                if(resp.status === 'success'){
                    alert("Stock successfully cut.");
                    location.reload();
                } else {
                    alert(resp.msg || "Failed to cut stock.");
                    _this.find('button').attr('disabled', false);
                }
            }
        });
    });
</script>
