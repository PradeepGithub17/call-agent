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
$sql  = "SELECT id, role, department FROM adminuser WHERE username = '$eUsername' LIMIT 1";
$admin = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$admin) {
    die('Username not found');
}
$adminId = (int)$admin['id'];
$role    = $admin['role'];
$admindepart = $admin['department'];
$isOwner     = ($role === 'owner');

/* === Build SQL based on role === */
if ($isOwner) {
    // Owner sees everything and pulls department via join
    $dataSql = "
        SELECT  o.agent,
                o.number,
                o.insertdate,
                o.butclick,
                o.butresponse,
                COALESCE(u.department,'') AS department,
                o.other
        FROM ownersmsdata AS o
        LEFT JOIN ausers AS a ON a.user = o.agent
        LEFT JOIN adminuser AS u ON u.id = a.adminid
        ORDER BY o.insertdate DESC
    ";
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

    $dataSql = "SELECT agent, number, insertdate, butclick, butresponse, other ,'$admindepart' AS department
                FROM adminsmsdata
                WHERE agent IN ($agentsCsv)
                ORDER BY insertdate DESC";
}

/* ---------- FETCH DATA ---------- */
$dataRs = mysqli_query($conn, $dataSql);
if (!$dataRs) {
    die('Query error: ' . mysqli_error($conn));
}
$isOwner = ($role === 'owner');
?>
<!doctype html>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin SMS Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
   <!-- <h3 class="mb-0">SMS Data (<= htmlspecialchars($username) ?>)</h3> -->
    <h3 class="mb-0">Clicks</h3>
    <p class="text-muted mb-3">
        Role: <strong><?= htmlspecialchars($role) ?></strong>
        <?= $isOwner ? " | Admin Dept: <strong>" . htmlspecialchars($admindepart) . "</strong>" : '' ?>
    </p>

    <div class="table-responsive">
        <table id="smsTable" class="table table-striped table-bordered table-sm text-start">
            <thead class="table-dark text-start">
            <tr>
                <th>Timestamp</th>
                <?php if ($isOwner): ?>
                    <th>Department</th>
                <?php endif; ?>
                <th class="text-start">Agent</th>
                <th class="text-start">Reference</th>
                <th class="text-start">Click</th>
                <th class="text-start">Response</th>
                <th class="text-start">Other</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($dataRs)): ?>
                <tr>
                    <td><?= date('H:i:s d-m-Y', strtotime($row['insertdate'])) ?></td>
                    <?php if ($isOwner): ?>
                        <td><?= htmlspecialchars($row['department']) ?></td>
                    <?php endif; ?>
                    <td class="text-start"><?= htmlspecialchars($row['agent']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['number']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['butclick']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['butresponse']) ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['other']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.js"></script>
<script>
    $(function () {
        $('#smsTable').DataTable({
            pageLength: 100,
            order: [[0, 'desc']], // Timestamp column
            responsive: true
        });
    });
</script>
<script>
    setInterval(() => {
        location.reload();
    }, 10000); // 10,000 ms = 10 seconds
</script>
</body>
</html>
