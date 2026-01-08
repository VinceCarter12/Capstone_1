<?php
/**
 * Complete Example: Student Excel Upload Flow
 * 
 * This file demonstrates the complete flow for uploading students via Excel:
 * 1. Professor logs in to website
 * 2. Professor uploads Excel file
 * 3. Website parses Excel and sends to Laravel API
 * 4. Website displays preview with validation results
 * 5. Professor confirms the import
 * 6. Laravel creates accounts and sends password reset emails
 * 
 * IMPORTANT: This is example code. In production:
 * - Add proper error handling
 * - Add CSRF protection
 * - Store tokens securely (session, not in code)
 * - Add proper file upload validation
 */

require_once 'InternTrackApiClient.php';
require_once 'ExcelStudentParser.php';

// ============================================
// Configuration
// ============================================
$apiBaseUrl = 'http://localhost:8000/api';  // Change to your Laravel API URL

// ============================================
// Example 1: Professor Login
// ============================================
function exampleLogin(): ?string
{
    global $apiBaseUrl;
    
    $api = new InternTrackApiClient($apiBaseUrl);
    
    $result = $api->login('professor@pup.edu.ph', 'password123');
    
    if ($result['success']) {
        echo "✓ Login successful!\n";
        echo "  Token: " . substr($result['token'], 0, 20) . "...\n";
        echo "  User: {$result['user']['fname']} {$result['user']['lname']}\n";
        return $result['token'];
    } else {
        echo "✗ Login failed: {$result['error']}\n";
        return null;
    }
}

// ============================================
// Example 2: Parse Excel File
// ============================================
function exampleParseExcel(string $filePath): ?array
{
    $parser = new ExcelStudentParser();
    $result = $parser->parse($filePath);
    
    echo "\n--- Excel Parse Results ---\n";
    echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
    echo "Students found: " . count($result['students']) . "\n";
    
    if (!empty($result['errors'])) {
        echo "Errors:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    
    if (!empty($result['warnings'])) {
        echo "Warnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - {$warning}\n";
        }
    }
    
    if ($result['success'] && !empty($result['students'])) {
        echo "\nParsed students:\n";
        foreach ($result['students'] as $index => $student) {
            echo "  " . ($index + 1) . ". {$student['fname']} {$student['lname']} ({$student['email']})\n";
        }
        return $result['students'];
    }
    
    return null;
}

// ============================================
// Example 3: Stage Import (Send to Laravel API)
// ============================================
function exampleStageImport(string $token, string $fileName, array $students): ?int
{
    global $apiBaseUrl;
    
    $api = new InternTrackApiClient($apiBaseUrl);
    $api->setToken($token);
    
    $result = $api->stageStudentImport($fileName, $students);
    
    echo "\n--- Stage Import Results ---\n";
    
    if ($result['success']) {
        echo "✓ Import staged successfully!\n";
        echo "  Import ID: {$result['import']['id']}\n";
        echo "  Status: {$result['import']['status']}\n";
        echo "  Summary:\n";
        echo "    - Total rows: {$result['summary']['total']}\n";
        echo "    - Valid: {$result['summary']['valid']}\n";
        echo "    - Invalid: {$result['summary']['invalid']}\n";
        
        // Show row details
        if (!empty($result['import']['rows'])) {
            echo "\n  Row details:\n";
            foreach ($result['import']['rows'] as $row) {
                $status = $row['status'] === 'pending' ? '✓' : '✗';
                echo "    {$status} {$row['fname']} {$row['lname']} ({$row['email']})";
                if ($row['error_message']) {
                    echo " - {$row['error_message']}";
                }
                echo "\n";
            }
        }
        
        return $result['import']['id'];
    } else {
        echo "✗ Failed to stage import: {$result['error']}\n";
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $field => $messages) {
                foreach ($messages as $message) {
                    echo "  - {$field}: {$message}\n";
                }
            }
        }
        return null;
    }
}

// ============================================
// Example 4: Confirm Import (Create Accounts)
// ============================================
function exampleConfirmImport(string $token, int $importId): bool
{
    global $apiBaseUrl;
    
    $api = new InternTrackApiClient($apiBaseUrl);
    $api->setToken($token);
    
    $result = $api->confirmImport($importId);
    
    echo "\n--- Confirm Import Results ---\n";
    
    if ($result['success']) {
        echo "✓ Import confirmed successfully!\n";
        echo "  Accounts created: {$result['summary']['accounts_created']}\n";
        echo "  Failed: {$result['summary']['failed']}\n";
        echo "  Emails sent: {$result['summary']['emails_sent']}\n";
        
        // Show email results
        if (!empty($result['email_results'])) {
            echo "\n  Email results:\n";
            foreach ($result['email_results'] as $emailResult) {
                $status = $emailResult['success'] ? '✓' : '✗';
                echo "    {$status} {$emailResult['email']}";
                if (!$emailResult['success'] && isset($emailResult['error'])) {
                    echo " - {$emailResult['error']}";
                }
                echo "\n";
            }
        }
        
        return true;
    } else {
        echo "✗ Failed to confirm import: {$result['error']}\n";
        return false;
    }
}

// ============================================
// Example 5: Complete Flow
// ============================================
function runCompleteExample(string $excelFilePath)
{
    echo "========================================\n";
    echo "InternTrack Student Import Example\n";
    echo "========================================\n";
    
    // Step 1: Login
    echo "\n[Step 1] Professor Login\n";
    $token = exampleLogin();
    if (!$token) {
        echo "\nFlow stopped: Login failed\n";
        return;
    }
    
    // Step 2: Parse Excel
    echo "\n[Step 2] Parse Excel File\n";
    $students = exampleParseExcel($excelFilePath);
    if (!$students) {
        echo "\nFlow stopped: No valid students found\n";
        return;
    }
    
    // Step 3: Stage Import
    echo "\n[Step 3] Stage Import (Send to API)\n";
    $importId = exampleStageImport($token, basename($excelFilePath), $students);
    if (!$importId) {
        echo "\nFlow stopped: Failed to stage import\n";
        return;
    }
    
    // Step 4: Ask for confirmation (in real app, show preview page)
    echo "\n[Step 4] Awaiting Confirmation...\n";
    echo "In a real application, you would show a preview page here.\n";
    echo "The professor reviews the data and clicks 'Confirm'.\n";
    
    // Step 5: Confirm Import
    echo "\n[Step 5] Confirm Import (Create Accounts)\n";
    $confirmed = exampleConfirmImport($token, $importId);
    
    if ($confirmed) {
        echo "\n========================================\n";
        echo "✓ COMPLETE! Students can now:\n";
        echo "  1. Check their email for password reset link\n";
        echo "  2. Set their password\n";
        echo "  3. Login to the Flutter mobile app\n";
        echo "========================================\n";
    }
}

// ============================================
// Run the example (uncomment to test)
// ============================================
// runCompleteExample('/path/to/students.xlsx');

// ============================================
// Simple test with hardcoded data (no Excel file needed)
// ============================================
function runSimpleTest()
{
    global $apiBaseUrl;
    
    echo "========================================\n";
    echo "Simple Test (No Excel File)\n";
    echo "========================================\n";
    
    // Hardcoded test students
    $students = [
        [
            'student_id' => '2021-00001',
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'email' => 'jdelacruz@iskolarngbayan.pup.edu.ph',
        ],
        [
            'student_id' => '2021-00002',
            'fname' => 'Maria',
            'lname' => 'Santos',
            'email' => 'msantos@iskolarngbayan.pup.edu.ph',
        ],
    ];
    
    $api = new InternTrackApiClient($apiBaseUrl);
    
    // You need a valid professor token here
    // Get one by calling the login API first
    $token = 'YOUR_PROFESSOR_TOKEN_HERE';
    $api->setToken($token);
    
    // Stage the import
    $result = $api->stageStudentImport('test_upload.xlsx', $students);
    print_r($result);
    
    // If successful, confirm it
    if ($result['success'] && isset($result['import']['id'])) {
        $confirmResult = $api->confirmImport($result['import']['id']);
        print_r($confirmResult);
    }
}

// runSimpleTest();
