YO SEAN READ THIS 
MEDYO WEIRD SAYO WEBSITE SINCE DI PA RESPONSIVE 
GAWA KA DATA BASE SA XAMPP PUNTA KASA http://localhost/phpmyadmin/
THEN CLICK New tapos create database 
Lagay mo database name is faculty_logbook
Click mo yung  new sa ilalim ng database mo faculty_logbook
then punta ka sa sql then type this
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('student','teacher') NOT NULL DEFAULT 'student',
  `name` varchar(255) NOT NULL
)
Then Click Go
Then dun sa users punta ka sa sql then type this
 INSERT INTO `users` (`id`, `password`, `email`, `role`, `name`) VALUES
(40, '$2y$10$S55WBPAWnb8gZRZwG8TbdO29mn1NU4LiRJyQrrasd7bdmN4Nb8CAa', 'Teacher1@gmail.com', 'teacher', 'Teacher1'),
(41, '$2y$10$Pl0ALrxkpsEdgcxBBy4xIuHSY7hFp85hpoXjoLoXw6mjXGd7vfPWW', 'Student123@gmail.com', 'student', 'Claudine De Guzman'),
(42, '$2y$10$/IkZISHz3R2cVrhTDYsSVO0uolt/lY2dfBpeEfDFLIinvDMDjHygC', 'Aming@gmail.com', 'student', 'Sean Dale Aming'),
(43, '$2y$10$wlmkYp2y82iKJttqCD4kvuK7AM7iNbQ/b5bqzXz6wiCnSXO1YZ6Ou', 'Teacher3@gmail.com', 'teacher', 'Teacher3'),
(44, '$2y$10$gnGS/0rD/2PCP2hBTu8LEe3FJl1a/7bRJB74qVuYa9FvPrvciIR6a', 'Jyro@gmail.com', 'student', 'Jyro');
Then Click Go

Then dun ule sa faculty_logbook new ulit sa new then sql 
CREATE TABLE `registrations` (
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `teacher` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `reason` text DEFAULT NULL
)
Then Click Go

Then dun sa registrations punta ka sql then type this 
INSERT INTO `registrations` (`user_id`, `name`, `section`, `teacher`, `date`, `time`, `reason`) VALUES
(40, 'Magpulong, Japhette Louis, C.', 'G12 - 01 CPG', '40', '2025-02-05', '14:40:00', 'Passing of Requirements'),
(40, 'Magpulong, Japhette Louis, C.', 'G12 - 01 CPG', '40', '2025-02-12', '14:40:00', 'Asking About research'),
(43, 'Magpulong, Japhette Louis, C.', 'G12 - 01 CPG', '43', '2025-02-06', '07:30:00', 'Passing of Requirements'),
(42, 'Magpulong, Japhette Louis, C.', 'G12 - 01 CPG', '43', '2025-02-06', '19:30:00', 'Passing of Requirements'),
(42, 'Magpulong, Japhette Louis, C.', 'G12 - 01 CPG', '40', '2025-02-05', '14:30:00', 'pass requirements'),
(42, 'Claudine De Guzman', 'G12 - 01 CPG', '40', '2025-02-03', '14:30:00', 'Passing of Requirements Of Research'),
(42, 'Magpulong, Japhette Louis C.', 'G12 - 01 CPG', '43', '2025-02-06', '07:07:00', 'Passing of Requirements Of Research');
Then Click Go

Then dun ule sa faculty_logbook new ulit sa new then sql 
CREATE TABLE `teacher_schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `meeting` varchar(255) DEFAULT NULL
)
Then Click Go
Then dun sa teacheer_schedules  punta ka sql then type this 
INSERT INTO `teacher_schedules` (`id`, `user_id`, `day_of_week`, `start_time`, `end_time`, `meeting`) VALUES
(22, 40, 'Monday', '14:30:00', '15:30:00', NULL),
(23, 40, 'Monday', '06:30:00', '07:33:00', NULL),
(24, 40, 'Tuesday', '16:30:00', '17:30:00', NULL),
(25, 40, 'Wednesday', '14:33:00', '15:33:00', NULL),
(26, 43, 'Thursday', '07:07:00', '08:30:00', NULL),
(27, 42, 'Friday', '17:30:00', '18:00:00', NULL),
(28, 40, 'Friday', '18:39:00', '19:00:00', NULL),
(29, 43, 'Monday', '14:30:00', '15:00:00', NULL),
(30, 43, 'Tuesday', '08:30:00', '09:30:00', NULL);
Then Click Go

