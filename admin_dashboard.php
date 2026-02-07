<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') { header("Location: index.php"); exit; }
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_doctor') {
    $name = $_POST['name']; $email = $_POST['email']; $phone = $_POST['phone']; $spec = $_POST['spec'];
    $conn->query("INSERT INTO users (full_name, email, password, role, phone_number) VALUES ('$name', '$email', 'docpass', 'Doctor', '$phone')");
    $id = $conn->insert_id;
    $conn->query("INSERT INTO patients (user_id, specialty) VALUES ($id, '$spec')");
    $message = "Doctor added.";
}

$pat_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='Patient'")->fetch_row()[0];
$doc_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='Doctor'")->fetch_row()[0];
$appt_count = $conn->query("SELECT COUNT(*) FROM appointments")->fetch_row()[0];
$doctors = $conn->query("SELECT u.full_name, u.phone_number, p.specialty FROM users u JOIN patients p ON u.user_id = p.user_id WHERE role='Doctor'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.tab-content{display:none}.tab-content.active{display:block}</style>
</head>
<body class="bg-gray-100 flex h-screen">
    <?php if($message): ?><div class="fixed top-5 left-1/2 bg-green-600 text-white px-4 py-2 rounded"><?= $message ?></div><?php endif; ?>
    <div class="w-64 bg-white shadow p-4 flex flex-col">
        <h1 class="text-2xl font-bold text-blue-800 mb-8">Admin Panel</h1>
        <button onclick="switchTab('dashboard')" class="text-left p-3 hover:bg-gray-100 mb-2">Dashboard</button>
        <button onclick="switchTab('doctors')" class="text-left p-3 hover:bg-gray-100 mb-2">Manage Doctors</button>
        <a href="logout.php" class="mt-auto text-red-600 p-3">Logout</a>
    </div>
    <div class="flex-1 p-8 overflow-auto">
        <div id="tab-dashboard" class="tab-content active">
            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded shadow"><h3 class="text-3xl font-bold"><?= $pat_count ?></h3><p>Patients</p></div>
                <div class="bg-white p-6 rounded shadow"><h3 class="text-3xl font-bold"><?= $doc_count ?></h3><p>Doctors</p></div>
                <div class="bg-white p-6 rounded shadow"><h3 class="text-3xl font-bold"><?= $appt_count ?></h3><p>Appointments</p></div>
            </div>
        </div>
        <div id="tab-doctors" class="tab-content">
            <h2 class="text-2xl font-bold mb-4">Doctors</h2>
            <div class="bg-white p-4 rounded shadow mb-6">
                <h3 class="font-bold mb-2">Add New Doctor</h3>
                <form method="POST" class="grid grid-cols-2 gap-4">
                    <input type="hidden" name="action" value="add_doctor">
                    <input type="text" name="name" placeholder="Name" class="border p-2" required>
                    <input type="email" name="email" placeholder="Email" class="border p-2" required>
                    <input type="text" name="phone" placeholder="Phone" class="border p-2" required>
                    <input type="text" name="spec" placeholder="Specialty" class="border p-2" required>
                    <button class="bg-blue-600 text-white p-2 rounded col-span-2">Add Doctor</button>
                </form>
            </div>
            <div class="bg-white rounded shadow p-4">
                <?php foreach($doctors as $d): ?>
                <div class="border-b p-2 flex justify-between"><span><?= $d['full_name'] ?></span><span class="text-gray-500"><?= $d['specialty'] ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>function switchTab(id){document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));document.getElementById('tab-'+id).classList.add('active');}</script>
</body>
</html>