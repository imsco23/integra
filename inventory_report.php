<?php 
$month = isset($_GET['month']) ? $_GET['month'] : date("m");
$year = isset($_GET['year']) ? $_GET['year'] : date("Y");

$from = date("Y-m-01", strtotime("$year-$month-01"));
$to = date("Y-m-t", strtotime($from));
$beginning_of_month = date("Y-m-01", strtotime($from));

// Calculate last month's range for ending balance
$last_month = date("m", strtotime("$from -1 month"));
$last_year = date("Y", strtotime("$from -1 month"));
$from_last_month = date("Y-m-01", strtotime("$last_year-$last_month-01"));
$to_last_month = date("Y-m-t", strtotime($from_last_month));

// Fetch sales data for last 6 months
$sales_months = [];
$sales_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i month"));
    $month_end = date('Y-m-t', strtotime($month_start));

    $sales_qry = $conn->query("SELECT SUM(ti.quantity * ti.price) as total_sales 
        FROM transaction_items ti 
        INNER JOIN transaction_list tl ON ti.transaction_id = tl.transaction_id 
        WHERE date(tl.date_added) BETWEEN '{$month_start}' AND '{$month_end}'");

    $total_sales = 0;
    if ($sales_qry && $sales_qry->num_rows > 0) {
        $res = $sales_qry->fetch_assoc();
        $total_sales = $res['total_sales'] ?? 0;
    }

    $sales_months[] = date('F Y', strtotime($month_start));
    $sales_values[] = $total_sales;
}
?>
<div class="card rounded-0 shadow">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Inventory Report</h3>
    </div>
    <div class="card-body">
        <h5>Filter</h5>
        <div class="row align-items-end">
            <div class="form-group col-md-2">
                <label for="month" class="control-label">Month</label>
                <select name="month" id="month" class="form-control rounded-0">
                    <?php for($m = 1; $m <= 12; $m++): 
                        $m_val = str_pad($m, 2, "0", STR_PAD_LEFT); ?>
                        <option value="<?= $m_val ?>" <?= $month == $m_val ? 'selected' : '' ?>>
                            <?= date("F", mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="year" class="control-label">Year</label>
                <select name="year" id="year" class="form-control rounded-0">
                    <?php 
                    $start_year = 2020;
                    $current_year = date("Y");
                    for($y = $current_year; $y >= $start_year; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group col-md-4 d-flex">
                <div class="col-auto">
                    <button class="btn btn-primary rounded-0" id="filter" type="button"><i class="fa fa-filter"></i> Filter</button>
                    <button class="btn btn-success rounded-0" id="print" type="button"><i class="fa fa-print"></i> Print</button>
                </div>
            </div>
        </div>
        <hr>
        <div id="outprint">
        <table id="inventoryTable" class="table table-hover table-striped table-bordered">
            <colgroup>
                <col width="5%">
                <col width="25%">
                <col width="10%">
                <col width="10%">
                <col width="10%">
                <col width="15%">
                <col width="15%">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center p-0">#</th>
                    <th class="text-center p-0">Product Name</th>
                    <th class="text-center p-0">Stock In</th>
                    <th class="text-center p-0">Stock Out</th>
                    <th class="text-center p-0">Available Stock</th>
                    <th class="text-center p-0">Expiry Date</th>
                    <th class="text-center p-0">Total Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                $qry = $conn->query("SELECT p.*, 
                    (SELECT SUM(quantity) FROM stock_list WHERE product_id = p.product_id AND date(date_added) BETWEEN '{$from}' AND '{$to}') as stock_in,
                    (SELECT SUM(quantity) FROM transaction_items WHERE product_id = p.product_id AND transaction_id IN 
                        (SELECT transaction_id FROM transaction_list WHERE date(date_added) BETWEEN '{$from}' AND '{$to}')) as stock_out,
                    (SELECT SUM(quantity * price) FROM transaction_items WHERE product_id = p.product_id AND transaction_id IN 
                        (SELECT transaction_id FROM transaction_list WHERE date(date_added) BETWEEN '{$from}' AND '{$to}')) as total_sales,
                    (SELECT SUM(quantity * price) FROM transaction_items WHERE product_id = p.product_id AND transaction_id IN 
                        (SELECT transaction_id FROM transaction_list WHERE date(date_added) BETWEEN '{$beginning_of_month}' AND '{$from}')) as beginning_sales,
                    (SELECT expiry_date FROM stock_list WHERE product_id = p.product_id AND quantity > 0 ORDER BY expiry_date ASC LIMIT 1) as expiry_date
                    FROM product_list p 
                    ORDER BY p.name ASC");

                while($row = $qry->fetch_assoc()):
                    $stock_in = $row['stock_in'] ?? 0;
                    $stock_out = $row['stock_out'] ?? 0;
                    $available = $stock_in - $stock_out;

                    // Only show products with available stock
                    if ($available <= 0) continue;

                    $product_sales = $row['total_sales'] ?? 0;
                    $beginning_sales = $row['beginning_sales'] ?? 0;
                    $expiry_date = $row['expiry_date'] ?? 'N/A';

                    $row_class = '';
                    if($expiry_date != 'N/A') {
                        $expiry_timestamp = strtotime($expiry_date);
                        $today_timestamp = strtotime(date("Y-m-d"));
                        $days_to_expiry = ($expiry_timestamp - $today_timestamp) / (60 * 60 * 24);

                        if($days_to_expiry < 0) {
                            $row_class = 'table-danger'; // Expired
                        } elseif($days_to_expiry <= 30) {
                            $row_class = 'table-warning'; // Near expiry
                        }
                        $expiry_date = date("Y-m-d", $expiry_timestamp);
                    }
                ?>
                <tr class="<?= $row_class ?>">
                    <td class="text-center p-0"><?= $i++ ?></td>
                    <td class="py-0 px-1"><?= $row['name'] ?></td>
                    <td class="py-0 px-1 text-end"><?= format_num($stock_in) ?></td>
                    <td class="py-0 px-1 text-end"><?= format_num($stock_out) ?></td>
                    <td class="py-0 px-1 text-end"><?= format_num($available) ?></td>
                    <td class="py-0 px-1 text-center"><?= $expiry_date ?></td>
                    <td class="py-0 px-1 text-end"><?= format_num($product_sales, 2) ?></td>
                </tr>
                <?php endwhile; ?>

                <?php if($i == 1): ?>
                    <tr>
                        <th colspan="7" class="text-center">No inventory records for this month.</th>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php 
        $sales_qry = $conn->query("SELECT SUM(ti.quantity * ti.price) as total_sales 
            FROM transaction_items ti 
            INNER JOIN transaction_list tl ON ti.transaction_id = tl.transaction_id 
            WHERE date(tl.date_added) BETWEEN '{$from}' AND '{$to}'");

        $total_sales = 0;
        if($sales_qry && $sales_qry->num_rows > 0){
            $res = $sales_qry->fetch_assoc();
            $total_sales = $res['total_sales'] ?? 0;
            
        }

        $ending_sales_qry = $conn->query("SELECT SUM(ti.quantity * ti.price) as ending_sales 
            FROM transaction_items ti 
            INNER JOIN transaction_list tl ON ti.transaction_id = tl.transaction_id 
            WHERE date(tl.date_added) BETWEEN '{$from_last_month}' AND '{$to_last_month}'");

        $ending_sales = 0;
        if($ending_sales_qry && $ending_sales_qry->num_rows > 0){
            $res = $ending_sales_qry->fetch_assoc();
            $ending_sales = $res['ending_sales'] ?? 0;
        }

        $beginning_sales_qry = $conn->query("SELECT SUM(ti.quantity * ti.price) as beginning_sales 
            FROM transaction_items ti 
            INNER JOIN transaction_list tl ON ti.transaction_id = tl.transaction_id 
            WHERE date(tl.date_added) BETWEEN '{$beginning_of_month}' AND '{$from}'");

        $beginning_sales = 0;
        if($beginning_sales_qry && $beginning_sales_qry->num_rows > 0){
            $res = $beginning_sales_qry->fetch_assoc();
            $beginning_sales = $res['beginning_sales'] ?? 0;
        }

        $total_stock_in_qry = $conn->query("SELECT SUM(quantity) as total_stock_in FROM stock_list WHERE quantity > 0");
        $total_stock_in = $total_stock_in_qry->fetch_assoc()['total_stock_in'] ?? 0;

// ✅ Corrected Total Stock Out (from transaction_items)
        $total_stock_out_qry = $conn->query("SELECT SUM(ti.quantity) as total_stock_out 
            FROM transaction_items ti 
            INNER JOIN transaction_list tl ON ti.transaction_id = tl.transaction_id 
            WHERE date(tl.date_added) BETWEEN '{$from}' AND '{$to}'");

        $total_stock_out = $total_stock_out_qry->fetch_assoc()['total_stock_out'] ?? 0;
        ?>
        <?php
// Compute total available stock again (since filtered rows only are shown)
$total_available_stock_qry = $conn->query("SELECT 
    SUM(
        (SELECT COALESCE(SUM(quantity), 0) FROM stock_list WHERE product_id = p.product_id AND date(date_added) BETWEEN '{$from}' AND '{$to}') - 
        (SELECT COALESCE(SUM(quantity), 0) FROM transaction_items WHERE product_id = p.product_id AND transaction_id IN 
            (SELECT transaction_id FROM transaction_list WHERE date(date_added) BETWEEN '{$from}' AND '{$to}'))
    ) as total_available
    FROM product_list p");

$total_available_stock = 0;
if ($total_available_stock_qry && $total_available_stock_qry->num_rows > 0) {
    $res = $total_available_stock_qry->fetch_assoc();
    $total_available_stock = $res['total_available'] ?? 0;
}
?>
<tr class="table-info">
    <th colspan="4" class="text-end">Total Available Stock</th>
    <th class="text-end"><?= format_num($total_available_stock) ?></th>
    <th colspan="2"></th>
</tr>

        <hr>
<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Sales Summary</h5>
    </div>
    <div class="card-body">
        <p class="mb-2">Total Sales for <strong><?= date("F Y", strtotime($from)) ?></strong>: <span class="float-end"><strong><?= format_num($total_sales, 2) ?></strong></span></p>
        <p class="mb-2">Ending Sales from Last Month: <span class="float-end"><strong><?= format_num($ending_sales, 2) ?></strong></span></p>
        <p class="mb-0">Beginning Sales for <strong><?= date("F Y", strtotime($from)) ?></strong>: <span class="float-end"><strong><?= format_num($beginning_sales, 2) ?></strong></span></p>
        <p class="mb-0">Total Stock In: <span class="float-end"><strong><?= format_num($total_stock_in, 2) ?></strong></span></p>
        <p class="mb-0">Total Stock Out: <span class="float-end"><strong><?= format_num($total_stock_out, 2) ?></strong></span></p>
    </div>
</div>
    </div>
</div>

<!-- Line Chart for Sales -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Sales Trend (Last 6 Months)</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart"></canvas>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    $('#inventoryTable').DataTable();

    var months = <?php echo json_encode($sales_months); ?>;
    var sales = <?php echo json_encode($sales_values); ?>;

    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Sales',
                data: sales,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales ($)'
                    }
                }
            }
        }
    });
});
</script>
