<?php
/* ---------- CONFIG ---------- */
$dbHost = 'localhost';
$dbUser = 'fromzero_santi';
$dbPass = 'Santivoip4321';
$dbName = 'fromzero_morevitility';

/* ---------- CONNECT ---------- */
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    die('DB connection failed: ' . mysqli_connect_error());
}

/* ---------- SANITISE INPUT ---------- */
$username = trim($_GET['username'] ?? '');
if ($username === '') {
    die('Missing ?username= parameter');
}
$eUsername = mysqli_real_escape_string($conn, $username);

/* ---------- FIND ADMIN ID & ROLE ---------- */
$sql  = "SELECT id, role FROM adminuser WHERE username = '$eUsername' LIMIT 1";
$admin = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$admin) {
    die('Username not found');
}
$adminId = (int)$admin['id'];
$role    = $admin['role'];

/* ---------- BUILD DATA QUERY ---------- */
if ($role === 'owner') {
    // Owners see everything
    $dataSql = "SELECT agent, number, insertdate, butclick, butresponse, other
                FROM ownersmsdata
                ORDER BY insertdate DESC";
} else {
    // Get agents assigned to this admin
    $agentRs   = mysqli_query($conn, "SELECT `user` FROM ausers WHERE adminid = $adminId");
    $agentsArr = [];
    while ($row = mysqli_fetch_assoc($agentRs)) {
        $agentsArr[] = "'" . mysqli_real_escape_string($conn, $row['user']) . "'";
    }
    if (empty($agentsArr)) {
        die('No agents assigned to this admin.');
    }
    $agentsCsv = implode(',', $agentsArr);

    $dataSql = "SELECT agent, number, insertdate, butclick, butresponse, other
                FROM adminsmsdata
                WHERE agent IN ($agentsCsv)
                ORDER BY insertdate DESC";
}

/* ---------- FETCH DATA ---------- */
$dataRs = mysqli_query($conn, $dataSql);
if (!$dataRs) {
    die('Query error: ' . mysqli_error($conn));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin SMS Data</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables + Bootstrap 5 styling -->
    <link href="https://cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-3">SMS Data (<?php echo htmlspecialchars($username); ?>)</h3>

    <div class="table-responsive">
        <table id="smsTable" class="table table-striped table-bordered table-sm">
            <thead class="table-dark">
            <tr>
                <th>Agent</th>
                <th>Number</th>
                <th>Insert&nbsp;Date</th>
                <th>Button&nbsp;Click</th>
                <th>Button&nbsp;Response</th>
                <th>Other</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($dataRs)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['agent']); ?></td>
                    <td><?php echo htmlspecialchars($row['number']); ?></td>
                    <td><?php echo htmlspecialchars($row['insertdate']); ?></td>
                    <td><?php echo htmlspecialchars($row['butclick']); ?></td>
                    <td><?php echo htmlspecialchars($row['butresponse']); ?></td>
                    <td><?php echo htmlspecialchars($row['other']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JS: Bootstrap + DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.js"></script>
<script>
    $(function () {
        $('#smsTable').DataTable({
            pageLength: 25,
            order: [[2, 'desc']],
            responsive: true
        });
    });
</script>
</body>
</html>
