<?php
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "faculty_logbook";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

$user_id = $data->userId;
$name = $data->name;
$section = $data->section;
$teacher = $data->teacher;
$date = $data->date;
$time = $data->time;
$reason = $data->reason;

$sql = "INSERT INTO registrations (user_id, name, section, teacher, date, time, reason)
        VALUES ('$user_id', '$name', '$section', '$teacher', '$date', '$time', '$reason')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["message" => "Registration successful"]);
} else {
    echo json_encode(["message" => "Error: " . $sql . "<br>" . $conn->error]);
}

$conn->close();
?>