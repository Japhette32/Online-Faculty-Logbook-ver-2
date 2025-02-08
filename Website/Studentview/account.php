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

$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

//Get information from the registration tab
$registrations = [];
$stmt = $conn->prepare("SELECT r.date, r.time, r.reason, u.name as teacher_name FROM registrations r JOIN users u ON r.teacher = u.id WHERE r.user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
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
    <link rel="stylesheet" href="account.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .scrollable-container {
            overflow-y: auto;
            position: relative;
        }

        .calendar-container {
            margin-top: 20px;
            width: 85%;
            border: 6px solid black;
            border-radius: 10px;
            padding: 25px;
            background-color: white;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
            font-size: 1.5em;
        }

        .calendar-header {
            font-weight: bold;
            background-color: rgb(6, 6, 134);
            color: white;
            padding: 10px;
        }

        .calendar-day {
            padding: 10px;
            border: 1px solid black;
            background-color: #f2f2f2;
            height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
        }

        .calendar-title {
            font-size: 2em;
            text-align: center;
            margin-top: 10px;
        }

        .scrollable-table {
            max-height: 570px;
            overflow-y: auto;
            overflow-x: auto;
            display: block;
            width: 85%;
            text-align: center;
        }

        .scrollable-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .scrollable-table th {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            background-color: rgb(6, 6, 134);
        }

        .scrollable-container td {
            background-color: #fff;
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        .consultation-table td {
            min-width: 180px;
        }
        .popup {
    visibility: hidden;
    position: absolute;
    border: 2px solid black;
    text-align: left;
    padding: 10px;
    z-index: 1001; /* Higher than the overlay */
    border-radius: 10px;
    font-size: 1.2em;
    opacity: 0; 
    transform: scale(0.8); 
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s 0.2s;
    box-shadow: 3px 5px 15px rgba(0, 0, 0, 0.3);
}

.popup.show {
    color:#E0E0E0;
    visibility: visible;
    opacity: 1; 
    transform: scale(1); 
    background-color:#3A3D46;
    transition-delay: 0s;
}

.popup .close {
    cursor: pointer;
    float: right;
    font-size: 20px;
    font-weight: bold;
    font-size: 1.5em;
}

.popup .close:hover {
    transition: 0.2s ;
    font-size: 30px;
    color: red;
}

.tooltip-container {
    cursor: pointer;
}

.reason-text {
    display: block;
    margin-bottom: 10px;
}

</style>

</head>

</style>
</head>

<body>
    <div class="nav">
        <img src="Logo.png" alt="Umak Logo">
        <img src="OSHO-LOGO.webp" alt="OSHO logo">
        <h2>Online Faculty Logbook</h2>
        <div class="line"></div>
        <ul>
            <li><a href="account.php" class="active">Your Schedule</a></li>
            <li><a href="registration.php">Registration</a></li>
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
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        $schedule = [];

                        foreach ($registrations as $registration) {
                            $day = date('l', strtotime($registration['date']));
                            $time = date('g:i A', strtotime($registration['time']));
                            $schedule[$time][$day][] = $registration;
                        }

                        // Sort the schedule by time intervals
                        uksort($schedule, function ($a, $b) {
                            $timeA = DateTime::createFromFormat('g:i A', $a);
                            $timeB = DateTime::createFromFormat('g:i A', $b);
                            return $timeA <=> $timeB;
                        });

                        $timeIntervals = array_keys($schedule);
                        $rowCount = 0;


                        foreach ($timeIntervals as $timeInterval) {
                            echo '<tr>';
                            echo '<td>' . $timeInterval . '</td>';
                            foreach ($days as $day) {
                                echo '<td>';
                                if (isset($schedule[$timeInterval][$day])) {
                                    foreach ($schedule[$timeInterval][$day] as $entry) {
                                        $formattedDate = date('F j, Y', strtotime($entry['date']));
                                        $formattedTime = date('h:i A', strtotime($entry['time'])); 
                                        echo '<div class="tooltip-container" onclick="showPopup(this, \'' . htmlspecialchars($entry['teacher_name']) . '\', \'' . $formattedDate . '\', \'' . $formattedTime . '\', \'' . htmlspecialchars($entry['reason']) . '\')">';
                                        echo htmlspecialchars($entry['reason']);
                                        echo '</div>';
                                    }
                                }
                                echo '</td>';
                            }
                            echo '</tr>';
                            $rowCount++;
                        }

                        // Add empty rows if less than 5 rows
                        while ($rowCount < 5) {
                            echo '<tr>';
                            echo '<td></td>';
                            foreach ($days as $day) {
                                echo '<td></td>';
                            }
                            echo '</tr>';
                            $rowCount++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="remark"> * Refresh to see the most recent updates to your schedule</p>
            <p class="mark"> * Click to see teacher, date, and time</p>
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
            
                // Display schedules for the current day
                foreach ($registrations as $registration) {
                    if ($registration['date'] == $date) {
                        $formattedDate = date('F j, Y', strtotime($registration['date']));
                        $formattedTime = date('g:i A', strtotime($registration['time'])); // 12-hour format with AM/PM
                        echo "
                        <div class='tooltip-container' onclick='showPopup(this, \"{$registration['teacher_name']}\", \"{$formattedDate}\", \"{$formattedTime}\", \"{$registration['reason']}\")'>
                            <span class='reason-text'>{$registration['reason']}</span>
                        </div>
                        ";
                    }
                }
            
                echo "</div>";
            }
            ?>
        </div>
    </div>

    </style>

    <script>
function showPopup(element, teacher, date, time, reason) {
    // Create a new popup element
    var popup = document.createElement("div");
    popup.classList.add("popup");

    // Create the close button
    var closeButton = document.createElement("span");
    closeButton.classList.add("close");
    closeButton.innerHTML = "&times;";
    closeButton.onclick = function() {
        closePopup(popup);
    };

    // Create the content
    var popupContent = document.createElement("p");
    popupContent.innerHTML = "Teacher: " + teacher + "<br>Date: " + date + "<br>Time: " + time + "<br>Reason: " + reason;

    // Append the close button and content to the popup
    popup.appendChild(closeButton);
    popup.appendChild(popupContent);

    // Set the position of the popup
    var rect = element.getBoundingClientRect();
    popup.style.top = rect.top + window.scrollY + "px";
    popup.style.left = rect.left + window.scrollX + "px";

    // Add the popup to the body
    document.body.appendChild(popup);

    // Display the popup with a slight delay to trigger the transition
    setTimeout(function() {
        popup.classList.add("show");
    }, 10);
}

function closePopup(popup) {
    popup.classList.remove("show");
    setTimeout(function() {
        popup.remove();
    }, 300);
}

window.onclick = function(event) {
    var popups = document.getElementsByClassName("popup");
    for (var i = 0; i < popups.length; i++) {
        if (event.target == popups[i]) {
            closePopup(popups[i]);
        }
    }
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