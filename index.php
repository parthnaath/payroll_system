<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['userlogin'])==0){
    header('location:login.php');
}

// Fetch user data
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Fetch dashboard data
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Total employees
$result = mysqli_query($db, "SELECT COUNT(*) as total FROM employee");
$total_employees = mysqli_fetch_assoc($result)['total'];

// Upcoming holidays
$result = mysqli_query($db, "SELECT COUNT(*) as total FROM holidays WHERE date > CURDATE() LIMIT 3");
$upcoming_holidays = mysqli_fetch_assoc($result)['total'];

// Pending leaves
$result = mysqli_query($db, "SELECT COUNT(*) as total FROM leaves WHERE from_date > CURDATE()");
$pending_leaves = mysqli_fetch_assoc($result)['total'];

// Total salary payout
$result = mysqli_query($db, "SELECT SUM(amount) as total FROM salaries WHERE MONTH(pay_date) = MONTH(CURDATE()) AND YEAR(pay_date) = YEAR(CURDATE())");
$total_salary_payout = mysqli_fetch_assoc($result)['total'];

// Recent activities
$result = mysqli_query($db, "SELECT action, icon, time_ago FROM activities ORDER BY created_at DESC LIMIT 4");
$recent_activities = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMS Dashboard</title>
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f7;
            color: #333;
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

        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
        }

        .nav-link {
            color: var(--secondary-color);
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }

        .card-title {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .display-4 {
            font-weight: 600;
        }

        .text-muted {
            color: var(--muted-color) !important;
        }

        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
        }

        .btn-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <header class="header">
            <img src="assets/img/logo.png" alt="Logo" class="logo">
            <h1 class="center">Employee Management System</h1>
        </header>
        
        <!-- Sidebar -->
        <?php include_once("includes/sidebar.php");?>
        <!-- /Sidebar -->

        <main class="content">
            <div class="page-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <main class="container-fluid py-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Employees</h5>
                                <p class="card-text display-4"><?php echo $total_employees; ?></p>
                                <p class="text-muted">+12% from last month</p>
                                <a href="employees.php" class="btn btn-link p-0">View All Employees</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Upcoming Holidays</h5>
                                <p class="card-text display-4"><?php echo $upcoming_holidays; ?></p>
                                <p class="text-muted">Next: Summer Break</p>
                                <a href="holidays.php" class="btn btn-link p-0">View Holiday Calendar</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Pending Leaves</h5>
                                <p class="card-text display-4"><?php echo $pending_leaves; ?></p>
                                <p class="text-muted">5 new requests</p>
                                <a href="leaves-employee.php" class="btn btn-link p-0">Manage Leave Requests</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Salary Payout</h5>
                                <p class="card-text display-4">$<?php echo number_format($total_salary_payout); ?></p>
                                <p class="text-muted">For current month</p>
                                <a href="salary.php" class="btn btn-link p-0">View Salary Details</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Activities</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_activities as $activity): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-<?php echo htmlspecialchars($activity['icon']); ?> me-2"></i>
                                        <?php echo htmlspecialchars($activity['action']); ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($activity['time_ago']); ?></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </main>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
            const sidebar = document.querySelector('.sidebar');

            function toggleSidebar() {
                sidebar.classList.toggle('show');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            if (sidebarToggleMobile) {
                sidebarToggleMobile.addEventListener('click', toggleSidebar);
            }

            // Employee Growth Chart
            const employeeGrowthCtx = document.getElementById('employeeGrowthChart').getContext('2d');
            new Chart(employeeGrowthCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($employee_growth, 'month')); ?>,
                    datasets: [{
                        label: 'Employee Count',
                        data: <?php echo json_encode(array_column($employee_growth, 'count')); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Department Distribution Chart
            const departmentDistributionCtx = document.getElementById('departmentDistributionChart').getContext('2d');
            new Chart(departmentDistributionCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($department_distribution, 'name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($department_distribution, 'value')); ?>,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>