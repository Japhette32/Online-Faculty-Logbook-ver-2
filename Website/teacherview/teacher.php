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
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            exit();
        }
        $stmt->close();
        $conn->close();

        echo json_encode(['success' => true]);
        exit();
    }

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    // Insert or update schedule
    foreach ($days as $day) {
        if (isset($_POST[$day . 'StartTime']) && isset($_POST[$day . 'EndTime'])) {
            foreach ($_POST[$day . 'StartTime'] as $index => $startTime) {
                $endTime = $_POST[$day . 'EndTime'][$index];

                if (!empty($startTime) && !empty($endTime)) {
                    // Insert new schedule
                    $stmt = $conn->prepare("INSERT INTO teacher_schedules (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)
                                            ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)");
                    if (!$stmt) {
                        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
                        exit();
                    }
                    $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);

                    if (!$stmt->execute()) {
                        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
                        exit();
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Retrieve the updated schedule
    $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        exit();
    }
    $result = $stmt->get_result();

    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $day = strtolower($row['day_of_week']);
        $timeInterval = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
        $schedule[$timeInterval][$day] = 'Scheduled';
    }

    $stmt->close();
    $conn->close();

    // Generate the table rows
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $timeIntervals = array_keys($schedule);
    $html = '';
    for ($i = 0; $i < 5; $i++) {
        $html .= '<tr>';
        if (isset($timeIntervals[$i])) {
            $timeInterval = $timeIntervals[$i];
            $html .= '<td>' . $timeInterval . '</td>';
            foreach ($days as $day) {
                $html .= '<td>' . (isset($schedule[$timeInterval][$day]) ? $schedule[$timeInterval][$day] : '') . '</td>';
            }
            $html .= '<td><button type="button" class="remove-row-btn" onclick="removeRow(this, \'' . $timeInterval . '\')">Remove</button></td>';
        } else {
            $html .= '<td></td>';
            foreach ($days as $day) {
                $html .= '<td></td>';
            }
            $html .= '<td></td>';
        }
        $html .= '</tr>';
    }

    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}

// Retrieve the schedule for the edit form
$schedule = [];
$stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $day = strtolower($row['day_of_week']);
        $schedule[$day][] = [
            'start' => date('H:i', strtotime($row['start_time'])),
            'end' => date('H:i', strtotime($row['end_time']))
        ];
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Schedule</title>
    <link rel="stylesheet" href="teacher.css">
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
            <button id="editScheduleBtn" onclick="openModal()">Edit Schedule</button>
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
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        include 'db_connection.php';

                        // Retrieve the schedule from the database
                        $stmt = $conn->prepare("SELECT day_of_week, start_time, end_time FROM teacher_schedules WHERE user_id = ? ORDER BY start_time ASC");
                        if (!$stmt) {
                            echo "<tr><td colspan='7'>Prepare failed: " . $conn->error . "</td></tr>";
                            exit();
                        }
                        $stmt->bind_param("i", $user_id);
                        if (!$stmt->execute()) {
                            echo "<tr><td colspan='7'>Execute failed: " . $stmt->error . "</td></tr>";
                            exit();
                        }
                        $result = $stmt->get_result();

                        $schedule = [];
                        while ($row = $result->fetch_assoc()) {
                            $day = strtolower($row['day_of_week']);
                            $timeInterval = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
                            $schedule[$timeInterval][$day] = 'Scheduled';
                        }

                        $stmt->close();
                        $conn->close();

                        // Generate the table rows
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                        $timeIntervals = array_keys($schedule);
                        for ($i = 0; $i < 5; $i++) {
                            echo '<tr>';
                            if (isset($timeIntervals[$i])) {
                                $timeInterval = $timeIntervals[$i];
                                echo '<td>' . $timeInterval . '</td>';
                                foreach ($days as $day) {
                                    echo '<td>' . (isset($schedule[$timeInterval][$day]) ? $schedule[$timeInterval][$day] : '') . '</td>';
                                }
                                echo '<td><button type="button" class="remove-row-btn" onclick="removeRow(this, \'' . $timeInterval . '\')">Remove</button></td>';
                            } else {
                                echo '<td></td>';
                                foreach ($days as $day) {
                                    echo '<td></td>';
                                }
                                echo '<td></td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
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
            <h2>Edit Schedule</h2>
            <form id="editScheduleForm">
                <div id="timeInputsContainer" class="scrollable-container">
                    <?php
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                    foreach ($days as $day) {
                        echo '<div class="time-inputs" id="' . $day . 'Inputs">';
                        echo '<label>' . ucfirst($day) . ':</label>';
                        if (isset($schedule[$day])) {
                            foreach ($schedule[$day] as $time) {
                                echo '<div class="time-inputs">';
                                echo '<input type="time" name="' . $day . 'StartTime[]" value="' . $time['start'] . '">';
                                echo '<span>-</span>';
                                echo '<input type="time" name="' . $day . 'EndTime[]" value="' . $time['end'] . '">';
                                echo '<button type="button" class="remove-time-btn" onclick="removeTime(this)">-</button>';
                                echo '</div>';
                            }
                        } else {
                            echo '<input type="time" name="' . $day . 'StartTime[]">';
                            echo '<span>-</span>';
                            echo '<input type="time" name="' . $day . 'EndTime[]">';
                            echo '<button type="button" class="remove-time-btn" onclick="removeTime(this)">-</button>';
                        }
                        echo '<button type="button" class="add-time-btn" onclick="addTime(\'' . ucfirst($day) . '\')">+</button>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <button type="button" class="save-schedule-btn" onclick="saveSchedule()">Save Schedule</button>
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
        <button type="button" class="remove-time-btn" onclick="removeTime(this)">-</button>
    `;
    container.appendChild(newInput);
}

function removeTime(button) {
    var container = button.parentElement;
    container.remove();
}

function removeRow(button, timeInterval) {
    var row = button.closest('tr');
    var day = button.closest('td').dataset.day;
    var times = timeInterval.split(' - ');
    var startTime = times[0];
    var endTime = times[1];

    console.log('Removing row:', { day, startTime, endTime });

    fetch('teacher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'delete',
            day: day,
            startTime: startTime,
            endTime: endTime
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response from server:', data);
        if (data.success) {
            row.remove();
        } else {
            alert('Failed to remove schedule: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove schedule.');
    });
}

function saveSchedule() {
    var form = document.getElementById('editScheduleForm');
    var formData = new FormData(form);

    fetch('teacher.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the schedule table
            document.getElementById('scheduleTableBody').innerHTML = data.html;
            closeModal();
        } else {
            alert('Failed to save schedule: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save schedule.');
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