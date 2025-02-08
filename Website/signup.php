<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="b.css">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding-right: 40px; /* Add space for the eye icon */
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .toggle-password img {
            width: 35px; 
            height: 35px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="Logo.png" alt="Logo Umak">
            <img src="OSHO-LOGO.webp" alt="Logo OHSO">
        </div>
        <div class="logo"><h2>Welcome to the Online Faculty Logbook!</h2></div>
        <?php
        include 'db_connection.php';

        $signupSuccess = false;
        $accountExists = false;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = 'student'; // Default role

            // Check for special keyword in the password
            if (strpos($password, 'teacher@$#%') !== false) {
                $role = 'teacher';
            }

            // Hash the password before storing it
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $accountExists = true;
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

                if ($stmt->execute()) {
                    $signupSuccess = true;
                    $userId = $stmt->insert_id;
                    echo "<script>
                        localStorage.setItem('currentUser', JSON.stringify({ id: $userId, email: '$email' }));
                    </script>";
                } else {
                    echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
                }
            }

            $stmt->close();
            $conn->close();
        }
        ?>
        <form id="signupForm" method="post" action="signup.php">
            <h2>Sign Up</h2>
            <div class="input-group">
                <input required type="text" name="name" autocomplete="off" class="input" placeholder="Name">
            </div>
            <div class="input-group">
                <input required type="email" name="email" autocomplete="off" class="input" placeholder="Email">
            </div>
            <div class="input-group password-container">
                <input required type="password" name="password" autocomplete="off" class="password" id="password" placeholder="Password">
                <span class="toggle-password" onclick="togglePassword()">
                    <img src="visual.png" id="toggleIcon">
                </span>
            </div>
            <?php if ($signupSuccess): ?>
                <p style="color:green;">Registration successful! Proceed to login.</p>
                <button type="button" onclick="window.location.href='index.php'">Log In</button>
            <?php elseif ($accountExists): ?>
                <p style="color:red;">Account already exists. Please choose a different email.</p>
                <button type="submit">Sign Up</button>
            <?php else: ?>
                <button type="submit">Sign Up</button>
            <?php endif; ?>
        </form>
    </div>
    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var toggleIcon = document.getElementById("toggleIcon");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.src = "eye.png"; 
            } else {
                passwordField.type = "password";
                toggleIcon.src = "visual.png"; 
            }
        }
    </script>
</body>
</html>