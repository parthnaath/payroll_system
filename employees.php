<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smarthr";

try {
    $dbh = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if Image column exists, if not, add it
    $stmt = $dbh->query("SHOW COLUMNS FROM `add-employees` LIKE 'Image'");
    if ($stmt->rowCount() == 0) {
        $dbh->exec("ALTER TABLE `add-employees` ADD COLUMN Image VARCHAR(255)");
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

if(strlen($_SESSION['userlogin'])==0){
    header('location:login.php');
}

// Fetch employees from the database
$sql = "SELECT * FROM `add-employees`";
$query = $dbh->prepare($sql);
$query->execute();
$employees = $query->fetchAll(PDO::FETCH_OBJ);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $email = $_POST['email'];
                $designation = $_POST['designation'];
                
                // Handle file upload
                $fileName = '';
                if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
                    $targetDir = "uploads/";
                    $fileName = basename($_FILES["image"]["name"]);
                    $targetFilePath = $targetDir . $fileName;
                    $fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);
                    
                    // Allow certain file formats
                    $allowTypes = array('jpg','png','jpeg','gif');
                    if(in_array($fileType, $allowTypes)){
                        // Upload file to server
                        if(move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)){
                            // File uploaded successfully
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Sorry, there was an error uploading your file.']);
                            exit;
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Sorry, only JPG, JPEG, PNG, & GIF files are allowed to upload.']);
                        exit;
                    }
                }
                
                // Insert employee data into database
                $sql = "INSERT INTO `add-employees` (FirstName, LastName, Email, Designation, Image) VALUES (:firstName, :lastName, :email, :designation, :image)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':firstName', $firstName, PDO::PARAM_STR);
                $query->bindParam(':lastName', $lastName, PDO::PARAM_STR);
                $query->bindParam(':email', $email, PDO::PARAM_STR);
                $query->bindParam(':designation', $designation, PDO::PARAM_STR);
                $query->bindParam(':image', $fileName, PDO::PARAM_STR);
                if ($query->execute()) {
                    $lastInsertId = $dbh->lastInsertId();
                    $newEmployee = $dbh->query("SELECT * FROM `add-employees` WHERE id = $lastInsertId")->fetch(PDO::FETCH_OBJ);
                    echo json_encode(['success' => true, 'message' => 'Employee added successfully', 'employee' => $newEmployee]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error adding employee']);
                }
                exit;
            case 'edit':
                $id = $_POST['id'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $email = $_POST['email'];
                $designation = $_POST['designation'];
                $sql = "UPDATE `add-employees` SET FirstName=:firstName, LastName=:lastName, Email=:email, Designation=:designation WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->bindParam(':firstName', $firstName, PDO::PARAM_STR);
                $query->bindParam(':lastName', $lastName, PDO::PARAM_STR);
                $query->bindParam(':email', $email, PDO::PARAM_STR);
                $query->bindParam(':designation', $designation, PDO::PARAM_STR);
                if ($query->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating employee']);
                }
                exit;
            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM `add-employees` WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                if ($query->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting employee']);
                }
                exit;
            case 'get':
                $id = $_POST['id'];
                $sql = "SELECT * FROM `add-employees` WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();
                $employee = $query->fetch(PDO::FETCH_OBJ);
                if ($employee) {
                    echo json_encode(['success' => true, 'employee' => $employee]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Employee not found']);
                }
                exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Employee Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        .profile-widget {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.2);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .profile-img {
            margin-bottom: 15px;
        }
        .profile-img img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-action {
            position: absolute;
            right: 5px;
            top: 10px;
        }
        .user-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .text-muted {
            color: #777;
        }
        .add-btn {
            background-color: #28a745;
            color: white;
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
             
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Employees</h3>
                </div>
                <div class="col-auto float-right ml-auto">
                    <a href="#" class="btn add-btn" data-toggle="modal" data-target="#add_employee"><i class="fa fa-plus"></i> Add Employee</a>
                </div>
            </div>
        </div>

        <div class="row" id="employeeList">
            <?php foreach($employees as $employee): ?>
            <div class="col-md-4 col-sm-6 col-12 col-lg-4 col-xl-3 employee-item">
                <div class="profile-widget">
                    <div class="profile-img">
                        <a href="profile.php?empid=<?php echo htmlentities($employee->id);?>" class="avatar">
                            <img src="<?php echo !empty($employee->Image) ? 'uploads/'.$employee->Image : 'https://via.placeholder.com/150'; ?>" alt="Employee Image">
                        </a>
                    </div>
                    <div class="dropdown profile-action">
                        <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#edit_employee" data-id="<?php echo $employee->id; ?>"><i class="fa fa-pencil m-r-5"></i> Edit</a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#delete_employee" data-id="<?php echo $employee->id; ?>"><i class="fa fa-trash-o m-r-5"></i> Delete</a>
                        </div>
                    </div>
                    <h4 class="user-name m-t-10 mb-0 text-ellipsis">
                        <a href="profile.php?empid=<?php echo htmlentities($employee->id);?>"><?php echo htmlentities($employee->FirstName." ".$employee->LastName);?></a>
                    </h4>
                    <div class="small text-muted"><?php echo htmlentities($employee->Designation);?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div id="add_employee" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Employee</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add_employee_form" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="firstName" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input class="form-control" type="text" name="lastName">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input class="form-control" type="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Designation <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="designation" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Image</label>
                                <input class="form-control" type="file" name="image" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="submit-section">
                        <button class="btn btn-primary submit-btn" type="submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="edit_employee" class="modal custom-modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit_employee_form">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input  class="form-control" type="text" name="firstName" id="edit_firstName" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input class="form-control" type="text" name="lastName" id="edit_lastName">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input class="form-control" type="email" name="email" id="edit_email" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Designation <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="designation" id="edit_designation" required>
                            </div>
                        </div>
                    </div>
                    <div class="submit-section">
                        <button class="btn btn-primary submit-btn" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Employee Modal -->
<div class="modal custom-modal fade" id="delete_employee" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="form-header">
                    <h3>Delete Employee</h3>
                    <p>Are you sure want to delete?</p>
                </div>
                <div class="modal-btn delete-action">
                    <div class="row">
                        <div class="col-6">
                            <a href="javascript:void(0);" class="btn btn-primary continue-btn">Delete</a>
                        </div>
                        <div class="col-6">
                            <a href="javascript:void(0);" data-dismiss="modal" class="btn btn-primary cancel-btn">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Bootstrap dropdowns
        $('[data-toggle="dropdown"]').dropdown();

        // Add Employee
        $("#add_employee_form").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('action', 'add');
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        var newEmployee = response.employee;
                        var employeeHtml = `
                            <div class="col-md-4 col-sm-6 col-12 col-lg-4 col-xl-3 employee-item">
                                <div class="profile-widget">
                                    <div class="profile-img">
                                        <a href="profile.php?empid=${newEmployee.id}" class="avatar">
                                            <img src="${newEmployee.Image ? 'uploads/' + newEmployee.Image : 'https://via.placeholder.com/150'}" alt="Employee Image">
                                        </a>
                                    </div>
                                    <div class="dropdown profile-action">
                                        <a href="#" class="action-icon dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-v"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#edit_employee" data-id="${newEmployee.id}"><i class="fa fa-pencil m-r-5"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#delete_employee" data-id="${newEmployee.id}"><i class="fa fa-trash-o m-r-5"></i> Delete</a>
                                        </div>
                                    </div>
                                    <h4 class="user-name m-t-10 mb-0 text-ellipsis">
                                        <a href="profile.php?empid=${newEmployee.id}">${newEmployee.FirstName} ${newEmployee.LastName}</a>
                                    </h4>
                                    <div class="small text-muted">${newEmployee.Designation}</div>
                                </div>
                            </div>
                        `;
                        $("#employeeList").append(employeeHtml);
                        $("#add_employee").modal('hide');
                        $("#add_employee_form")[0].reset();
                        // Reinitialize dropdowns for new elements
                        $('[data-toggle="dropdown"]').dropdown();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("An error occurred while adding the employee. Please try again.");
                }
            });
        });

        // Edit Employee
        $("#edit_employee").on('show.bs.modal', function(e) {
            var id = $(e.relatedTarget).data('id');
            $.ajax({
                url: '',
                type: 'POST',
                data: {action: 'get', id: id},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $("#edit_id").val(response.employee.id);
                        $("#edit_firstName").val(response.employee.FirstName);
                        $("#edit_lastName").val(response.employee.LastName);
                        $("#edit_email").val(response.employee.Email);
                        $("#edit_designation").val(response.employee.Designation);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert("An error occurred while fetching employee data. Please try again.");
                }
            });
        });

        $("#edit_employee_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '',
                type: 'POST',
                data: $(this).serialize() + '&action=edit',
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
                    alert("An error occurred while updating the employee. Please try again.");
                }
            });
        });

        // Delete Employee
        var deleteId;
        $("#delete_employee").on('show.bs.modal', function(e) {
            deleteId = $(e.relatedTarget).data('id');
        });

        $(".continue-btn").on('click', function(e) {
            e.preventDefault();
            $.ajax({
                url: '',
                type: 'POST',
                data: {action: 'delete', id: deleteId},
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
                    alert("An error occurred while deleting the employee. Please try again.");
                }
            });
        });
    });
</script>
</body>
</html>