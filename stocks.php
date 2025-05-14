 <div class="card rounded-0 shadow">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Stock List</h3>
        <div class="card-tools align-middle">
            <button class="btn btn-dark btn-sm py-1 rounded-0" type="button" id="create_new">Add New</button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-hover table-striped table-bordered">
            <colgroup>
                <col width="5%">
                <col width="10%">
                <col width="40%">
                <col width="10%">
                <col width="10%">
                <col width="10%">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-center p-0">#</th>
                    <th class="text-center p-0">Date Added</th>
                    <th class="text-center p-0">Product</th>
                    <th class="text-center p-0">Quantity</th>
                    <th class="text-center p-0">Expiry</th>
                    <th class="text-center p-0">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sql = "SELECT s.*, p.name as pname, p.sku  
                FROM `stock_list` s 
                inner join `product_list` p on s.product_id = p.product_id 
                where p.delete_flag = 0 
                order by unix_timestamp(s.date_added) desc";
                $qry = $conn->query($sql);
                $i = 1;
                    while($row = $qry->fetch_assoc()):
                ?>
                <tr class="<?php echo strtotime(date("Y-m-d")) > strtotime($row['expiry_date']) ? 'bg-danger bg-opacity-50' : '' ?>">
                    <td class="text-center p-0"><?php echo $i++; ?></td>
                    <td class="py-0 px-1"><?php echo date("Y-m-d",strtotime($row['date_added'])) ?></td>
                    <td class="py-0 px-1">
                        <div class="fs-6 fw-bold truncate-1" title="<?php echo $row['sku'] ?>"><?php echo $row['sku'] ?></div>
                        <div class="fs-6 fw-light truncate-1" title="<?php echo $row['pname'] ?>"><?php echo $row['pname'] ?></div>
                    </td>
                    <td class="py-0 px-1 text-end"><?php echo format_num($row['quantity']) ?></td>
                    <td class="py-0 px-1 text-center"><?php echo date("Y-m-d",strtotime($row['expiry_date'])) ?></td>
                    <td class="text-center py-0 px-1">
                        <div class="btn-group" role="group">
                            <a href="javascript:void(0)" class="btn btn-primary btn-sm edit_data" data-id="<?= $row['stock_id'] ?>"><i class="fa fa-edit"></i></a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-sm delete_data" data-id="<?= $row['stock_id'] ?>" data-name="<?= $row['pname']." - ".$row['sku'] ?>"><i class="fa fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    $(function(){
        $('#create_new').click(function(){
            uni_modal('Add New Stock',"manage_stock.php")
        })
        $('.edit_data').click(function(){
            uni_modal('Edit Stock Details',"manage_stock.php?id="+$(this).attr('data-id'))
        })
        $('.view_data').click(function(){
            uni_modal('Stock Details',"view_stock.php?id="+$(this).attr('data-id'),'')
        })
        $('.delete_data').click(function(){
            _conf("Are you sure to delete <b>"+$(this).attr('data-name')+"</b> from Stock List?",'delete_data',[$(this).attr('data-id')])
        })
        $('table td,table th').addClass('align-middle')
        $('table').dataTable({
            columnDefs: [
                { orderable: false, targets:3 }
            ]
        })
    })
    function delete_data($id){
        $('#confirm_modal button').attr('disabled',true)
        $.ajax({
            url:'./Actions.php?a=delete_stock',
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
