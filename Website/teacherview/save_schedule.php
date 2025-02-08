<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

// Insert or update schedule
foreach ($days as $day) {
    if (isset($_POST[$day . 'StartTime']) && isset($_POST[$day . 'EndTime'])) {
        foreach ($_POST[$day . 'StartTime'] as $index => $startTime) {
            $endTime = $_POST[$day . 'EndTime'][$index];

            if (!empty($startTime) && !empty($endTime)) {
                // Check if the schedule already exists
                $stmt = $conn->prepare("SELECT id FROM teacher_schedules WHERE user_id = ? AND day_of_week = ? AND start_time = ? AND end_time = ?");
                $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Update existing schedule
                    $stmt->close();
                    $stmt = $conn->prepare("UPDATE teacher_schedules SET start_time = ?, end_time = ? WHERE user_id = ? AND day_of_week = ?");
                    $stmt->bind_param("ssis", $startTime, $endTime, $user_id, ucfirst($day));
                } else {
                    // Insert new schedule
                    $stmt->close();
                    $stmt = $conn->prepare("INSERT INTO teacher_schedules (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $user_id, ucfirst($day), $startTime, $endTime);
                }

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
    $schedule[$timeInterval][$day][] = "<div class='time-slot'>Free Time</div>";
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
            $html .= '<td>';
            if (isset($schedule[$timeInterval][$day])) {
                $html .= '<div class="scrollable-container">';
                foreach ($schedule[$timeInterval][$day] as $entry) {
                    $html .= $entry;
                }
                $html .= '</div>';
            } else {
                $html .= '<div class="time-slot">Free Time</div>';
            }
            $html .= '</td>';
        }
        $html .= '<td><button type="button" class="remove-row-btn" onclick="removeRow(this)">Remove</button></td>';
    } else {
        $html .= '<td colspan="6">No Schedule</td>';
        $html .= '<td><button type="button" class="remove-row-btn" onclick="removeRow(this)">Remove</button></td>';
    }
    $html .= '</tr>';
}

echo json_encode(['success' => true, 'html' => $html]);
?>