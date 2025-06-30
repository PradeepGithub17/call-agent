<?php
require_once 'config/db_config.php';

/* * Function to send data to Telegram Bot
 * 
 * @param array $postData Data to be sent to the bot
 * @return array Response from the bot or error information
 */

function sendDataToTelegramBot($postData, $url = TELEGRAM_BOT_URL)
{
    $targetUrl = $url;

    try {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $targetUrl,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ActivityTracker/1.0'
            ]
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('HTTP Error: ' . $httpCode . ' - Response: ' . $result);
        }

        $response = json_decode($result, true);

        return [
            'success' => true,
            'response' => $response,
            'message' => 'Data sent successfully',
            'sent_data' => $postData,
            'http_code' => $httpCode
        ];
    } catch (Exception $e) {
        error_log('Data sending failed: ' . $e->getMessage());

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'sent_data' => $postData
        ];
    }
}

/**
 * Function to print and exit with formatted data
 *
 * @param mixed $data Data to be printed
 */

function dd($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit;
}

/**
 * Function to store or update reference tracking data
 *
 * @param string $reference Reference identifier
 * @param string $department Department name
 * @param string|int $agent Agent identifier (username or ID)
 * @param float $balance Balance amount
 * @return bool|array Success status or error information
 */
function storeReferenceTracking($reference, $department, $agent, $balance = 0.00)
{
    $conn = getDbConnection();

    try {
        // Sanitize inputs
        $reference = mysqli_real_escape_string($conn, $reference);
        $department = mysqli_real_escape_string($conn, $department);
        $balance = floatval($balance);


        // Agent is a username, get the ID
        $agentName = mysqli_real_escape_string($conn, $agent);
        $agentQuery = "SELECT id FROM ausers WHERE user = '$agentName' LIMIT 1";
        $agentResult = mysqli_query($conn, $agentQuery);
        if ($agentResult && mysqli_num_rows($agentResult) > 0) {
            $agentRow = mysqli_fetch_assoc($agentResult);
            $agentId = $agentRow['id'];
        }

        // Check if reference already exists
        $checkSql = "SELECT id FROM reference_tracking WHERE reference = '$reference'";
        $checkResult = mysqli_query($conn, $checkSql);

        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing reference
            $row = mysqli_fetch_assoc($checkResult);
            $id = $row['id'];

            $updateSql = "UPDATE reference_tracking 
                         SET department = '$department', 
                             agent = " . ($agentId ? $agentId : "NULL") . ", 
                             balance = $balance,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = $id";

            if (mysqli_query($conn, $updateSql)) {
                closeDbConnection($conn);
                return [
                    'success' => true,
                    'message' => 'Reference tracking data updated successfully',
                    'id' => $id,
                    'reference' => $reference,
                    'operation' => 'update'
                ];
            } else {
                throw new Exception("Update error: " . mysqli_error($conn));
            }
        } else {
            // Insert new reference
            $insertSql = "INSERT INTO reference_tracking (reference, department, agent, balance)
                         VALUES ('$reference', '$department', " . ($agentId ? $agentId : "NULL") . ", $balance)";

            if (mysqli_query($conn, $insertSql)) {
                $id = mysqli_insert_id($conn);
                closeDbConnection($conn);
                return [
                    'success' => true,
                    'message' => 'Reference tracking data inserted successfully',
                    'id' => $id,
                    'reference' => $reference,
                    'operation' => 'insert'
                ];
            } else {
                throw new Exception("Insert error: " . mysqli_error($conn));
            }
        }
    } catch (Exception $e) {
        error_log('Reference tracking error: ' . $e->getMessage());
        closeDbConnection($conn);
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'reference' => $reference
        ];
    }
}

/**
 * Function to get reference tracking data
 *
 * @param string $reference Reference identifier
 * @return array|bool Reference data or false if not found
 */
function getReferenceTracking($reference)
{
    $conn = getDbConnection();

    // Sanitize input
    $reference = mysqli_real_escape_string($conn, $reference);

    // Join with ausers table to get agent name
    $sql = "SELECT rt.*,au.role, au.user as agent_name 
            FROM reference_tracking rt
            LEFT JOIN ausers au ON rt.agent = au.id
            WHERE rt.reference = '$reference'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        closeDbConnection($conn);
        return $data;
    }

    closeDbConnection($conn);
    return false;
}

/**
 * Function to update balance for a reference
 *
 * @param string $reference Reference identifier
 * @param float $balance New balance amount
 * @return bool|array Success status or error information
 */
function updateReferenceBalance($reference, $balance)
{
    $conn = getDbConnection();

    try {
        // Sanitize inputs
        $reference = mysqli_real_escape_string($conn, $reference);
        $balance = floatval($balance);

        $sql = "UPDATE reference_tracking 
               SET balance = $balance,
                   updated_at = CURRENT_TIMESTAMP
               WHERE reference = '$reference'";

        if (mysqli_query($conn, $sql)) {
            $affectedRows = mysqli_affected_rows($conn);
            closeDbConnection($conn);

            if ($affectedRows > 0) {
                return [
                    'success' => true,
                    'message' => 'Reference balance updated successfully',
                    'reference' => $reference,
                    'balance' => $balance
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Reference not found',
                    'reference' => $reference
                ];
            }
        } else {
            throw new Exception("Update error: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        error_log('Reference balance update error: ' . $e->getMessage());
        closeDbConnection($conn);
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'reference' => $reference
        ];
    }
}

/**
 * Function to delete reference tracking data
 *
 * @param string $reference Reference identifier
 * @return bool|array Success status or error information
 */
function deleteReferenceTracking($reference)
{
    $conn = getDbConnection();

    try {
        // Sanitize input
        $reference = mysqli_real_escape_string($conn, $reference);

        $sql = "DELETE FROM reference_tracking WHERE reference = '$reference'";

        if (mysqli_query($conn, $sql)) {
            $affectedRows = mysqli_affected_rows($conn);
            closeDbConnection($conn);

            if ($affectedRows > 0) {
                return [
                    'success' => true,
                    'message' => 'Reference tracking data deleted successfully',
                    'reference' => $reference
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Reference not found',
                    'reference' => $reference
                ];
            }
        } else {
            throw new Exception("Delete error: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        error_log('Reference tracking deletion error: ' . $e->getMessage());
        closeDbConnection($conn);
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'reference' => $reference
        ];
    }
}


/**
 * Function to get all ausers
 *
 * @return array List of ausers
 */
function getAllAusers($user = null)
{
    $conn = getDbConnection(); 

    // If a specific user is provided, filter by that user
    if ($user) {
        $user = mysqli_real_escape_string($conn, $user);
        $sql = "SELECT * FROM ausers WHERE user = '$user' LIMIT 1";
    } else {
        // Otherwise, get all ausers
        $sql = "SELECT id, user, role FROM ausers";
    }

    $result = mysqli_query($conn, $sql);
    $ausers = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ausers[] = $row;
        }
    }

    closeDbConnection($conn);
    return $ausers;
}

/**
 * Function to store or update user info data
 *
 * @param string $reference Reference identifier
 * @param array $data User info data (last_login, balance, tokens, exchanges, cold_wallets, notes)
 * @return array Success status and operation information
 */
function storeUserInfo($reference, $data)
{
    
    $conn = getDbConnection();

    try {
        // Sanitize inputs
        $reference = mysqli_real_escape_string($conn, $reference);
        $lastLogin = isset($data['last_login']) ? mysqli_real_escape_string($conn, $data['last_login']) : null;
        $balance = isset($data['balance']) ? floatval($data['balance']) : null;
        $tokens = isset($data['tokens']) ? intval($data['tokens']) : null;
        $exchanges = isset($data['exchanges']) ? mysqli_real_escape_string($conn, $data['exchanges']) : null;
        $coldWallets = isset($data['cold_wallets']) ? mysqli_real_escape_string($conn, $data['cold_wallets']) : null;
        $notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : null;

        // Check if reference already exists in user_info
        $checkSql = "SELECT id FROM user_info WHERE reference = '$reference'";
        $checkResult = mysqli_query($conn, $checkSql);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            // Update existing record
            $row = mysqli_fetch_assoc($checkResult);
            $id = $row['id'];

            $updateSql = "UPDATE user_info SET 
                         last_login = " . ($lastLogin ? "'$lastLogin'" : "NULL") . ",
                         balance = " . ($balance !== null ? $balance : "NULL") . ",
                         tokens = " . ($tokens !== null ? $tokens : "NULL") . ",
                         exchanges = " . ($exchanges ? "'$exchanges'" : "NULL") . ",
                         cold_wallets = " . ($coldWallets ? "'$coldWallets'" : "NULL") . ",
                         notes = " . ($notes ? "'$notes'" : "NULL") . ",
                         updated_at = CURRENT_TIMESTAMP
                         WHERE id = $id";

            if (mysqli_query($conn, $updateSql)) {
                closeDbConnection($conn);
                
                // Also update reference_tracking balance if provided
                if ($balance !== null) {
                    updateReferenceBalance($reference, $balance);
                }
                
                return [
                    'success' => true,
                    'message' => 'User info updated successfully',
                    'id' => $id,
                    'reference' => $reference,
                    'operation' => 'update'
                ];
            } else {
                throw new Exception("Update error: " . mysqli_error($conn));
            }
        } else {
            // Insert new record
            $insertSql = "INSERT INTO user_info (
                          reference, 
                          last_login, 
                          balance, 
                          tokens, 
                          exchanges, 
                          cold_wallets, 
                          notes
                        ) VALUES (
                          '$reference', 
                          " . ($lastLogin ? "'$lastLogin'" : "NULL") . ",
                          " . ($balance !== null ? $balance : "NULL") . ",
                          " . ($tokens !== null ? $tokens : "NULL") . ",
                          " . ($exchanges ? "'$exchanges'" : "NULL") . ",
                          " . ($coldWallets ? "'$coldWallets'" : "NULL") . ",
                          " . ($notes ? "'$notes'" : "NULL") . "
                        )";

            if (mysqli_query($conn, $insertSql)) {
                $id = mysqli_insert_id($conn);
                closeDbConnection($conn);
                
                // Also update reference_tracking balance if provided
                if ($balance !== null) {
                    updateReferenceBalance($reference, $balance);
                }
                
                return [
                    'success' => true,
                    'message' => 'User info created successfully',
                    'id' => $id,
                    'reference' => $reference,
                    'operation' => 'insert'
                ];
            } else {
                throw new Exception("Insert error: " . mysqli_error($conn));
            }
        }
    } catch (Exception $e) {
        error_log('User info storage error: ' . $e->getMessage());
        closeDbConnection($conn);
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'reference' => $reference
        ];
    }
}

/**
 * Function to get user info data
 *
 * @param string $reference Reference identifier
 * @return array|bool User info data or false if not found
 */
function getUserInfo($reference)
{
    $conn = getDbConnection();

    // Sanitize input
    $reference = mysqli_real_escape_string($conn, $reference);

    $sql = "SELECT * FROM user_info WHERE reference = '$reference'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        closeDbConnection($conn);
        return $data;
    }

    closeDbConnection($conn);
    return false;
}