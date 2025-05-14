<?php 
    session_start();
    require_once('DBConnection.php');

    Class Actions extends DBConnection{
        function __construct(){
            parent::__construct();
        }
        function __destruct(){
            parent::__destruct();
        }
        function login(){
            extract($_POST);
            $sql = "SELECT * FROM user_list where username = '{$username}' and `password` = '".md5($password)."' ";
            @$qry = $this->db->query($sql)->fetch_array();
            if(!$qry){
                $resp['status'] = "failed";
                $resp['msg'] = "Invalid username or password.";
            }else{
                $resp['status'] = "success";
                $resp['msg'] = "Login successfully.";
                foreach($qry as $k => $v){
                    if(!is_numeric($k))
                    $_SESSION[$k] = $v;
                }
            }
            return json_encode($resp);
        }  
        function logout(){
            session_destroy();
            header("location:./");
        }
        function save_user() {
            $resp = ['status' => 'failed', 'msg' => 'Unknown error occurred.'];
            
            // Extract and sanitize
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $username = isset($_POST['username']) ? $this->db->real_escape_string(trim($_POST['username'])) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
            // Check for duplicate username
            $check_sql = "SELECT COUNT(user_id) as `count` FROM user_list WHERE `username` = '{$username}'" . ($id > 0 ? " AND user_id != '{$id}'" : "");
            $check_result = $this->db->query($check_sql);
        
            if (!$check_result) {
                $resp['msg'] = "Error checking for duplicate username: " . $this->db->error;
                return json_encode($resp);
            }
        
            $check = $check_result->fetch_array()['count'];
            if ($check > 0) {
                $resp['msg'] = "Username already exists.";
                return json_encode($resp);
            }
        
            $data = "";
            $cols = [];
            $values = [];
        
            foreach ($_POST as $k => $v) {
                if ($k == 'id') continue;
        
                $v = $this->db->real_escape_string(trim($v));
        
                if ($k == 'password') {
                    if ($id == 0 && !empty($v)) {
                        // Only add password if creating new user
                        $cols[] = "`password`";
                        $values[] = "'" . md5($v) . "'";
                    } elseif ($id > 0 && !empty($v)) {
                        // Allow password update
                        if (!empty($data)) $data .= ", ";
                        $data .= "`password` = '" . md5($v) . "'";
                    }
                } else {
                    if ($id > 0) {
                        if (!empty($data)) $data .= ", ";
                        $data .= "`{$k}` = '{$v}'";
                    } else {
                        $cols[] = "`{$k}`";
                        $values[] = "'{$v}'";
                    }
                }
            }
        
            if ($id > 0) {
                // Update user
                $sql = "UPDATE `user_list` SET {$data} WHERE user_id = '{$id}'";
            } else {
                // Insert new user
                $sql = "INSERT INTO `user_list` (" . implode(",", $cols) . ") VALUES (" . implode(",", $values) . ")";
            }
        
            $save = $this->db->query($sql);
            if ($save) {
                $resp['status'] = 'success';
                $resp['msg'] = $id > 0 ? 'User Details successfully updated.' : 'New User successfully saved.';
            } else {
                $resp['msg'] = 'Saving User Details Failed. Error: ' . $this->db->error;
                $resp['sql'] = $sql; // For debugging
            }
        
            return json_encode($resp);
        }    
        function delete_user(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `user_list` where user_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'User successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function update_credentials(){
            extract($_POST);
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id','old_password')) && !empty($v)){
                    if(!empty($data)) $data .= ",";
                    if($k == 'password') $v = md5($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            if(!empty($password) && md5($old_password) != $_SESSION['password']){
                $resp['status'] = 'failed';
                $resp['msg'] = "Old password is incorrect.";
            }else{
                $sql = "UPDATE `user_list` set {$data} where user_id = '{$_SESSION['user_id']}'";
                @$save = $this->db->query($sql);
                if($save){
                    $resp['status'] = 'success';
                    $_SESSION['flashdata']['type'] = 'success';
                    $_SESSION['flashdata']['msg'] = 'Credential successfully updated.';
                    foreach($_POST as $k => $v){
                        if(!in_array($k,array('id','old_password')) && !empty($v)){
                            if(!empty($data)) $data .= ",";
                            if($k == 'password') $v = md5($v);
                            $_SESSION[$k] = $v;
                        }
                    }
                }else{
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Updating Credentials Failed. Error: '.$this->db->error;
                    $resp['sql'] =$sql;
                }
            }
            return json_encode($resp);
        }
        function save_category(){
            extract($_POST);
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    $v = addslashes(trim($v));
                if(empty($id)){
                    $cols[] = "`{$k}`";
                    $vals[] = "'{$v}'";
                }else{
                    if(!empty($data)) $data .= ", ";
                    $data .= " `{$k}` = '{$v}' ";
                }
                }
            }
            if(isset($cols) && isset($vals)){
                $cols_join = implode(",",$cols);
                $vals_join = implode(",",$vals);
            }
            if(empty($id)){
                $sql = "INSERT INTO `category_list` ({$cols_join}) VALUES ($vals_join)";
            }else{
                $sql = "UPDATE `category_list` set {$data} where category_id = '{$id}'";
            }
            @$check= $this->db->query("SELECT COUNT(category_id) as count from `category_list` where `name` = '{$name}' ".($id > 0 ? " and category_id != '{$id}'" : ""))->fetch_array()['count'];
            if(@$check> 0){
                $resp['status'] ='failed';
                $resp['msg'] = 'Category already exists.';
            }else{
                @$save = $this->db->query($sql);
                if($save){
                    $resp['status']="success";
                    if(empty($id))
                        $resp['msg'] = "Category successfully saved.";
                    else
                        $resp['msg'] = "Category successfully updated.";
                }else{
                    $resp['status']="failed";
                    if(empty($id))
                        $resp['msg'] = "Saving New Category Failed.";
                    else
                        $resp['msg'] = "Updating Category Failed.";
                    $resp['error']=$this->db->error;
                }
            }
            return json_encode($resp);
        }
        function delete_category(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `category_list` where category_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Stock successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function save_supplier(){
            extract($_POST);
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    $v = addslashes(trim($v));
                if(empty($id)){
                    $cols[] = "`{$k}`";
                    $vals[] = "'{$v}'";
                }else{
                    if(!empty($data)) $data .= ", ";
                    $data .= " `{$k}` = '{$v}' ";
                }
                }
            }
            if(isset($cols) && isset($vals)){
                $cols_join = implode(",",$cols);
                $vals_join = implode(",",$vals);
            }
            if(empty($id)){
                $sql = "INSERT INTO `supplier_list` ({$cols_join}) VALUES ($vals_join)";
            }else{
                $sql = "UPDATE `supplier_list` set {$data} where supplier_id = '{$id}'";
            }
            @$check= $this->db->query("SELECT COUNT(supplier_id) as count from `supplier_list` where `name` = '{$name}' ".($id > 0 ? " and supplier_id != '{$id}'" : ""))->fetch_array()['count'];
            if(@$check> 0){
                $resp['status'] ='failed';
                $resp['msg'] = 'Supplier already exists.';
            }else{
                @$save = $this->db->query($sql);
                if($save){
                    $resp['status']="success";
                    if(empty($id))
                        $resp['msg'] = "Supplier successfully saved.";
                    else
                        $resp['msg'] = "Supplier successfully updated.";
                }else{
                    $resp['status']="failed";
                    if(empty($id))
                        $resp['msg'] = "Saving New Supplier Failed.";
                    else
                        $resp['msg'] = "Updating Supplier Failed.";
                    $resp['error']=$this->db->error;
                }
            }
            return json_encode($resp);
        }
        function delete_supplier(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `supplier_list` where supplier_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Stock successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function save_product() {
            extract($_POST);
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k, array('id'))){
                    $v = addslashes(trim($v));
                    if(empty($id)){
                        $cols[] = "`{$k}`";
                        $vals[] = "'{$v}'";
                    }else{
                        if(!empty($data)) $data .= ", ";
                        $data .= " `{$k}` = '{$v}' ";
                    }
                }
            }
        
            if(isset($cols) && isset($vals)){
                $cols_join = implode(",", $cols);
                $vals_join = implode(",", $vals);
            }
        
            if(empty($id)){
                $sql = "INSERT INTO `product_list` ({$cols_join}) VALUES ($vals_join)";
            }else{
                $sql = "UPDATE `product_list` SET {$data} WHERE product_id = '{$id}'";
            }
        
            @$save = $this->db->query($sql);
            if($save){
                $resp['status'] = "success";
                $resp['msg'] = empty($id) ? "Product successfully saved." : "Product successfully updated.";
            }else{
                $resp['status'] = "failed";
                $resp['msg'] = empty($id) ? "Saving New Product Failed." : "Updating Product Failed.";
                $resp['error'] = $this->db->error;
            }
        
            // Bulk add products (if any)
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $product) {
                    $stmt = $conn->prepare("INSERT INTO product_list (sku, product_code, name, price, category_id, description, status) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssdiss", $product['sku'], $product['product_code'], $product['name'], $product['price'], 
                                      $product['category_id'], $product['description'], $product['status']);
                    if ($stmt->execute()) {
                        // optional: handle success for each product
                    } else {
                        echo json_encode(['status' => 'error', 'msg' => 'Failed to add some products.']);
                        exit();
                    }
                }
                echo json_encode(['status' => 'success', 'msg' => 'Products added successfully.']);
            }
        
            return json_encode($resp);
        }
        
        function delete_product(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `product_list` where product_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Stock successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function save_stock(){
            extract($_POST);
            $resp = [];
        
            // ✅ Prepare data
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k, array('id'))){
                    $v = addslashes(trim($v));
                    if(empty($id)){
                        $cols[] = "`{$k}`";
                        $vals[] = "'{$v}'";
                    } else {
                        if(!empty($data)) $data .= ", ";
                        $data .= " `{$k}` = '{$v}' ";
                    }
                }
            }
        
            if(isset($cols) && isset($vals)){
                $cols_join = implode(",", $cols);
                $vals_join = implode(",", $vals);
            }
        
            // ✅ Run query
            if(empty($id)){
                $sql = "INSERT INTO `stock_list` ($cols_join) VALUES ($vals_join)";
            } else {
                $sql = "UPDATE `stock_list` SET {$data} WHERE stock_id = '{$id}'";
            }
        
            @$save = $this->db->query($sql);
            if($save){
                $resp['status'] = "success";
                $resp['msg'] = empty($id) ? "Stock successfully saved." : "Stock successfully updated.";
            } else {
                $resp['status'] = "failed";
                $resp['msg'] = empty($id) ? "Saving New Stock Failed." : "Updating Stock Failed.";
                $resp['error'] = $this->db->error;
            }
        
            return json_encode($resp);
        }
        
        function delete_stock(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `stock_list` where stock_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Stock successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function save_transaction(){
            extract($_POST);
            $data = "";
            $receipt_no = time();
            $i = 0;
            while(true){
                $i++;
                $chk = $this->db->query("SELECT count(transaction_id) `count` FROM `transaction_list` where receipt_no = '{$receipt_no}' ")->fetch_array()['count'];
                if($chk > 0){
                    $receipt_no = time().$i;
                }else{
                    break;
                }
            }
            $_POST['receipt_no'] = $receipt_no;
            $_POST['user_id'] = $_SESSION['user_id'];
        
            // Sanitize and assign all fields including new ones
            $cols = $vals = [];
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id')) && !is_array($v)){
                    $v = addslashes(trim($v));
                    $cols[] = "`{$k}`";
                    $vals[] = "'{$v}'";
                }
            }
        
            if(!empty($cols) && !empty($vals)){
                $cols_join = implode(",",$cols);
                $vals_join = implode(",",$vals);
            }
        
            $sql = "INSERT INTO `transaction_list` ({$cols_join}) VALUES ({$vals_join})";
        
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                $_SESSION['flashdata']['type']="success";
                $_SESSION['flashdata']['msg'] = "Transaction successfully saved.";
                $last_id = $this->db->insert_id;
                $tid = $last_id;
        
                $data ="";
                foreach($product_id as $k => $v){
                    if(!empty($data)) $data .=",";
                    $data .= "('{$tid}','{$v}','{$quantity[$k]}','{$price[$k]}')";
                }
                if(!empty($data)){
                    $this->db->query("DELETE FROM transaction_items where transaction_id = '{$tid}'");
                    $sql = "INSERT INTO transaction_items (`transaction_id`,`product_id`,`quantity`,`price`) VALUES {$data}";
                    $save = $this->db->query($sql);
                }
                $resp['transaction_id'] = $tid;
            }else{
                $resp['status']="failed";
                $resp['msg'] = "Saving Transaction Failed.";
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
        function delete_transaction(){
            extract($_POST);

            @$delete = $this->db->query("DELETE FROM `transaction_list` where transaction_id = '{$id}'");
            if($delete){
                $resp['status']='success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Transaction successfully deleted.';
            }else{
                $resp['status']='failed';
                $resp['error']=$this->db->error;
            }
            return json_encode($resp);
        }
    }
    $a = isset($_GET['a']) ?$_GET['a'] : '';
    $action = new Actions();
    switch($a){
        case 'login':
            echo $action->login();
        break;
        case 'customer_login':
            echo $action->customer_login();
        break;
        case 'logout':
            echo $action->logout();
        break;
        case 'customer_logout':
            echo $action->customer_logout();
        break;
        case 'save_user':
            echo $action->save_user();
        break;
        case 'delete_user':
            echo $action->delete_user();
        break;
        case 'update_credentials':
            echo $action->update_credentials();
        break;
        case 'save_category':
            echo $action->save_category();
        break;
        case 'delete_category':
            echo $action->delete_category();
        break;
        case 'save_supplier':
            echo $action->save_supplier();
        break;
        case 'delete_supplier':
            echo $action->delete_supplier();
        break;
        case 'save_product':
            echo $action->save_product();
        break;
        case 'delete_product':
            echo $action->delete_product();
        break;
        case 'save_stock':
            echo $action->save_stock();
        break;
        case 'delete_stock':
            echo $action->delete_stock();
        break;
        case 'save_transaction':
            echo $action->save_transaction();
        break;
        case 'delete_transaction':
            echo $action->delete_transaction();
        break;
        default:
        // default action here
        break;
    }