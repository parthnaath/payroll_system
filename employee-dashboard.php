<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/config.php');

// Check if user is logged in
if(strlen($_SESSION['userlogin'])==0) {
    header('location:login.php');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smarthr";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch employee data
function getEmployeeData($conn, $employee_id) {
    $sql = "SELECT e.*, s.amount, s.pay_date, l.days, l.from_date, l.to_date,
                   get_next_holiday() as next_holiday_date,
                   (SELECT title FROM holidays WHERE date = get_next_holiday()) as next_holiday_title,
                   get_team_profile(e.id) as team_id
            FROM employee e
            LEFT JOIN salaries s ON e.id = s.employee_id
            LEFT JOIN leaves l ON e.name = l.employee
            WHERE e.id = ?
            ORDER BY s.pay_date DESC, l.from_date DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'getEmployeeData') {
        $selectedEmployeeId = $_POST['employee_id'];
        $selectedEmployeeData = getEmployeeData($conn, $selectedEmployeeId);
        echo json_encode($selectedEmployeeData);
        exit;
    }
}

// Fetch employee data
$employee_id = $_SESSION['user_id'] ?? 1; // Fallback to ID 1 if not set
$employeeData = getEmployeeData($conn, $employee_id);

// Fetch all employees for the dropdown
$sql = "SELECT id, name FROM employee";
$result = $conn->query($sql);
$allEmployees = $result->fetch_all(MYSQLI_ASSOC);

// Fetch team profile
$team_id = $employeeData['team_id'] ?? null;
if ($team_id) {
    $sql = "SELECT * FROM team_profiles WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teamProfile = $result->fetch_assoc();
} else {
    $teamProfile = null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
        }
        .avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
        }
        .progress {
            height: 10px;
        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <header class="header">
        <img src="assets/img/logo.png" alt="Logo" class="logo">
        <h1>Employee Management System</h1>
    </header>
      
    <!-- Sidebar -->
    <?php include_once("includes/sidebar.php");?>
    <!-- /Sidebar -->

    <div class="container py-5">
        <h1 class="mb-4">Employee Dashboard: <span id="employeeName"><?php echo htmlspecialchars($employeeData['name'] ?? 'N/A'); ?></span></h1>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
            <div class="col">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Employee ID</h5>
                        <i class="fas fa-user text-muted" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="card-text" id="employeeId"><?php echo htmlspecialchars($employeeData['employee_id'] ?? 'N/A'); ?></h2>
                        <p class="card-text text-muted" id="employeeRole"><?php echo htmlspecialchars($employeeData['role'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Latest Salary</h5>
                        <i class="fas fa-dollar-sign text-muted" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="card-text" id="employeeSalary">$<?php echo number_format($employeeData['amount'] ?? 0, 2); ?></h2>
                        <p class="card-text text-muted" id="salaryDate">Pay Date: <?php echo htmlspecialchars($employeeData['pay_date'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Upcoming Leave</h5>
                        <i class="fas fa-calendar-alt text-muted" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="card-text" id="leaveDays"><?php echo $employeeData['days'] ? $employeeData['days'] : '0'; ?> days</h2>
                        <p class="card-text text-muted" id="leaveDates">
                            <?php if ($employeeData['from_date'] && $employeeData['to_date']): ?>
                                From <?php echo htmlspecialchars($employeeData['from_date']); ?> to <?php echo htmlspecialchars($employeeData['to_date']); ?>
                            <?php else: ?>
                                No upcoming leave
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Next Holiday</h5>
                        <i class="fas fa-calendar-day text-muted" aria-hidden="true"></i>
                    </div>
                    <div class="card-body">
                        <h2 class="card-text"><?php echo htmlspecialchars($employeeData['next_holiday_title'] ?? 'N/A'); ?></h2>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($employeeData['next_holiday_date'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Team Profile</h5>
                    </div>
                    <div class="card-body" id="teamProfileContent">
                        <?php if ($teamProfile): ?>
                            <h3 class="h5 mb-3"><?php echo htmlspecialchars($teamProfile['name']); ?></h3>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars($teamProfile['role']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($teamProfile['department']); ?></p>
                            <p><strong>Reports To:</strong> <?php echo htmlspecialchars($teamProfile['reportsTo']); ?></p>
                            <p><strong>Team Members:</strong> <?php echo htmlspecialchars($teamProfile['members']); ?></p>
                            <p><strong>Join Date:</strong> <?php echo htmlspecialchars($teamProfile['joinDate']); ?></p>
                        <?php else: ?>
                            <p>No team profile information available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profile</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo htmlspecialchars($employeeData['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($employeeData['name'] ?? ''); ?>" class="avatar mb-3" id="employeeImage">
                        <h3 class="h5 fw-bold" id="profileName"><?php echo htmlspecialchars($employeeData['name'] ?? 'N/A'); ?></h3>
                        <p class="text-muted mb-1" id="profileRole"><?php echo htmlspecialchars($employeeData['role'] ?? 'N/A'); ?></p>
                        <p class="text-muted" id="profileEmail"><?php echo htmlspecialchars($employeeData['email'] ?? 'N/A'); ?></p>
                        <select class="form-select mb-3" id="employeeSelect" aria-label="Select an employee">
                            <option value="">Select an employee</option>
                            <?php foreach ($allEmployees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary mt-3" id="updateProfileBtn">Update Profile</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#employeeSelect').change(function() {
                var employeeId = $(this).val();
                if (employeeId) {
                    $.ajax({
                        url: 'employee-dashboard.php',
                        type: 'POST',
                        data: {
                            action: 'getEmployeeData',
                            employee_id: employeeId
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                $('#employeeName').text(data.name || 'N/A');
                                $('#employeeId').text(data.employee_id || 'N/A');
                                $('#employeeRole').text(data.role || 'N/A');
                                $('#employeeSalary').text('$' + (parseFloat(data.amount) || 0).toFixed(2));
                                $('#salaryDate').text('Pay Date: ' + (data.pay_date || 'N/A'));
                                $('#leaveDays').text((data.days || '0') + ' days');
                                $('#leaveDates').text(data.from_date && data.to_date ? 'From ' + data.from_date + ' to ' + data.to_date : 'No upcoming leave');
                                $('#employeeImage').attr('src', data.image || '').attr('alt', data.name || '');
                                $('#profileName').text(data.name || 'N/A');
                                $('#profileRole').text(data.role || 'N/A');
                                $('#profileEmail').text(data.email || 'N/A');

                                // Update next holiday information
                                $('.card-body:contains("Next Holiday")').find('h2').text(data.next_holiday_title || 'N/A');
                                $('.card-body:contains("Next Holiday")').find('p').text(data.next_holiday_date || 'N/A');

                                // Update team profile information
                                if (data.team_profile) {
                                    var teamProfileHtml = '<h3 class="h5 mb-3">' + (data.team_profile.name || 'N/A') + '</h3>' +
                                        '<p><strong>Role:</strong> ' + (data.team_profile.role || 'N/A') + '</p>' +
                                        '<p><strong>Department:</strong> ' + (data.team_profile.department || 'N/A') + 
                                        '</p>' +
                                        '<p><strong>Reports To:</strong> ' + (data.team_profile.reportsTo || 'N/A') + '</p>' +
                                        '<p><strong>Team Members:</strong> ' + (data.team_profile.members || 'N/A') + '</p>' +
                                        '<p><strong>Join Date:</strong> ' + (data.team_profile.joinDate || 'N/A') + '</p>';
                                    $('#teamProfileContent').html(teamProfileHtml);
                                } else {
                                    $('#teamProfileContent').html('<p>No team profile information available.</p>');
                                }
                            } else {
                                alert('No data received for the selected employee.');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error:', textStatus, errorThrown);
                            alert('Error fetching employee data. Please try again.');
                        }
                    });
                }
            });

            $('#updateProfileBtn').click(function() {
                var selectedEmployeeId = $('#employeeSelect').val();
                if (selectedEmployeeId) {
                    // Implement your update profile logic here
                    alert('Update profile for employee ID: ' + selectedEmployeeId);
                } else {
                    alert('Please select an employee to update');
                }
            });
        });
    </script>
</body>
</html>