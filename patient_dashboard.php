<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Patient') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['id'];
$message = "";

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'book_appt') {
        $doctor_id = $_POST['doctor_id'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $reason = $_POST['reason'];
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, doctor_id, appt_date, appt_time, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $doctor_id, $date, $time, $reason);
        if ($stmt->execute()) $message = "Appointment booked!";
    }
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $phone = $_POST['phone'];
        $stmt = $conn->prepare("UPDATE patients SET phone=? WHERE user_id=?");
        $stmt->bind_param("si", $phone, $user_id);
        $stmt->execute();
        $message = "Profile updated!";
    }
}

// Fetch Data
$user = $conn->query("SELECT u.*, p.* FROM users u JOIN patients p ON u.user_id = p.user_id WHERE u.user_id=$user_id")->fetch_assoc();
$appointments = $conn->query("SELECT a.*, u.full_name as doctor_name FROM appointments a LEFT JOIN users u ON a.doctor_id = u.user_id WHERE a.user_id=$user_id ORDER BY appt_date DESC");
$prescriptions = $conn->query("SELECT * FROM prescriptions WHERE user_id=$user_id");
$reports = $conn->query("SELECT * FROM lab_reports WHERE user_id=$user_id");
$doctors = $conn->query("SELECT user_id, full_name FROM users WHERE role='Doctor'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-content { display: none; } .tab-content.active { display: block; }
        .sidebar-link.active { background-color: #ebf8ff; color: #2b6cb0; border-right: 4px solid #2b6cb0; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <?php if($message): ?><div onclick="this.remove()" class="fixed top-5 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-6 py-3 rounded shadow-lg cursor-pointer"><?= $message ?></div><?php endif; ?>

    <div class="flex h-screen">
        <div class="w-64 bg-white shadow-lg flex flex-col">
            <div class="p-6 border-b"><h1 class="text-2xl font-bold text-blue-700">Medic1+</h1><p class="text-sm text-gray-500">Patient Portal</p></div>
            <nav class="flex-1 p-4 space-y-2">
                <button onclick="switchTab('dashboard')" class="sidebar-link active w-full text-left p-3 rounded">Dashboard</button>
                <button onclick="switchTab('appointments')" class="sidebar-link w-full text-left p-3 rounded">Appointments</button>
                <button onclick="switchTab('medical')" class="sidebar-link w-full text-left p-3 rounded">Medical Records</button>
                <button onclick="switchTab('profile')" class="sidebar-link w-full text-left p-3 rounded">Profile</button>
            </nav>
            <div class="p-4"><a href="logout.php" class="text-red-600">Logout</a></div>
        </div>

        <div class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Welcome, <?= $user['full_name'] ?></h2>
                <button onclick="document.getElementById('book-modal').style.display='flex'" class="bg-blue-600 text-white px-4 py-2 rounded">Book Appointment</button>
            </div>

            <div id="tab-dashboard" class="tab-content active">
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded shadow border-l-4 border-blue-500">
                        <h3 class="text-3xl font-bold"><?= $appointments->num_rows ?></h3><p class="text-gray-500">Total Appointments</p>
                    </div>
                    <div class="bg-white p-6 rounded shadow border-l-4 border-green-500">
                        <h3 class="text-3xl font-bold"><?= $prescriptions->num_rows ?></h3><p class="text-gray-500">Prescriptions</p>
                    </div>
                    <div class="bg-white p-6 rounded shadow border-l-4 border-purple-500">
                        <h3 class="text-3xl font-bold"><?= $reports->num_rows ?></h3><p class="text-gray-500">Lab Reports</p>
                    </div>
                </div>
            </div>

            <div id="tab-appointments" class="tab-content">
                <div class="bg-white p-6 rounded shadow">
                    <h3 class="text-xl font-bold mb-4">Your Appointments</h3>
                    <table class="w-full text-left">
                        <tr class="bg-gray-100"><th>Date</th><th>Doctor</th><th>Status</th></tr>
                        <?php foreach($appointments as $appt): ?>
                        <tr class="border-b">
                            <td class="p-3"><?= $appt['appt_date'] ?> <?= $appt['appt_time'] ?></td>
                            <td class="p-3"><?= $appt['doctor_name'] ?></td>
                            <td class="p-3"><?= $appt['status'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div id="tab-medical" class="tab-content">
                <div class="bg-white p-6 rounded shadow mb-6">
                    <h3 class="text-xl font-bold mb-4">Prescriptions</h3>
                    <?php foreach($prescriptions as $p): ?>
                        <div class="border-b p-2"><b><?= $p['medication'] ?></b> (<?= $p['dosage'] ?>)</div>
                    <?php endforeach; ?>
                </div>
                <div class="bg-white p-6 rounded shadow">
                    <h3 class="text-xl font-bold mb-4">Lab Reports</h3>
                    <?php foreach($reports as $r): ?>
                        <div class="border-b p-2 flex justify-between"><span><?= $r['report_name'] ?></span><span class="text-gray-500"><?= $r['report_date'] ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="tab-profile" class="tab-content">
                <div class="bg-white p-6 rounded shadow max-w-lg">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <label class="block mb-2">Full Name</label>
                        <input type="text" value="<?= $user['full_name'] ?>" class="w-full border p-2 mb-4 rounded" disabled>
                        <label class="block mb-2">Phone</label>
                        <input type="text" name="phone" value="<?= $user['phone'] ?>" class="w-full border p-2 mb-4 rounded">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="book-modal" class="modal">
        <div class="bg-white p-6 rounded w-96">
            <h3 class="text-xl font-bold mb-4">Book Appointment</h3>
            <form method="POST">
                <input type="hidden" name="action" value="book_appt">
                <select name="doctor_id" class="w-full border p-2 mb-3" required>
                    <option value="">Select Doctor</option>
                    <?php foreach($doctors as $d): ?><option value="<?= $d['user_id'] ?>"><?= $d['full_name'] ?></option><?php endforeach; ?>
                </select>
                <input type="date" name="date" class="w-full border p-2 mb-3" required>
                <input type="time" name="time" class="w-full border p-2 mb-3" required>
                <textarea name="reason" placeholder="Reason" class="w-full border p-2 mb-3"></textarea>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Book</button>
                <button type="button" onclick="document.getElementById('book-modal').style.display='none'" class="text-red-500 ml-2">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
            event.target.classList.add('active');
        }
    </script>
</body>
</html>