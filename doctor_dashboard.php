<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Doctor') { header("Location: index.php"); exit; }
$doc_id = $_SESSION['id'];
$message = "";

// Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $status = $_POST['status'];
        $appt_id = $_POST['appt_id'];
        $conn->query("UPDATE appointments SET status='$status' WHERE id=$appt_id");
        $message = "Status updated.";
    }
    if (isset($_POST['action']) && $_POST['action'] == 'add_presc') {
        $pat_id = $_POST['patient_id'];
        $med = $_POST['medication'];
        $dos = $_POST['dosage'];
        $conn->query("INSERT INTO prescriptions (user_id, medication, dosage) VALUES ($pat_id, '$med', '$dos')");
        $message = "Prescription added.";
    }
}

$today = date('Y-m-d');
$appts = $conn->query("SELECT a.*, u.full_name, u.patient_id FROM appointments a JOIN users u ON a.user_id = u.user_id WHERE a.doctor_id=$doc_id AND a.appt_date='$today'");
$patients = $conn->query("SELECT DISTINCT u.user_id, u.full_name, u.patient_id FROM appointments a JOIN users u ON a.user_id = u.user_id WHERE a.doctor_id=$doc_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.tab-content{display:none}.tab-content.active{display:block}.sidebar-link.active{background:#ebf8ff;color:#2b6cb0;border-right:4px solid #2b6cb0}</style>
</head>
<body class="bg-gray-50 flex h-screen">
    <?php if($message): ?><div class="fixed top-5 left-1/2 bg-green-600 text-white px-4 py-2 rounded"><?= $message ?></div><?php endif; ?>
    
    <div class="w-64 bg-white shadow-lg flex flex-col p-4">
        <h1 class="text-2xl font-bold text-blue-700 mb-6">Medic1+ <span class="text-sm font-normal text-gray-500">Doctor</span></h1>
        <button onclick="switchTab('dashboard')" class="sidebar-link active text-left p-3 mb-2 rounded">Dashboard</button>
        <button onclick="switchTab('patients')" class="sidebar-link text-left p-3 mb-2 rounded">My Patients</button>
        <a href="logout.php" class="mt-auto text-red-600 p-3">Logout</a>
    </div>

    <div class="flex-1 p-8 overflow-auto">
        <div id="tab-dashboard" class="tab-content active">
            <h2 class="text-2xl font-bold mb-4">Today's Appointments (<?= $today ?>)</h2>
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full text-left">
                    <tr class="bg-gray-100"><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th>Action</th></tr>
                    <?php foreach($appts as $a): ?>
                    <tr class="border-b">
                        <td class="p-3"><?= $a['appt_time'] ?></td>
                        <td class="p-3"><?= $a['full_name'] ?></td>
                        <td class="p-3"><?= $a['reason'] ?></td>
                        <td class="p-3"><?= $a['status'] ?></td>
                        <td class="p-3">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                                <button name="status" value="confirmed" class="text-green-600"><i class="fas fa-check"></i></button>
                                <button name="status" value="cancelled" class="text-red-600 ml-2"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div id="tab-patients" class="tab-content">
            <h2 class="text-2xl font-bold mb-4">My Patients</h2>
            <div class="bg-white rounded shadow p-4">
                <?php foreach($patients as $p): ?>
                <div class="flex justify-between border-b p-3 items-center">
                    <div><b><?= $p['full_name'] ?></b> (<?= $p['patient_id'] ?>)</div>
                    <form method="POST" class="flex gap-2">
                        <input type="hidden" name="action" value="add_presc">
                        <input type="hidden" name="patient_id" value="<?= $p['user_id'] ?>">
                        <input type="text" name="medication" placeholder="Medication" class="border p-1 rounded" required>
                        <input type="text" name="dosage" placeholder="Dosage" class="border p-1 rounded" required>
                        <button class="bg-blue-600 text-white px-3 py-1 rounded">Prescribe</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>function switchTab(id){document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));document.getElementById('tab-'+id).classList.add('active');document.querySelectorAll('.sidebar-link').forEach(l=>l.classList.remove('active'));event.target.classList.add('active');}</script>
</body>
</html>