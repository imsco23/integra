<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location:./");
    exit;
}
require_once('DBConnection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Integra Medica Solutions Corp. Management System</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/bootstrap.bundle.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('./images/login.jpeg') no-repeat center center fixed;
            background-size: cover;
            filter: blur(5px);
            z-index: -1;
        }

        #sys_title {
            font-size: 4em;
            text-shadow: 2px 2px 8px #000;
        }
        @media (max-width: 700px) {
            #sys_title {
                font-size: 2em !important;
            }
        }

        .card {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .card .form-control {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .card label {
            color: #f8f9fa;
        }

        #login-msg.alert {
            color: white;
        }

        .btn-orange {
            background-color: #fd7e14;
            border-color: #fd7e14;
            color: white;
        }

        .btn-orange:hover {
            background-color: #e96b0f;
            border-color: #e96b0f;
            color: white;
        }
    </style>
</head>
<body>
    <div class="d-flex flex-column justify-content-center align-items-center h-100">
        <h1 class="text-center text-light px-3 py-4" id="sys_title">Integra Medica Solutions Corporation <br> Inventory Management System</h1>
        <div class="card shadow-lg w-100" style="max-width: 400px;">
            <div class="card-body">
                <form id="login-form" autocomplete="off">
                    <div id="login-msg" class="mb-3"></div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="new-password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-orange">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
$(function () {
    $('#login-form').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $msgBox = $('#login-msg');

        $msgBox.html('').removeClass('alert alert-success alert-danger');
        $form.find('button').prop('disabled', true).text('Logging in...');

        $.ajax({
            url: './Actions.php?a=login',
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 'success') {
                    $msgBox.addClass('alert alert-success').text(resp.msg);
                    setTimeout(() => location.replace('./'), 1500);
                } else {
                    $msgBox.addClass('alert alert-danger').text(resp.msg);
                }
            },
            error: function () {
                $msgBox.addClass('alert alert-danger').text('An unexpected error occurred. Please try again.');
            },
            complete: function () {
                $form.find('button').prop('disabled', false).text('Login');
            }
        });
    });
});
</script>
</body>
</html>
