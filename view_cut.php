<?php
require_once('DBConnection.php');
$parent_id = $_GET['parent_id'];
$qry = $conn->query("SELECT * FROM stock_list WHERE parent_stock_id = '{$parent_id}' ORDER BY date_added ASC");
?>
<div class="container-fluid">
    <h5 class="mb-3">Cut History</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Quantity</th>
                <th>Description</th>
                <th>Expiry</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while($row = $qry->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= date("Y-m-d", strtotime($row['date_added'])) ?></td>
                <td class="text-end"><?= format_num($row['quantity']) ?></td>
                <td><?= $row['cut_from'] ?: 'N/A' ?></td>
                <td><?= date("Y-m-d", strtotime($row['expiry_date'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
