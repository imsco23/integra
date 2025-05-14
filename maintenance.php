<div class="card shadow-sm rounded-0">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-2 mb-md-0">List of Categories</h5>
            <a href="javascript:void(0)" id="new_category" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Create New
            </a>
        </div>
        <div class="row mt-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <label class="form-label mb-1" for="show_entries">Show 
                    <select id="show_entries" class="form-select form-select-sm d-inline w-auto mx-1">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                entries</label>
            </div>
            <div class="col-md-6 text-md-end d-flex justify-content-md-end align-items-center gap-2">
                <input type="text" id="search_supplier" class="form-control form-control-sm w-auto" placeholder="Search..." />
                <button class="btn btn-sm btn-secondary" id="search_button"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0" id="category_table">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $sup_qry = $conn->query("SELECT * FROM `category_list` WHERE delete_flag = 0 ORDER BY `name` ASC");
                    while($row = $sup_qry->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['name'] ?></td>
                        <td>
                            <?php if($row['status'] == 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0)" class="btn btn-info btn-sm view_category" data-id="<?= $row['category_id'] ?>"><i class="fa fa-th-list"></i></a>
                            <a href="javascript:void(0)" class="btn btn-primary btn-sm edit_category" data-id="<?= $row['category_id'] ?>" data-name="<?= $row['name'] ?>"><i class="fa fa-edit"></i></a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-sm delete_category" data-id="<?= $row['category_id'] ?>" data-name="<?= $row['name'] ?>"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($sup_qry->num_rows <= 0): ?>
                    <tr>
                        <td colspan="6" class="text-center">No data available in table</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="table_info" class="text-muted small">Showing 0 to 0 of 0 entries</div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-1" id="prev_page" disabled>Previous</button>
                    <button class="btn btn-sm btn-outline-secondary" id="next_page" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    // Supplier modals
    $('#new_category').click(function(){
        uni_modal('Add New Supplier',"manage_category.php")
    })
    $('.edit_category').click(function(){
        uni_modal('Edit Supplier Details',"manage_category.php?id="+$(this).attr('data-id'))
    })
    $('.view_category').click(function(){
        uni_modal('Supplier Details',"view_category.php?id="+$(this).attr('data-id'))
    })
    $('.delete_category').click(function(){
        _conf("Are you sure to delete <b>"+$(this).attr('data-name')+"</b> from Supplier List?",'delete_category',[$(this).attr('data-id')])
    })

    // Pagination + Search
    let currentPage = 1;
    let rowsPerPage = parseInt($('#show_entries').val());
    let filteredRows = [];

    function paginateTable() {
        let rows = filteredRows.length ? filteredRows : $('#category_table tbody tr');
        let totalRows = rows.length;
        let totalPages = Math.ceil(totalRows / rowsPerPage);

        $('#category_table tbody tr').hide(); // hide all first

        let start = (currentPage - 1) * rowsPerPage;
        let end = start + rowsPerPage;
        rows.slice(start, end).show();

        $('#table_info').text(
            totalRows === 0
                ? `Showing 0 to 0 of 0 entries`
                : `Showing ${start + 1} to ${Math.min(end, totalRows)} of ${totalRows} entries`
        );

        $('#prev_page').prop('disabled', currentPage === 1);
        $('#next_page').prop('disabled', currentPage >= totalPages);
    }

    function filterSupplierTable() {
        let value = $('#search_supplier').val().toLowerCase();
        filteredRows = $('#category_table tbody tr').filter(function () {
            return $(this).text().toLowerCase().indexOf(value) > -1;
        });

        currentPage = 1;
        paginateTable();
    }

    $('#show_entries').change(function(){
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        paginateTable();
    });

    $('#prev_page').click(function(){
        if(currentPage > 1){
            currentPage--;
            paginateTable();
        }
    });

    $('#next_page').click(function(){
        let rows = filteredRows.length ? filteredRows : $('#category_table tbody tr');
        let totalPages = Math.ceil(rows.length / rowsPerPage);
        if(currentPage < totalPages){
            currentPage++;
            paginateTable();
        }
    });

    $('#search_supplier').on('keyup', filterSupplierTable);
    $('#search_button').on('click', filterSupplierTable);

    paginateTable(); // Initial render
})

function delete_category($id){
    $('#confirm_modal button').attr('disabled',true)
    $.ajax({
        url:'./Actions.php?a=delete_category',
        method:'POST',
        data:{id:$id},
        dataType:'JSON',
        error:err=>{
            console.log(err)
            alert("An error occurred.")
            $('#confirm_modal button').attr('disabled',false)
        },
        success:function(resp){
            if(resp.status == 'success'){
                location.reload()
            }else{
                alert("An error occurred.")
                $('#confirm_modal button').attr('disabled',false)
            }
        }
    })
}
</script>
