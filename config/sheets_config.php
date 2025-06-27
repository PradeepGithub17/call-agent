<?php
return [
    'credentials_path' => __DIR__ . '/credentials/google-sheets-credentials.json',
    'spreadsheet_id' => '11pybVCf-9rkqbjl-e3XL4aTKuP01ZkUzZoskraRzULY', // spreadsheet ID
    'ranges' => [
        'user_data' => 'A:E',
        'verification_log' => 'VerificationLog!A:D',
        'admin_log' => 'AdminLog!A:E',
        'agent_roles' => 'AgentRoles!A:C',
        'settings' => 'Settings!A:B'
    ],
    'cache_minutes' => 5, // Cache data for 5 minutes
    'default_sheet' => 'Sheet1'
];
?>
