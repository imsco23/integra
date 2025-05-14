<style>
    input[type="text"], textarea {
        text-transform: uppercase;
    }

    table td, table th {
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .table th, .table td {
            font-size: 12px;
            padding: 4px;
        }
    }
</style>

<div class="card rounded-0 shadow">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Product List</h3>
        <div class="card-tools align-middle">
            <button class="btn btn-dark btn-sm py-1 rounded-0" type="button" id="create_new">Add New</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered w-100">
                <colgroup>
                    <col width="5%">
                    <col width="25%">
                    <col width="15%">
                    <col width="10%">
                    <col width="15%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="15%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Product Code</th>
                        <th>SKU</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Alert</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sql = "SELECT p.*, c.name as cname, s.name as sname 
                            FROM `product_list` p 
                            INNER JOIN `category_list` c ON p.category_id = c.category_id 
                            INNER JOIN `supplier_list` s ON p.supplier_id = s.supplier_id 
                            WHERE p.delete_flag = 0 
                            ORDER BY p.name ASC";
                    $qry = $conn->query($sql);
                    $i = 1;
                    while($row = $qry->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="text-center p-0"><?php echo $i++; ?></td>
                        <td class="py-0 px-1">
                            <div class="fs-6 fw-bold truncate-1" title="<?php echo $row['name'] ?>"><?php echo $row['name'] ?></div>
                            <div class="fs-6 fw-light truncate-3" title="<?php echo $row['description'] ?>"><?php echo $row['description'] ?></div>
                        </td>
                        <td class="py-0 px-1"><?php echo $row['cname'] ?></td>
                        <td class="py-0 px-1"><?php echo $row['product_code'] ?></td>
                        <td class="py-0 px-1"><?php echo $row['sku'] ?></td>
                        <td class="py-0 px-1"><?php echo $row['sname'] ?></td>
                        <td class="py-0 px-1 text-end"><?php echo number_format($row['price']) ?></td>
                        <td class="py-0 px-1 text-end"><?php echo number_format($row['alert_restock']) ?></td>
                        <td class="py-0 px-1 text-center">
                            <?php 
                            if($row['status'] == 1){
                                echo '<span class="py-1 px-3 badge rounded-pill bg-success"><small>Active</small></span>';
                            } else {
                                echo '<span class="py-1 px-3 badge rounded-pill bg-danger"><small>Inactive</small></span>';
                            }
                            ?>
                        </td>
                        <td class="text-center py-0 px-1">
                            <div class="btn-group" role="group">
                                <a href="javascript:void(0)" class="btn btn-info btn-sm view_data" data-id="<?= $row['product_id'] ?>"><i class="fa fa-th-list"></i></a>
                                <a href="javascript:void(0)" class="btn btn-primary btn-sm edit_data" data-id="<?= $row['product_id'] ?>" data-name="<?= $row['name'] ?>"><i class="fa fa-edit"></i></a>
                                <a href="javascript:void(0)" class="btn btn-danger btn-sm delete_data" data-id="<?= $row['product_id'] ?>" data-name="<?= $row['name'] ?>"><i class="fa fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function(){
    $('#create_new').click(function(){
        uni_modal('Add New Product', "manage_product.php", 'mid-large');
    });

    $('.edit_data').click(function(){
        uni_modal('Edit Product Details', "manage_product.php?id=" + $(this).data('id'), 'mid-large');
    });

    $('.view_data').click(function(){
        uni_modal('Product Details', "view_product.php?id=" + $(this).data('id'), '');
    });

    $('.delete_data').click(function(){
        _conf("Are you sure to delete <b>" + $(this).data('name') + "</b> from Product List?", 'delete_data', [$(this).data('id')]);
    });

    $('table td, table th').addClass('align-middle');

    $('table').dataTable({
        columnDefs: [
            { orderable: false, targets: 9 }
        ]
    });

    $(document).on('input', 'input[type="text"], textarea', function(){
        this.value = this.value.toUpperCase();
    });
});

function delete_data($id){
    $('#confirm_modal button').attr('disabled', true);
    $.ajax({
        url: './Actions.php?a=delete_product',
        method: 'POST',
        data: { id: $id },
        dataType: 'JSON',
        error: err => {
            console.log(err);
            alert("An error occurred.");
            $('#confirm_modal button').attr('disabled', false);
        },
        success: function(resp){
            if(resp.status == 'success'){
                location.reload();
            } else {
                alert("An error occurred.");
                $('#confirm_modal button').attr('disabled', false);
            }
        }
    })
}
</script>
