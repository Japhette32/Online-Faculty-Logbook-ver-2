<?php
session_start();

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
}

$user_id = $_SESSION['user_id'];

include 'db_connection.php';

if (isset($_POST['ajax']) && $_POST['ajax'] === 'load_schedule' && isset($_POST['teacher_id'])) {
    $selected_teacher_id = $_POST['teacher_id'];
    $schedules = [];
    $stmt = $conn->prepare("SELECT day_of_week, TIME_FORMAT(start_time, '%h:%i %p') AS start_time, TIME_FORMAT(end_time, '%h:%i %p') AS end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
    if ($stmt) {
        $stmt->bind_param("i", $selected_teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        $stmt->close();
    }
    echo json_encode($schedules);
    exit();
}

// Retrieve the list of teachers
$teachers = [];
$stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'teacher'");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($teacher_id, $teacher_name);
    while ($stmt->fetch()) {
        $teachers[] = ['id' => $teacher_id, 'name' => $teacher_name];
    }
    $stmt->close();
}

// Handle form submission
if (isset($_POST['ajax']) && $_POST['ajax'] === 'submit_registration') {
    $user_id = $_POST['userId'];
    $name = $_POST['name'];
    $section = $_POST['section'];
    $teacher = $_POST['teacher'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = $_POST['reason'];

    if (empty($user_id) || empty($name) || empty($section) || empty($teacher) || empty($date) || empty($time) || empty($reason)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    } else {
        $stmt = $conn->prepare("INSERT INTO registrations (user_id, name, section, teacher, date, time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issssss", $user_id, $name, $section, $teacher, $date, $time, $reason);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Schedule Updated']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        }
    }
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="select2.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=League+Spartan:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .select2-container .select2-selection--single {
            height: 50px;
            margin-bottom: 25px;
            border-radius: 10px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 50px;
            font-size: 24px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 50px;
        }
        .select2-search__field {
            height: 50px;
            font-size: 20px;
            padding-left: 30px;
            background: url('search.png') no-repeat right center;
            background-size: 45px 45px;
        }
        .input-group select {
            margin-bottom: 15px;
        }
        #registerAgain {
            display: none;
            margin-top: 20px;
        }
        #responseMessage {
            margin-top: 20px;
            font-size: 1.5em;
            color: green;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        .button-container button {
            display: inline-block;
            margin: 0 auto;
        }
        #scheduleList {
            font-size: 1.2em;
            list-style-type: none;
            margin-right: 40px;
        }
        .select2-results__option {
            font-size: 20px;
            padding: 10px;
        }
    </style>
</head>
<body>
<div class="nav">
  <img src="Logo.png" alt="Umak Logo">
  <img src="OSHO-LOGO.webp" alt="OSHO logo">
  <h2>Online Faculty Logbook</h2>
  <div class="line"></div>
  
  <div class="hamburger-menu">
    <span class="bar"></span>
    <span class="bar"></span>
    <span class="bar"></span>
  </div>
  
  <ul>
    <li><a href="account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">Your Schedule</a></li>
    <li><a href="registration.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'registration.php' ? 'active' : ''; ?>">Registration</a></li>
    <li><a href="facultymap.html">Faculty Map</a></li>
    <!-- Add logout link for mobile view -->
    <li class="mobile-logout"><a href="../index.php">Log Out</a></li>
  </ul>
  
  <!-- This will only be visible on larger screens -->
  <a href="../index.php" class="logout-btn">Log Out</a>
</div>
    <div class="container">
        <div id="responseMessage" class="hidden">Schedule Updated</div>
        <h2>Registration Form</h2>
        <form id="registrationForm" method="POST" action="">
            <div class="input-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required placeholder="Surname, Given Name, Middle Initial">

                <label for="section">Section:</label>
                <input type="text" id="section" name="section" required placeholder="G12 - 01 CPG">

                <label for="teacher">Teacher:</label>
                <select id="teacher" name="teacher" data-placeholder="Select a Teacher" required>
                    <option value="" disabled selected>Select a Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="schedule" style="display: none;">
                <h3>Available Schedule</h3>
                <ul id="scheduleList"></ul>
            </div>
            <div class="input-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div class="input-group">
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" required>
            </div>
            <div class="input-group">
                <label for="reason">Reason for consultation:</label>
                <input type="text" id="reason" name="reason" required>
            </div>
            <div class="button-container">
                <button type="submit" id="registerButton">Register</button>
                <button type="button" id="registerAgain" onclick="resetForm()">Register Again</button>
            </div>
            <input type="hidden" id="userId" name="userId" value="<?php echo htmlspecialchars($user_id); ?>">
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#teacher').select2({
                width: '100%',
                placeholder: 'Select a Teacher',
                allowClear: true,
                language: {
                    inputTooShort: function() {
                        return 'Search here';
                    },
                    searching: function() {
                        return 'Searching...';
                    },
                    noResults: function() {
                        return 'No results found';
                    }
                }
            });

            $('#teacher').on('change', function() {
                var teacherId = $(this).val();
                if (teacherId) {
                    $.ajax({
                        type: 'POST',
                        url: 'registration.php',
                        data: {
                            ajax: 'load_schedule',
                            teacher_id: teacherId
                        },
                        success: function(response) {
                            var schedules = JSON.parse(response);
                            var scheduleList = $('#scheduleList');
                            scheduleList.empty();
                            if (schedules.length > 0) {
                                $('#schedule').show();
                                var scheduleMap = {};
                                schedules.forEach(function(schedule) {
                                    if (!scheduleMap[schedule.day_of_week]) {
                                        scheduleMap[schedule.day_of_week] = [];
                                    }
                                    scheduleMap[schedule.day_of_week].push(schedule.start_time + ' - ' + schedule.end_time);
                                });
                                for (var day in scheduleMap) {
                                    scheduleList.append('<li>' + day + ': ' + scheduleMap[day].join(', ') + '</li>');
                                }
                            } else {
                                $('#schedule').hide();
                            }
                        }
                    });
                } else {
                    $('#schedule').hide();
                }
            });

            $('#registrationForm').on('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission
                var formData = $(this).serialize() + '&ajax=submit_registration';
                $.ajax({
                    type: 'POST',
                    url: 'registration.php',
                    data: formData,
                    success: function(response) {
                        var result = JSON.parse(response);
                        if (result.status === 'success') {
                            $('#responseMessage').removeClass('hidden').text(result.message);
                            $('#registerButton').hide();
                            $('#registerAgain').show();
                        } else {
                            alert(result.message);
                        }
                    }
                });
            });
        });

        function resetForm() {
            document.getElementById('registrationForm').reset();
            document.getElementById('responseMessage').classList.add('hidden');
            document.getElementById('registerButton').style.display = 'inline-block';
            document.getElementById('registerAgain').style.display = 'none';
        }
        
    </script>
       <footer class="footer">
    <div class="info">
        <div class="ohsologo-container">
            <h2>Occupational Health and Safety Office</h2>
            <img src="OSHO-LOGO.webp" alt="OHSO Logo" class="ohsologo">
        </div>
        <div class="contact-info">
            <h2>Contact OHSO</h2>
            <ul>
                <li>
                    <img src="gmail.png" alt="Gmail Icon">
                    <span>ohso@umak.edu.ph</span>
                </li>
                <li>
                    <img src="phone-call.png" alt="Phone Icon">
                    <span>288820535</span>
                </li>
                <li>
                    <img src="facebook.png" alt="Facebook Icon">
                    <a href="https://www.facebook.com/profile.php?id=100076383932855"><span>UMak Occupational Health and Safety Office </span></a>
                </li>
            </ul>
        </div>
        <div class="location-info">
            <h2>Ohso Office</h2>
            <ul>
                <li>
                    <img src="map.png" alt="Map Icon">
                    <span>J.P. Rizal Extn. West Rembo, Makati, Philippines, 1215</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
       <!-- <div class="credits">
            <p>Dito lalagay credits.</p>
        </div> -->
        <div class="copyright">
            <p>&copy; <?php echo date("Y"); ?> Online Faculty Logbook. All rights reserved. Icons and code used are copyrighted by their respective owners.</p>
        </div>
    </div>
</footer>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const navMenu = document.querySelector('.nav ul');
    
    hamburger.addEventListener('click', function() {
      hamburger.classList.toggle('active');
      navMenu.classList.toggle('active');
    });
    
    // Close menu when clicking on a nav link
    document.querySelectorAll('.nav ul li a').forEach(link => {
      link.addEventListener('click', function() {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
      });
    });
  });
</script>
                </body>
                </html>