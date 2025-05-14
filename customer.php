<?php 
$dfrom = $_GET['date_from'] ?? date("Y-m-d", strtotime("-1 month"));
$dto = $_GET['date_to'] ?? date("Y-m-d");
$page = max(1, (int)($_GET['pg'] ?? 1));
$per_page = max(1, (int)($_GET['per_page'] ?? 10));
$offset = ($page - 1) * $per_page;

// Count unique patients
$total_qry = $conn->query("SELECT COUNT(DISTINCT patient_name) as total FROM transaction_list WHERE date(date_added) BETWEEN '$dfrom' AND '$dto'");
$total = $total_qry->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total / $per_page);

// Main Query
$qry = $conn->query("
    SELECT 
        tl1.patient_name,
        tl2.surgeon,
        tl2.hospital,
        tl2.technician,
        tl2.remarks,
        tl2.sets
    FROM (
        SELECT patient_name, MAX(date_added) as latest_date
        FROM transaction_list 
        WHERE date(date_added) BETWEEN '$dfrom' AND '$dto'
        GROUP BY patient_name
        LIMIT $per_page OFFSET $offset
    ) tl1
    JOIN transaction_list tl2 
        ON tl2.patient_name = tl1.patient_name AND tl2.date_added = tl1.latest_date
    ORDER BY tl2.date_added DESC
");
?>
<div class="card rounded-0 shadow">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Customer Report</h3>
        <button class="btn btn-success rounded-0" id="print"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>
                Page 
                <button class="btn btn-sm btn-outline-secondary" onclick="goPage(<?= $page - 1 ?>)" <?= $page == 1 ? 'disabled' : '' ?>>&#8249;</button>
                <input type="number" min="1" max="<?= $total_pages ?>" value="<?= $page ?>" id="pg_input" class="form-control d-inline-block text-center" style="width:60px;" onchange="goPage(this.value)">
                <button class="btn btn-sm btn-outline-secondary" onclick="goPage(<?= $page + 1 ?>)" <?= $page >= $total_pages ? 'disabled' : '' ?>>&#8250;</button>
                of <?= $total_pages ?> |
                View 
                <select id="per_page" onchange="goPage(1)" class="form-select d-inline-block" style="width:auto;">
                    <?php foreach([10, 25, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select> records | Found total <?= $total ?> records
            </div>
        </div>

        <div id="outprint">
            <table class="table table-hover table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th class="text-center p-0">#</th>
                        <th class="text-center p-0">Customer Name</th>
                        <th class="text-center p-0">Surgeon</th>
                        <th class="text-center p-0">Hospital</th>
                        <th class="text-center p-0">Technician</th>
                        <th class="text-center p-0">Remarks</th>
                        <th class="text-center p-0">Orders</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($qry && $qry->num_rows > 0): ?>
                        <?php $i = $offset + 1; ?>
                        <?php while($row = $qry->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center p-0"><?= $i++ ?></td>
                            <td class="py-0 px-1"><?= htmlspecialchars($row['patient_name']) ?></td>
                            <td class="py-0 px-1"><?= htmlspecialchars($row['surgeon']) ?></td>
                            <td class="py-0 px-1"><?= htmlspecialchars($row['hospital']) ?></td>
                            <td class="py-0 px-1"><?= htmlspecialchars($row['technician']) ?></td>
                            <td class="py-0 px-1"><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                            <td class="py-0 px-1"><?= nl2br(htmlspecialchars($row['sets'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No customer records for selected date range.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td class="text-center" colspan="2">TOTAL</td>  
                        <td colspan="5">Customers: <?= $qry->num_rows ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function goPage(pg){
    const per_page = document.getElementById('per_page').value;
    location.href = `./?page=customers&pg=${pg}&per_page=${per_page}`;
}

document.getElementById('print').addEventListener('click', () => {
    const range = `<?= date('M d, Y', strtotime($dfrom)) ?><?= $dfrom !== $dto ? " - " . date('M d, Y', strtotime($dto)) : '' ?>`;
    const content = document.getElementById('outprint').outerHTML;
    const printWindow = window.open('', '', 'width=800,height=900');
    printWindow.document.write(`<html><head><title>Print</title></head><body><div style="text-align:center;font-weight:bold;">Customer Report<br/>As of<br/>${range}</div><hr/>${content}</body></html>`);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        setTimeout(() => printWindow.close(), 150);
    }, 200);
});
</script>
