<?php
session_start();
error_reporting(0);
include_once("includes/config.php");
if($_SESSION['userlogin']>0){
    header('location:index.php');
}elseif(isset($_POST['login'])){
    $_SESSION['userlogin'] = $_POST['username'];
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $sql = "SELECT UserName,Password from security where UserName=:username";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username',$username,PDO::PARAM_STR);
    $query-> execute();
    $results=$query->fetchAll(PDO::FETCH_OBJ);
    if($query->rowCount() > 0){
        foreach ($results as $row) {
            $hashpass=$row->Password;
        }
        if (password_verify($password, $hashpass)) {
            $_SESSION['userlogin']=$_POST['username'];
            echo "<script>window.location.href= 'index.php'; </script>";
        }
        else {
            $wrongpassword='<div class="alert alert-danger">Incorrect password.</div>';
        }
    }
    else{
        $wrongusername='<div class="alert alert-danger">User not found.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Admin Login - HRMS</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        body {
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #ff9b44, #fc6075);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .login-btn:hover {
            opacity: 0.9;
        }
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        .forgot-password a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            color: white;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <form method="POST">
            <?php 
            if($wrongusername){
                echo $wrongusername;
            }
            if($wrongpassword){
                echo $wrongpassword;
            }
            ?>
            <div class="form-group">
                <label for="username">User Name</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="login-btn">Login</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();
                if (!username || !password) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                }
            });
        });
    </script>
</body>
</html>