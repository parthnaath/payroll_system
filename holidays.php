<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Set the content type to JSON for all AJAX responses
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smarthr";

try {
    $dbh = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the 'date' column exists, if not, add it
    $sql = "SHOW COLUMNS FROM holidays LIKE 'date'";
    $result = $dbh->query($sql);
    if ($result->rowCount() == 0) {
        $sql = "ALTER TABLE holidays ADD COLUMN date DATE";
        $dbh->exec($sql);
    }

} catch(PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'error.log');
}

if(strlen($_SESSION['userlogin'])==0){
    header('location:login.php');
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $date = $_POST['date'];
                $sql = "INSERT INTO holidays (title, date) VALUES (:title, :date)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':title', $title, PDO::PARAM_STR);
                $query->bindParam(':date', $date, PDO::PARAM_STR);
                if ($query->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Holiday added successfully']);
                } else {
                    $errorInfo = $query->errorInfo();
                    logError("Error adding holiday: " . implode(", ", $errorInfo));
                    echo json_encode(['success' => false, 'message' => 'Error adding holiday: ' . $errorInfo[2]]);
                }
                exit;
            case 'edit':
                $id = $_POST['id'];
                $title = $_POST['title'];
                $date = $_POST['date'];
                $sql = "UPDATE holidays SET title=:title, date=:date WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->bindParam(':title', $title, PDO::PARAM_STR);
                $query->bindParam(':date', $date, PDO::PARAM_STR);
                if ($query->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Holiday updated successfully']);
                } else {
                    $errorInfo = $query->errorInfo();
                    logError("Error updating holiday: " . implode(", ", $errorInfo));
                    echo json_encode(['success' => false, 'message' => 'Error updating holiday: ' . $errorInfo[2]]);
                }
                exit;
            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM holidays WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                if ($query->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Holiday deleted successfully']);
                } else {
                    $errorInfo = $query->errorInfo();
                    logError("Error deleting holiday: " . implode(", ", $errorInfo));
                    echo json_encode(['success' => false, 'message' => 'Error deleting holiday: ' . $errorInfo[2]]);
                }
                exit;
            case 'get':
                $id = $_POST['id'];
                $sql = "SELECT * FROM holidays WHERE id=:id";
                $query = $dbh->prepare($sql);
                $query->bindParam(':id', $id, PDO::PARAM_INT);
                $query->execute();
                $holiday = $query->fetch(PDO::FETCH_OBJ);
                if ($holiday) {
                    echo json_encode(['success' => true, 'holiday' => $holiday]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Holiday not found']);
                }
                exit;
        }
    }
}

// Fetch holidays from the database
try {
    $sql = "SELECT * FROM holidays ORDER BY date";
    $query = $dbh->prepare($sql);
    $query->execute();
    $holidays = $query->fetchAll(PDO::FETCH_OBJ);
} catch(PDOException $e) {
    logError("Error fetching holidays: " . $e->getMessage());
    $holidays = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Holidays - Employee Management System</title>
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
                    <h2>All Holidays</h2>
                </div>
                <div class="mb-3">
                    <button class="btn add-btn" data-toggle="modal" data-target="#add_holiday"><i class="fa fa-plus"></i> Add Holiday</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Holiday Date</th>
                                <th>Day</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cnt = 1;
                            foreach($holidays as $holiday) {
                                $date = new DateTime($holiday->date);
                            ?>
                            <tr>
                                <td><?php echo $cnt; ?></td>
                                <td><?php echo htmlentities($holiday->title); ?></td>
                                <td><?php echo $date->format('Y-m-d'); ?></td>
                                <td><?php echo $date->format('l'); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm action-btn edit-holiday" data-id="<?php echo $holiday->id; ?>">
                                        <i class="fa fa-pencil-alt"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm action-btn delete-holiday" data-id="<?php echo $holiday->id; ?>">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php
                                $cnt++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal custom-modal fade" id="add_holiday" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Holiday</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add_holiday_form">
                    <div class="form-group">
                        <label>Holiday Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Holiday Date <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" name="date" required>
                    </div>
                    <div class="submit-section">
                        <button class="btn btn-primary submit-btn" type="submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal custom-modal fade" id="edit_holiday" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Holiday</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit_holiday_form">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label>Holiday Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-group">
                        <label>Holiday Date <span class="text-danger">*</span></label>
                        <input class="form-control" type="date" name="date" id="edit_date" required>
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
        // Add Holiday
        $("#add_holiday_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $(this).serialize() + '&action=add',
                dataType: 'json'
            })
            .done(function(response) {
                if (response && response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response && response.message ? response.message : 'An error occurred while adding the holiday.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        // Edit Holiday
        $(".edit-holiday").on('click', function() {
            var id = $(this).data('id');
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {action: 'get', id: id},
                dataType: 'json'
            })
            .done(function(response) {
                if (response && response.success && response.holiday) {
                    $("#edit_id").val(response.holiday.id);
                    $("#edit_title").val(response.holiday.title);
                    $("#edit_date").val(response.holiday.date);
                    $("#edit_holiday").modal('show');
                } else {
                    alert(response && response.message ? response.message : 'An error occurred while fetching the holiday data.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        $("#edit_holiday_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $(this).serialize() + '&action=edit',
                dataType: 'json'
            })
            .done(function(response) {
                if (response && response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response && response.message ? response.message : 'An error occurred while updating the holiday.');
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown)

 {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('An error occurred. Please check the console and try again.');
            });
        });

        // Delete Holiday
        $(".delete-holiday").on('click', function() {
            if (confirm('Are you sure you want to delete this holiday?')) {
                var id = $(this).data('id');
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {action: 'delete', id: id},
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response && response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response && response.message ? response.message : 'An error occurred while deleting the holiday.');
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                    alert('An error occurred. Please check the console and try again.');
                });
            }
        });
    });
</script>
</body>
</html>