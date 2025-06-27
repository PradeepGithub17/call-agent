<?php
require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsHelper {
    private $client;
    private $service;
    private $credentialsPath;
    private $spreadsheetId;
    private $range;
    
    public function __construct() {

        $config = require 'config/sheets_config.php';
        $this->credentialsPath = $config['credentials_path'];
        $this->spreadsheetId = $config['spreadsheet_id'];
        $this->range = $config['ranges']['user_data'] ?? 'A:E'; 
        $this->initializeClient();
    }
    
    private function initializeClient() {
        try {
            $this->client = new Client();
            $this->client->setAuthConfig($this->credentialsPath);
            $this->client->addScope(Sheets::SPREADSHEETS_READONLY);
            $this->client->setAccessType('offline');
            $this->service = new Sheets($this->client);
        } catch (Exception $e) {
            error_log("Google Sheets Client Init Error: " . $e->getMessage());
            throw new Exception("Failed to initialize Google Sheets client: " . $e->getMessage());
        }
    }
    
    /**
     * Read data from Google Sheet with caching
     */
    public function readSheet($spreadsheetId = null) {
        if (!$this->spreadsheetId) {
            throw new Exception("Spreadsheet ID is required");
        }
        
        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $this->range);
            $values = $response->getValues();
            
            if (empty($values)) {
                return [];
            }
            
            return $values;
        } catch (Exception $e) {
            error_log("Google Sheets Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read sheet with headers (first row as keys)
     */
    public function readSheetWithHeaders() {
        $data = $this->readSheet($this->range, $this->spreadsheetId);
        
        if (!$data || count($data) < 2) {
            return [];
        }
        
        $headers = array_shift($data);
        $result = [];
        
        foreach ($data as $row) {
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? $row[$index] : '';
            }
            $result[] = $rowData;
        }
        
        return $result;
    }
    
    /**
     * Search for specific data in sheet
     */
    public function searchInSheet($searchColumn, $searchValue) {
        $data = $this->readSheetWithHeaders();

        $results = [];
        foreach ($data as $row) {
            if (isset($row[$searchColumn]) && strcasecmp($row[$searchColumn], $searchValue) === 0) {
                $results[] = $row;
            }
        }
        
        return $results;
    }
    
    /**
     * Get specific cell value
     */
    public function getCellValue($cellAddress, $spreadsheetId = null) {
        $data = $this->readSheet($cellAddress, $spreadsheetId);
        
        if ($data && isset($data[0][0])) {
            return $data[0][0];
        }
        
        return null;
    }
    
    /**
     * Get sheet metadata
     */
    public function getSheetInfo() {
        
        try {
            $response = $this->service->spreadsheets->get($this->spreadsheetId);
            return [
                'title' => $response->getProperties()->getTitle(),
                'sheets' => array_map(function($sheet) {
                    return [
                        'title' => $sheet->getProperties()->getTitle(),
                        'sheetId' => $sheet->getProperties()->getSheetId(),
                        'rowCount' => $sheet->getProperties()->getGridProperties()->getRowCount(),
                        'columnCount' => $sheet->getProperties()->getGridProperties()->getColumnCount()
                    ];
                }, $response->getSheets())
            ];
        } catch (Exception $e) {
            error_log("Google Sheets Info Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Batch read multiple ranges
     */
    public function batchRead($ranges) {
        
        try {
            $response = $this->service->spreadsheets_values->batchGet($this->spreadsheetId, [
                'ranges' => $ranges
            ]);
            
            $result = [];
            foreach ($response->getValueRanges() as $index => $valueRange) {
                $result[$ranges[$index]] = $valueRange->getValues() ?: [];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Google Sheets Batch Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache sheet data to file (simple file-based caching)
     */
    public function readSheetCached($cacheMinutes = 5) {
        $cacheFile = sys_get_temp_dir() . '/sheets_cache_' . md5($this->range . ($this->spreadsheetId)) . '.json';
        
        // Check if cache exists and is fresh
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < ($cacheMinutes * 60)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Fetch fresh data
        $data = $this->readSheet($this->range, $this->spreadsheetId);
        
        if ($data !== false) {
            file_put_contents($cacheFile, json_encode($data));
        }
        
        return $data;
    }
}
?>
