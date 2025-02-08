
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="select2.min.css">
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
    <?php
    session_start();

    // Check if user is logged in via session or cookie
    if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
        header("Location: ../index.php");
        exit();
    }

    // Set session from cookie if session is not set
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
    }

    $user_id = $_SESSION['user_id'];

    include 'db_connection.php';

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

    // Retrieve the selected teacher's schedule if a teacher is selected
    $selected_teacher_id = isset($_POST['teacher']) ? $_POST['teacher'] : null;
    $schedules = [];
    if ($selected_teacher_id) {
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
    }
    ?>
    <div class="nav">
        <img src="Logo.png" alt="Umak Logo">
        <img src="OSHO-LOGO.webp" alt="OSHO logo">
        <h2>Online Faculty Logbook</h2>
        <div class="line"></div>
        <ul>
            <li><a href="account.php">Your Schedule</a></li>
            <li><a href="registration.php" class="active">Registration</a></li>
            <li><a href="facultymap.html">Faculty Map</a></li>
        </ul>
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
                <select id="teacher" name="teacher" onchange="this.form.submit()" data-placeholder="Select a Teacher" required>
                    <option value="" disabled selected>Select a Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>" <?php echo ($selected_teacher_id == $teacher['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($teacher['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selected_teacher_id): ?>
            <div id="schedule">
                <h3>Available Schedule</h3>
                <ul id="scheduleList">
                    <?php foreach ($schedules as $schedule): ?>
                        <li><?php echo htmlspecialchars($schedule['day_of_week']) . ', ' . htmlspecialchars($schedule['start_time']) . ' - ' . htmlspecialchars($schedule['end_time']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
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
        });

        function resetForm() {
            document.getElementById('registrationForm').reset();
            document.getElementById('responseMessage').classList.add('hidden');
            document.getElementById('registerButton').style.display = 'inline-block';
            document.getElementById('registerAgain').style.display = 'none';
        }
    </script>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_POST['userId'];
        $name = $_POST['name'];
        $section = $_POST['section'];
        $teacher = $_POST['teacher'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $reason = $_POST['reason'];

        if (empty($user_id) || empty($name) || empty($section) || empty($teacher) || empty($date) || empty($time) || empty($reason)) {
            echo "<script>alert('Please fill in all fields.');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO registrations (user_id, name, section, teacher, date, time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssss", $user_id, $name, $section, $teacher, $date, $time, $reason);
                if ($stmt->execute()) {
                    echo "<script>document.getElementById('responseMessage').classList.remove('hidden');</script>";
                } else {
                    echo "<script>alert('Error: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            } else {
                echo "<script>alert('Prepare failed: " . $conn->error . "');</script>";
            }
        }

        $conn->close();
    }
    ?>
    <footer class="footer">
        <div class="info">
            <h2>Contact OHSO</h2>
            <span class="ohsologo"><img src="OSHO-LOGO.webp"></span>
            <br>
            <ul>
                <li>
                    <div class="footer-item">
                        <img src="gmail.png">
                        <span>ohso@umak.edu.ph</span>
                    </div>
                </li>
                <li>
                    <div class="footer-item">
                        <img src="map.png">
                        <span>J.P. Rizal Extn. West Rembo, Makati, Philippines, 1215</span>
                    </div>
                </li>
                <li>
                    <div class="footer-item">
                        <img src="phone-call.png">
                        <span>288820535</span>
                    </div>
                </li>
                <li>
                    <div class="footer-item">
                        <img src="facebook.png">
                    </div>
                </li>
            </ul>
        </div>
    </footer>
</body>
</html>