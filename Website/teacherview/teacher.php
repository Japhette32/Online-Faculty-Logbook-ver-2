<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
    } else {
        header("Location: ../index.php");
        exit();
    }
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Retrieve the user's name from the database
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $day = $_POST['day'];
        $startTime = $_POST['startTime'];
        $endTime = $_POST['endTime'];

        $stmt = $conn->prepare("DELETE FROM teacher_schedules WHERE user_id = ? AND day_of_week = ? AND start_time = ? AND end_time = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        $conn->close();

        header("Location: teacher.php");
        exit();
    }

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    foreach ($days as $day) {
        if (isset($_POST[$day . 'StartTime']) && isset($_POST[$day . 'EndTime'])) {
            foreach ($_POST[$day . 'StartTime'] as $index => $startTime) {
                $endTime = $_POST[$day . 'EndTime'][$index];

                if (!empty($startTime) && !empty($endTime)) {
                    // Insert new schedule
                    $stmt = $conn->prepare("INSERT INTO teacher_schedules (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)
                                            ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)");
                    if (!$stmt) {
                        die("Prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);

                    if (!$stmt->execute()) {
                        die("Execute failed: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
        }
    }

    header("Location: teacher.php");
    exit();
}

// Retrieve the updated schedule
$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();

$schedule = [];
$scheduleForModal = [];
while ($row = $result->fetch_assoc()) {
    $day = strtolower($row['day_of_week']);
    $timeInterval = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
    $schedule[$timeInterval][$day] = 'Scheduled';
    $scheduleForModal[$day][] = ['start' => $row['start_time'], 'end' => $row['end_time']];
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Schedule</title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        .schedule-table th, .schedule-table td {
            padding: 10px;
            text-align: center;
        }
        .btn-remove, .btn-add {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        .save-btn {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="nav">
        <img src="Logo.png" alt="Umak Logo">
        <img src="OSHO-LOGO.webp" alt="OSHO logo">
        <h2>Online Faculty Logbook</h2>
        <div class="line"></div>
        <ul>
            <li><a href="teacher.php" class="active">Your Schedule</a></li>
            <li><a href="facultymap.html">Faculty Map</a></li>
        </ul>
        <a href="../index.php" class="logout-btn">Log Out</a>
    </div>
    <div class="container">
        <div class="account-info">
            <h2 class="account-name">Welcome, <?php echo htmlspecialchars($name); ?></h2>
            <h3>Weekly Consultation Schedule</h3>
            <div class="scrollable-table">
                <table class="consultation-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th class="remove-column" style="display: none;">Remove</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        // Generate the table rows
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                        $timeIntervals = array_keys($schedule);
                        foreach ($timeIntervals as $timeInterval) {
                            echo '<tr>';
                            echo '<td>' . $timeInterval . '</td>';
                            foreach ($days as $day) {
                                echo '<td>' . (isset($schedule[$timeInterval][$day]) ? $schedule[$timeInterval][$day] : '') . '</td>';
                            }
                            echo '<td class="remove-column" style="display: none;"><button type="button" class="remove-row-btn" onclick="removeRow(this, \'' . $timeInterval . '\')">Remove</button></td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="remark"> * Refresh to see the most recent updates to your schedule</p>
            <p class="mark"> * Click to see teacher, date, and time</p>
        </div>
        <button id="editScheduleBtn" onclick="openModal()">+</button>
        <button id="toggleRemoveBtn" onclick="toggleRemoveColumn()">-</button>
    </div>

    <div class="calendar-container">
        <h3 class="calendar-title">Monthly Schedule</h3>
        <h3 class="calendar-title">Current Month: <?php echo date('F Y'); ?></h3>
        <div class="calendar">
            <?php
            $currentMonth = new DateTime();
            $currentMonth->modify('first day of this month');
            $daysInMonth = $currentMonth->format('t');
            $firstDayOfMonth = $currentMonth->format('N') - 1;

            $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            // Print the days of the week headers
            foreach ($daysOfWeek as $day) {
                echo "<div class='calendar-header'>$day</div>";
            }

            // Print empty cells for days before the first day of the month
            for ($i = 0; $i < $firstDayOfMonth; $i++) {
                echo "<div class='calendar-day'></div>";
            }

            // Print the days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $currentMonth->format('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                echo "<div class='calendar-day'>";
                echo "<div class='date'>$day</div>";

                echo "</div>";
            }
            ?>
        </div>
    </div>

    <!-- Edit Schedule Popup -->
    <div id="editScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 class="modal-title">Edit Schedule</h2>
            <form id="editScheduleForm" method="POST" action="teacher.php">
                <table class="schedule-table">
                    <tr>
                        <th>Day</th>
                        <th>Time Slot</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    foreach ($days as $day) {
                        echo '<tr>';
                        echo '<td>' . ucfirst($day) . '</td>';
                        echo '<td>';
                        if (isset($scheduleForModal[$day])) {
                            foreach ($scheduleForModal[$day] as $time) {
                                echo '<div class="time-inputs">';
                                echo '<input type="time" name="' . $day . 'StartTime[]" value="' . $time['start'] . '">';
                                echo ' - ';
                                echo '<input type="time" name="' . $day . 'EndTime[]" value="' . $time['end'] . '">';
                                echo '<button type="button" class="btn-remove" onclick="removeTime(this, \'' . $day . '\', \'' . $time['start'] . '\', \'' . $time['end'] . '\')">➖</button>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="time-inputs">';
                            echo '<input type="time" name="' . $day . 'StartTime[]">';
                            echo ' - ';
                            echo '<input type="time" name="' . $day . 'EndTime[]">';
                            echo '<button type="button" class="btn-remove" onclick="removeTime(this, \'' . $day . '\', \'\', \'\')">➖</button>';
                            echo '</div>';
                        }
                        echo '</td>';
                        echo '<td>';
                        echo '<button type="button" class="btn-add" onclick="addTime(\'' . ucfirst($day) . '\')">➕</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
                <button type="submit" class="save-btn">Save Schedule</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            var modal = document.getElementById('editScheduleModal');
            modal.style.display = 'block';
            setTimeout(function() {
                modal.classList.add('show');
            }, 10); // Slight delay to trigger the transition
        }

        function closeModal() {
            var modal = document.getElementById('editScheduleModal');
            modal.classList.remove('show');
            setTimeout(function() {
                modal.style.display = 'none';
            }, 300); // Wait for the transition to complete
        }

        function addTime(day) {
            var container = document.getElementById(day.toLowerCase() + 'Inputs');
            var newInput = document.createElement('div');
            newInput.className = 'time-inputs';
            newInput.innerHTML = `
                <input type="time" name="${day.toLowerCase()}StartTime[]">
                <span>-</span>
                <input type="time" name="${day.toLowerCase()}EndTime[]">
                <button type="button" class="btn-remove" onclick="removeTime(this, '${day.toLowerCase()}', '', '')">➖</button>
            `;
            container.appendChild(newInput);
        }

        function removeTime(button, day, startTime, endTime) {
            var container = button.parentElement;
            container.remove();

            if (startTime && endTime) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'teacher.php';

                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                var dayInput = document.createElement('input');
                dayInput.type = 'hidden';
                dayInput.name = 'day';
                dayInput.value = day;
                form.appendChild(dayInput);

                var startTimeInput = document.createElement('input');
                startTimeInput.type = 'hidden';
                startTimeInput.name = 'startTime';
                startTimeInput.value = startTime;
                form.appendChild(startTimeInput);

                var endTimeInput = document.createElement('input');
                endTimeInput.type = 'hidden';
                endTimeInput.name = 'endTime';
                endTimeInput.value = endTime;
                form.appendChild(endTimeInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function removeRow(button, timeInterval) {
            var row = button.closest('tr');
            var day = button.closest('td').dataset.day;
            var times = timeInterval.split(' - ');
            var startTime = times[0];
            var endTime = times[1];

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'teacher.php';

            var actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);

            var dayInput = document.createElement('input');
            dayInput.type = 'hidden';
            dayInput.name = 'day';
            dayInput.value = day;
            form.appendChild(dayInput);

            var startTimeInput = document.createElement('input');
            startTimeInput.type = 'hidden';
            startTimeInput.name = 'startTime';
            startTimeInput.value = startTime;
            form.appendChild(startTimeInput);

            var endTimeInput = document.createElement('input');
            endTimeInput.type = 'hidden';
            endTimeInput.name = 'endTime';
            endTimeInput.value = endTime;
            form.appendChild(endTimeInput);

            document.body.appendChild(form);
            form.submit();
        }

        function toggleRemoveColumn() {
            var removeColumns = document.querySelectorAll('.remove-column');
            removeColumns.forEach(function(column) {
                column.style.display = column.style.display === 'none' ? '' : 'none';
            });
        }
    </script>
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