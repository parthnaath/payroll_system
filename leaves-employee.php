<?php
session_start();
error_reporting(0);
include_once('includes/config.php');
include_once("includes/functions.php");

if(strlen($_SESSION['userlogin'])==0){
    header('location:login.php');
} elseif (isset($_GET['delid'])) {
    $rid=intval($_GET['delid']);
    $sql="DELETE from leaves where id=:rid";
    $query=$dbh->prepare($sql);
    $query->bindParam(':rid',$rid,PDO::PARAM_STR);
    $query->execute();
    echo "<script>alert('Leave record has been deleted');</script>"; 
    echo "<script>window.location.href ='leaves.php'</script>";
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $employee = $_POST['employee'];
                $from = $_POST['from'];
                $to = $_POST['to'];
                $days = $_POST['days'];
                $reason = $_POST['reason'];
                $sql = "INSERT INTO leaves (employee, from_date, to_date, days, reason) VALUES (:employee, :from, :to, :days, :reason)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':employee', $employee, PDO::PARAM_STR);
                $query->bindParam(':from', $from, PDO::PARAM_STR);
                $query->bindParam(':to', $to, PDO::PARAM_STR);
                $query->bindParam(':days', $days, PDO::PARAM_INT);
                $query->bindParam(':reason', $reason, PDO::PARAM_STR);
                $query->execute();
                echo json_encode(['success' => true, 'message' => 'Leave added successfully']);
                exit;
            case 'edit':
                $id = $_POST['id'];
                $employee = $_POST['employee'];
                $from = $_POST['from'];
                $to = $_POST['to'];
                $days = $_POST['days'];
                $reason = $_POST['reason'];
                $sql = "UPDATE leaves SET employee=:employee, from_date=:from, to_date=:to, days=:days, reason=:reason WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->bindParam(':employee', $employee, PDO::PARAM_STR);
                $query->bindParam(':from', $from, PDO::PARAM_STR);
                $query->bindParam(':to', $to, PDO::PARAM_STR);
                $query->bindParam(':days', $days, PDO::PARAM_INT);
                $query->bindParam(':reason', $reason, PDO::PARAM_STR);
                $query->execute();
                echo json_encode(['success' => true, 'message' => 'Leave updated successfully']);
                exit;
            case 'get':
                $id = $_POST['id'];
                $sql = "SELECT * FROM leaves WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();
                $leave = $query->fetch(PDO::FETCH_OBJ);
                if ($leave) {
                    echo json_encode(['success' => true, 'leave' => $leave]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Leave not found']);
                }
                exit;
        }
    }
}

// Fetch leaves from the database
$sql = "SELECT * FROM leaves ORDER BY from_date ASC";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Leaves - Employee Management System</title>
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
        .add-btn {
            background-color: #28a745;
            color: white;
        }
        .table-responsive {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .action-btn {
            margin-right: 5px;
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
             
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-title">
                        <h2>Leaves</h2>
                    </div>
                    <div class="mb-3">
                        <button class="btn add-btn" data-toggle="modal" data-target="#add_leave"><i class="fa fa-plus"></i> Add Leave</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>No of Days</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($query->rowCount() > 0) {
                                    foreach($results as $row) {
                                ?>
                                <tr>
                                    <td><?php echo htmlentities($row->employee);?></td>
                                    <td><?php echo htmlentities($row->from_date);?></td>
                                    <td><?php echo htmlentities($row->to_date);?></td>
                                    <td><?php echo htmlentities($row->days);?></td>
                                    <td><?php echo htmlentities($row->reason);?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm action-btn edit-leave" data-id="<?php echo $row->id; ?>">
                                            <i class="fa fa-pencil-alt"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm action-btn delete-leave" data-id="<?php echo $row->id; ?>">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Leave Modal -->
    <div class="modal custom-modal fade" id="add_leave" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Leave</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="add_leave_form">
                        <div class="form-group">
                            <label>Employee <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employee" required>
                        </div>
                        <div class="form-group">
                            <label>From <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="from" required>
                        </div>
                        <div class="form-group">
                            <label>To <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="to" required>
                        </div>
                        <div class="form-group">
                            <label>Number of Days <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" name="days" required>
                        </div>
                        <div class="form-group">
                            <label>Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" required></textarea>
                        </div>
                        <div class="submit-section">
                            <button class="btn btn-primary submit-btn" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Leave Modal -->
    <div class="modal custom-modal fade" id="edit_leave" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Leave</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="edit_leave_form">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label>Employee <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employee" id="edit_employee" required>
                        </div>
                        <div class="form-group">
                            <label>From <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="from" id="edit_from" required>
                        </div>
                        <div class="form-group">
                            <label>To <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="to" id="edit_to" required>
                        </div>
                        <div class="form-group">
                            <label>Number of Days <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" name="days" id="edit_days" required>
                        </div>
                        <div class="form-group">
                            <label>Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" id="edit_reason" required></textarea>
                        </div>
                        <div class="submit-section">
                            <button class="btn btn-primary submit-btn" type="submit">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Add Leave
        $("#add_leave_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $(this).serialize() + '&action=add',
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'An error occurred while adding the leave.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        // Edit Leave
        $(".edit-leave").on('click', function() {
            var id = $(this).data('id');
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {action: 'get', id: id},
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success && response.leave) {
                    $("#edit_id").val(response.leave.id);
                    $("#edit_employee").val(response.leave.employee);
                    $("#edit_from").val(response.leave.from_date);
                    $("#edit_to").val(response.leave.to_date);
                    $("#edit_days").val(response.leave.days);
                    $("#edit_reason").val(response.leave.reason);
                    $("#edit_leave").modal('show');
                } else {
                    alert(response.message || 'An error occurred while fetching the leave data.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        $("#edit_leave_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $(this).serialize() + '&action=edit',
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message || 'An error occurred while updating the leave.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        // Delete Leave
        $(".delete-leave").on('click', function() {
            if (confirm('Are you sure you want to delete this leave?')) {
                var id = $(this).data('id');
                window.location.href = 'leaves.php?delid=' + id;
            }
        });
    });
    </script>
</body>
</html>