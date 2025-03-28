<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EMS admin template</title>
    <style>
        .main-header {
            background-color: #0000FF; /* Existing blue header color */
            padding: 10px 0;
            text-align: center;
            color: #fff;
            font-size: 24px;
            font-weight: bold;
        }
        .header-bar {
            display: flex;
            justify-content: space-around;
            align-items: center;
            background-color: #f8f9fa; /* Change as needed */
            padding: 10px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        .header-bar ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .header-bar li {
            padding: 10px 20px;
            position: relative;
        }
        .header-bar a {
            text-decoration: none;
            color: #000; /* Change as needed */
            font-weight: bold;
        }
        .header-bar .submenu ul {
            display: none;
            position: absolute;
            background-color: #f8f9fa;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            top: 100%;
            left: 0;
            width: 200px;
            z-index: 1000;
        }
        .header-bar .submenu:hover ul,
        .header-bar .submenu ul:hover {
            display: block;
        }
        .header-bar .submenu ul li {
            padding: 10px;
            white-space: nowrap;
        }
        .main-content {
            width: 80%; /* Adjust as needed */
            max-width: 1200px; /* Adjust as needed */ /* Adjust as needed for spacing */
        }
        
    </style>
</head>
<body>

    <header class="header-bar">
        <ul>
		  <li class="submenu">
            <a href="#">Dashboard</a>
			    <ul>
                    <li><a href="index.php">Admin Dashboard</a></li>
                    <li><a href="employee-dashboard.php">Employee Dashboard</a></li>
                </ul>
	      </li>
		  <li class="submenu">
            <a href="#">Employees</a>
			    <ul>
				<li><a href="employees.php">All Employees</a></li>
				<li><a href="holidays.php">Holidays</a></li>
				<li><a href="leaves-employee.php">Employee Leave</a></li>
                </ul>
	      </li>
            
            <li class="submenu">
                <a href="#">Payroll</a>
                <ul>
                    <li><a href="salary.php">Employee Salary</a></li>
                </ul>
            </li>
            <li><a href="security.php">Security</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </header>
</body>
</html>
