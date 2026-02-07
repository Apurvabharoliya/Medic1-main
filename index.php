<?php
session_start();
include 'db_connect.php';

$message = "";
$msg_type = "";

// LOGIN LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_btn'])) {
    $email = $_POST['loginEmail'];
    $password = $_POST['loginPassword'];

    // Static Staff Check (from app.py)
    $staff = [
        'admin@medic1.com' => ['pass' => 'adminpass', 'role' => 'Admin', 'redirect' => 'admin_dashboard.php'],
        'doctor@medic1.com' => ['pass' => 'docpass', 'role' => 'Doctor', 'redirect' => 'doctor_dashboard.php'],
        'lab@medic1.com' => ['pass' => 'labpass', 'role' => 'Lab Asst', 'redirect' => 'lab_dashboard.php']
    ];

    if (isset($staff[$email]) && $staff[$email]['pass'] === $password) {
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $staff[$email]['role'];
        // Fetch ID from DB for consistency
        $res = $conn->query("SELECT user_id, full_name FROM users WHERE email='$email'");
        if ($row = $res->fetch_assoc()) {
            $_SESSION['id'] = $row['user_id'];
            $_SESSION['name'] = $row['full_name'];
        }
        header("Location: " . $staff[$email]['redirect']);
        exit;
    } else {
        // Patient Check
        $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email=? AND role='Patient'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($password === $row['password']) {
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $row['user_id'];
                $_SESSION['name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                header("Location: patient_dashboard.php");
                exit;
            }
        }
        $message = "Invalid email or password.";
        $msg_type = "error";
    }
}

// REGISTER LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_btn'])) {
    $name = $_POST['registerName'];
    $email = $_POST['registerEmail'];
    $pass = $_POST['registerPassword'];

    $check = $conn->query("SELECT email FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "Email already registered!";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'Patient')");
        $stmt->bind_param("sss", $name, $email, $pass);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $pat_id = "P-" . ($new_id + 1000);
            $conn->query("UPDATE users SET patient_id='$pat_id' WHERE user_id=$new_id");
            $conn->query("INSERT INTO patients (user_id) VALUES ($new_id)");
            $message = "Registration successful! Please login.";
            $msg_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medic1 Clinic - Premium Healthcare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative; }
        .close-modal { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #aaa; }
    </style>
</head>
<body class="antialiased bg-gray-50">
    <?php if ($message): ?>
    <div onclick="this.style.display='none'" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-[2000] px-6 py-3 rounded shadow-lg text-white cursor-pointer <?= $msg_type == 'success' ? 'bg-green-600' : 'bg-red-600' ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <nav class="bg-white shadow-md fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="#" class="text-3xl font-bold text-blue-700 flex items-center gap-2"><i class="fas fa-heartbeat"></i> Medic1+</a>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#" class="text-gray-600 hover:text-blue-600 font-medium">Home</a>
                    <a href="#services" class="text-gray-600 hover:text-blue-600 font-medium">Services</a>
                    <button onclick="openModal('login-modal')" class="text-blue-600 font-semibold hover:text-blue-800">Login</button>
                    <button onclick="openModal('register-modal')" class="bg-blue-600 text-white px-5 py-2 rounded-full font-medium hover:bg-blue-700 transition">Get Started</button>
                </div>
            </div>
        </div>
    </nav>

    <section id="home" class="pt-32 pb-20 bg-gradient-to-br from-blue-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <h1 class="text-5xl font-extrabold text-gray-900 leading-tight mb-6">Advanced Healthcare <br> <span class="text-blue-600">For Your Family</span></h1>
                <p class="text-xl text-gray-600 mb-8 max-w-lg">Experience the future of medical care with our state-of-the-art facility.</p>
                <div class="flex gap-4">
                    <button onclick="openModal('register-modal')" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 shadow-lg">Book Appointment</button>
                </div>
            </div>
            <div class="md:w-1/2 flex justify-center relative">
                <img src="assets/image_b6aaa3.png" alt="Doctor" class="rounded-2xl shadow-2xl z-10 relative max-w-md w-full object-cover">
            </div>
        </div>
    </section>

    <section id="services" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16"><h2 class="text-3xl font-bold text-gray-900 mb-4">Our Medical Services</h2></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 text-center"><i class="fas fa-stethoscope text-2xl text-blue-600 mb-4"></i><h3 class="text-xl font-bold">General Checkup</h3></div>
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 text-center"><i class="fas fa-flask text-2xl text-green-600 mb-4"></i><h3 class="text-xl font-bold">Laboratory</h3></div>
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100 text-center"><i class="fas fa-heart text-2xl text-red-600 mb-4"></i><h3 class="text-xl font-bold">Cardiology</h3></div>
            </div>
        </div>
    </section>

    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('login-modal')">&times;</span>
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Welcome Back</h2>
            <form method="POST">
                <input type="hidden" name="login_btn" value="1">
                <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email</label><input type="email" name="loginEmail" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Password</label><input type="password" name="loginPassword" required class="w-full px-3 py-2 border rounded-lg"></div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700">Login</button>
            </form>
            <p class="mt-4 text-center text-sm"><a href="#" onclick="switchModal('login-modal', 'register-modal')" class="text-blue-600 font-semibold">Sign Up</a></p>
        </div>
    </div>

    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('register-modal')">&times;</span>
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Create Account</h2>
            <form method="POST">
                <input type="hidden" name="register_btn" value="1">
                <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Full Name</label><input type="text" name="registerName" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email</label><input type="email" name="registerEmail" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Password</label><input type="password" name="registerPassword" required class="w-full px-3 py-2 border rounded-lg"></div>
                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700">Register</button>
            </form>
            <p class="mt-4 text-center text-sm"><a href="#" onclick="switchModal('register-modal', 'login-modal')" class="text-blue-600 font-semibold">Login</a></p>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = 'block'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        function switchModal(close, open) { closeModal(close); openModal(open); }
        window.onclick = function(event) { if (event.target.classList.contains('modal')) { event.target.style.display = "none"; } }
    </script>
</body>
</html>