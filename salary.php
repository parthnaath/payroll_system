<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smarthr";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get all employees with their latest salary
function getEmployeesWithSalary() {
    global $conn;
    $sql = "SELECT e.*, s.pay_date, s.amount, s.addition, s.deduction,
                   (s.amount + s.addition - s.deduction) as total_amount
            FROM employee e
            LEFT JOIN (
                SELECT employee_id, pay_date, amount, addition, deduction,
                       ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY pay_date DESC) as rn
                FROM salaries
            ) s ON e.id = s.employee_id AND s.rn = 1
            ORDER BY e.id ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to add new employee and salary
function addEmployeeAndSalary($name, $employee_id, $email, $role, $image, $pay_date, $amount, $addition, $deduction) {
    global $conn;
    $conn->begin_transaction();

    try {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($image["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($image["tmp_name"]);
        if($check === false) {
            throw new Exception("File is not an image.");
        }

        // Check file size
        if ($image["size"] > 500000) {
            throw new Exception("Sorry, your file is too large.");
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        }

        // Try to upload file
        if (!move_uploaded_file($image["tmp_name"], $target_file)) {
            $error = error_get_last();
            throw new Exception("Sorry, there was an error uploading your file: " . ($error['message'] ?? 'Unknown error'));
        }

        // Insert new employee
        $sql = "INSERT INTO employee (name, employee_id, email, role, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $employee_id, $email, $role, $target_file);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting employee: " . $stmt->error);
        }
        $new_employee_id = $conn->insert_id;

        // Insert salary
        $sql = "INSERT INTO salaries (employee_id, pay_date, amount, addition, deduction) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isddd", $new_employee_id, $pay_date, $amount, $addition, $deduction);
        if (!$stmt->execute()) {
            throw new Exception("Error inserting salary: " . $stmt->error);
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Function to update employee and salary
function updateEmployeeAndSalary($id, $name, $employee_id, $email, $role, $image, $pay_date, $amount, $addition, $deduction) {
    global $conn;
    $conn->begin_transaction();

    try {
        // Get the current image path
        $sql = "SELECT image FROM employee WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_image = $result->fetch_assoc()['image'];

        $target_file = $current_image; // Default to current image
        
        // Check if a new image is uploaded
        if(is_array($image) && isset($image['tmp_name']) && !empty($image['tmp_name'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($image["name"]);
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            // Check if image file is a actual image or fake image
            $check = getimagesize($image["tmp_name"]);
            if($check === false) {
                throw new Exception("File is not an image.");
            }

            // Check file size
            if ($image["size"] > 500000) {
                throw new Exception("Sorry, your file is too large.");
            }

            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
                throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
            }

            // if everything is ok, try to upload file
            if (!move_uploaded_file($image["tmp_name"], $target_file)) {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        }

        // Update employee
        $sql = "UPDATE employee SET name=?, employee_id=?, email=?, role=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $employee_id, $email, $role, $target_file, $id);
        $stmt->execute();

        // Update salary
        $sql = "UPDATE salaries SET pay_date=?, amount=?, addition=?, deduction=? WHERE employee_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdddi", $pay_date, $amount, $addition, $deduction, $id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// Function to delete employee and salary
function deleteEmployeeAndSalary($id) {
    global $conn;
    $conn->begin_transaction();

    try {
        // Delete salary records
        $sql = "DELETE FROM salaries WHERE employee_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete employee
        $sql = "DELETE FROM employee WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Function to get employee by ID
function getEmployeeById($id) {
    global $conn;
    $sql = "SELECT e.*, s.pay_date, s.amount, s.addition, s.deduction 
            FROM employee e
            LEFT JOIN salaries s ON e.id = s.employee_id
            WHERE e.id = ?
            ORDER BY s.pay_date DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];

    try {
        switch ($_POST['action']) {
            case 'add':
                if (addEmployeeAndSalary(
                    $_POST['name'],
                    $_POST['employee_id'],
                    $_POST['email'],
                    $_POST['role'],
                    $_FILES['image'],
                    $_POST['pay_date'],
                    $_POST['amount'],
                    $_POST['addition'],
                    $_POST['deduction']
                )) {
                    $response['success'] = true;
                    $response['message'] = 'Employee and salary added successfully';
                }
                break;
            case 'update':
                $image = isset($_FILES['image']) && $_FILES['image']['error'] == 0 ? $_FILES['image'] : $_POST['current_image'];
                if (updateEmployeeAndSalary(
                    $_POST['id'],
                    $_POST['name'],
                    $_POST['employee_id'],
                    $_POST['email'],
                    $_POST['role'],
                    $image,
                    $_POST['pay_date'],
                    $_POST['amount'],
                    $_POST['addition'],
                    $_POST['deduction']
                )) {
                    $response['success'] = true;
                    $response['message'] = 'Employee and salary updated successfully';
                }
                break;
            case 'delete':
                if (deleteEmployeeAndSalary($_POST['id'])) {
                    $response['success'] = true;
                    $response['message'] = 'Employee and salary deleted successfully';
                } else {
                    $response['message'] = 'Failed to delete employee and salary';
                }
                break;
            case 'get':
                $employee = getEmployeeById($_POST['id']);
                if ($employee) {
                    $response['success'] = true;
                    $response['data'] = $employee;
                } else {
                    $response['message'] = 'Failed to fetch employee data';
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log($e->getMessage()); // Log the error server-side
    }

    echo json_encode($response);
    exit;
}

$employees = getEmployeesWithSalary();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Employee Salary - Employee Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
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
        .page-title {
            font-size: 1.75rem;
            color: #8b4513;
            margin-bottom: 1rem;
        }
        .add-salary-btn {
            background-color: #ff9b44;
            border-color: #ff9b44;
        }
        .table-responsive {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 1px rgba(0,0,0,.05);
        }
        .employee-info {
            display: flex;
            align-items: center;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        .employee-name {
            font-weight: bold;
            margin-bottom: 0;
        }
        .employee-role {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .action-column {
            width: 150px;
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
             
    <div class="content">
        <div class="container">
            <div class="row mb-3">
                <div class="col">
                    <h2 class="page-title">Employee Salary</h2>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary add-salary-btn" data-toggle="modal" data-target="#addSalaryModal">+ Add Salary</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped" id="salaryTable">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Email</th>
                            <th>Pay Date</th>
                            <th>Base Amount</th>
                            <th>Total Amount</th>
                            <th class="action-column">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div class="employee-info">
                                    <img src="<?php echo htmlspecialchars($employee['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>" class="avatar">
                                    <div>
                                        <p class="employee-name"><?php echo htmlspecialchars($employee['name'] ?? ''); ?></p>
                                        <p class="employee-role"><?php echo htmlspecialchars($employee['role'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($employee['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($employee['pay_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($employee['amount'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($employee['total_amount'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-salary" data-id="<?php echo $employee['id']; ?>">Edit</button>
                                <button class="btn btn-sm btn-danger delete-salary" data-id="<?php echo $employee['id']; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Salary Modal -->
    <div class="modal fade" id="addSalaryModal" tabindex="-1" role="dialog" aria-labelledby="addSalaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSalaryModalLabel">Add Salary</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addSalaryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Employee Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="employee_id">Employee ID</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="role">Role</label>
                                <input type="text" class="form-control" id="role" name="role" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="image">Employee Image</label>
                            <input type="file" class="form-control-file" id="image" name="image" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="pay_date">Pay Date</label>
                                <input type="date" class="form-control" id="pay_date" name="pay_date" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="amount">Base Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="addition">Addition</label>
                                <input type="number" class="form-control" id="addition" name="addition" value="0" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="deduction">Deduction</label>
                                <input type="number" class="form-control" id="deduction" name="deduction" value="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Salary Modal -->
    <div class="modal fade" id="editSalaryModal" tabindex="-1" role="dialog" aria-labelledby="editSalaryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSalaryModalLabel">Edit Salary</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editSalaryForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_name">Employee Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_employee_id">Employee ID</label>
                                <input type="text" class="form-control" id="edit_employee_id" name="employee_id" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_email">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_role">Role</label>
                                <input type="text" class="form-control" id="edit_role" name="role" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_image">Employee Image</label>
                            <input type="file" class="form-control-file" id="edit_image" name="image">
                            <input type="hidden" id="current_image" name="current_image">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_pay_date">Pay Date</label>
                                <input type="date" class="form-control" id="edit_pay_date" name="pay_date" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_amount">Base Amount</label>
                                <input type="number" class="form-control" id="edit_amount" name="amount" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_addition">Addition</label>
                                <input type="number" class="form-control" id="edit_addition" name="addition" value="0" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_deduction">Deduction</label>
                                <input type="number" class="form-control" id="edit_deduction" name="deduction" value="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_total_amount">Total Amount</label>
                            <input type="number" class="form-control" id="edit_total_amount" name="total_amount" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    function calculateTotalAmount(baseAmount, addition, deduction) {
        return parseFloat(baseAmount) + parseFloat(addition) - parseFloat(deduction);
    }

    function updateTotalAmount(formId) {
        var baseAmount = $(`#${formId} [name="amount"]`).val() || 0;
        var addition = $(`#${formId} [name="addition"]`).val() || 0;
        var deduction = $(`#${formId} [name="deduction"]`).val() || 0;
        var totalAmount = calculateTotalAmount(baseAmount, addition, deduction);
        $(`#${formId} [name="total_amount"]`).val(totalAmount.toFixed(2));
    }

    // Add Salary
    $('#addSalaryForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'add');
        $.ajax({
            url: 'salary.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert("An error occurred. Please check the console for more details.");
            }
        });
    });

    // Edit Salary
    $(document).on('click', '.edit-salary', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'salary.php',
            type: 'POST',
            data: {action: 'get', id: id},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var employee = response.data;
                    $('#edit_id').val(employee.id);
                    $('#edit_name').val(employee.name);
                    $('#edit_employee_id').val(employee.employee_id);
                    $('#edit_email').val(employee.email);
                    $('#edit_role').val(employee.role);
                    $('#current_image').val(employee.image);
                    $('#edit_pay_date').val(employee.pay_date);
                    $('#edit_amount').val(employee.amount);
                    $('#edit_addition').val(employee.addition);
                    $('#edit_deduction').val(employee.deduction);
                    updateTotalAmount('editSalaryForm');
                    $('#editSalaryModal').modal('show');
                } else {
                    alert('Failed to fetch employee data');
                }
            }
        });
    });

    // Update Salary
    $('#editSalaryForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'update');
        
        // Only append the image if a new file is selected
        var imageInput = $('#edit_image')[0];
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        } else {
            formData.append('image', ''); // Send an empty string if no new image
        }

        $.ajax({
            url: 'salary.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert("An error occurred. Please try again.");
            }
        });
    });

    // Delete Salary
    $(document).on('click', '.delete-salary', function() {
        var id = $(this).data('id');
        if (confirm('Are you sure you want to delete this employee and salary record?')) {
            $.ajax({
                url: 'salary.php',
                type: 'POST',
                data: {id: id, action: 'delete'},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("An error occurred. Please try again.");
                }
            });
        }
    });

    // Calculate total amount on input change
    $('#addSalaryForm input[name="amount"], #addSalaryForm input[name="addition"], #addSalaryForm input[name="deduction"]').on('input', function() {
        updateTotalAmount('addSalaryForm');
    });

    $('#editSalaryForm input[name="amount"], #editSalaryForm input[name="addition"], #editSalaryForm input[name="deduction"]').on('input', function() {
        updateTotalAmount('editSalaryForm');
    });

    // Initialize total amount calculation
    updateTotalAmount('addSalaryForm');
    updateTotalAmount('editSalaryForm');

    // Function to ensure proper alignment of employee info
    function adjustEmployeeInfoHeight() {
        $('.employee-info').each(function() {
            var maxHeight = 0;
            $(this).find('p').each(function() {
                maxHeight = Math.max(maxHeight, $(this).outerHeight());
            });
            $(this).height(maxHeight);
        });
    }

    // Call the function on page load and window resize
    adjustEmployeeInfoHeight();
    $(window).resize(adjustEmployeeInfoHeight);
});
</script>
</body>
</html>