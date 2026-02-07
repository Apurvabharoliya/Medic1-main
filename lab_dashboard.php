<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Lab Asst') { header("Location: index.php"); exit; }
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload') {
    $uid = $_POST['patient_id']; $name = $_POST['report_name']; $date = $_POST['date']; $sum = $_POST['summary'];
    $conn->query("INSERT INTO lab_reports (user_id, report_name, report_date, summary) VALUES ($uid, '$name', '$date', '$sum')");
    $message = "Report uploaded.";
}
$patients = $conn->query("SELECT user_id, full_name FROM users WHERE role='Patient'");
$reports = $conn->query("SELECT r.*, u.full_name FROM lab_reports r JOIN users u ON r.user_id = u.user_id ORDER BY report_date DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen">
    <?php if($message): ?><div class="fixed top-5 left-1/2 bg-green-600 text-white px-4 py-2 rounded"><?= $message ?></div><?php endif; ?>
    <div class="w-64 bg-white shadow p-4 flex flex-col">
        <h1 class="text-2xl font-bold text-blue-800 mb-8">Lab Panel</h1>
        <a href="logout.php" class="mt-auto text-red-600">Logout</a>
    </div>
    <div class="flex-1 p-8 overflow-auto">
        <h2 class="text-2xl font-bold mb-6">Upload Report</h2>
        <div class="bg-white p-6 rounded shadow mb-8 max-w-lg">
            <form method="POST">
                <input type="hidden" name="action" value="upload">
                <select name="patient_id" class="w-full border p-2 mb-3" required>
                    <option value="">Select Patient</option>
                    <?php foreach($patients as $p): ?><option value="<?= $p['user_id'] ?>"><?= $p['full_name'] ?></option><?php endforeach; ?>
                </select>
                <input type="text" name="report_name" placeholder="Report Name" class="w-full border p-2 mb-3" required>
                <input type="date" name="date" class="w-full border p-2 mb-3" required>
                <textarea name="summary" placeholder="Summary" class="w-full border p-2 mb-3"></textarea>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Upload</button>
            </form>
        </div>
        <h2 class="text-2xl font-bold mb-4">Recent Reports</h2>
        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left">
                <tr class="bg-gray-100"><th>Date</th><th>Patient</th><th>Report</th><th>Summary</th></tr>
                <?php foreach($reports as $r): ?>
                <tr class="border-b"><td class="p-3"><?= $r['report_date'] ?></td><td class="p-3"><?= $r['full_name'] ?></td><td class="p-3"><?= $r['report_name'] ?></td><td class="p-3"><?= $r['summary'] ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>