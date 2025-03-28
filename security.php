<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("includes/config.php");

if(strlen($_SESSION['userlogin'])==0){
    header('location:login.php');
    exit();
}

$message = '';

if(isset($_POST['change_password'])){
    $username = $_SESSION['userlogin'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    $sql = "SELECT Password FROM security WHERE username=:username";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    if(!$query->execute()){
        error_log("Database error: " . implode(", ", $query->errorInfo()));
        $message = '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
    } else {
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if($result && $current_password === $result['Password']){
            if($new_password === $confirm_password){
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_sql = "UPDATE security SET Password=:password WHERE username=:username";
                $update_query = $dbh->prepare($update_sql);
                $update_query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $update_query->bindParam(':username', $username, PDO::PARAM_STR);
                
                if($update_query->execute()){
                    $message = '<div class="alert alert-success">Password changed successfully. Please log in again with your new password.</div>';
                    // Destroy the session to force re-login with the new password
                    session_destroy();
                } else {
                    error_log("Database error: " . implode(", ", $update_query->errorInfo()));
                    $message = '<div class="alert alert-danger">Failed to change password. Please try again.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">New passwords do not match.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --primary-color: #3b82f6;
            --secondary-color: #64748b;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
            --muted-color: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #8b4513;
            color: white;
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .logo {
            width: 40px;
            height: 40px;
            position: absolute;
            left: 1rem;
        }

        .content {
            flex-grow: 1;
            padding: 1rem;
        }

        .page-header {
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.75rem;
            color: #8b4513;
        }

        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <header class="header">
            <img src="assets/img/logo.png" alt="Logo" class="logo">
            <h1 class="center">Employee Management System</h1>
        </header>
        
        <?php include_once("includes/sidebar.php");?>

        <main class="content">
            <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="card-title text-center mb-4">Change Password</h2>
                                <?php echo $message; ?>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password').value.trim();
                const newPassword = document.getElementById('new_password').value.trim();
                const confirmPassword = document.getElementById('confirm_password').value.trim();
                
                if (!currentPassword || !newPassword || !confirmPassword) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                } else if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match');
                }
            });
        });
    </script>
</body>
</html>