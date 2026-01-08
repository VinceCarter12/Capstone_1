<?php
/**
 * Excel Parser for Student Import
 * 
 * This class parses Excel files (.xlsx, .xls) containing student data
 * and returns a normalized array ready to send to the Laravel API.
 * 
 * Requirements:
 *   composer require phpoffice/phpspreadsheet
 * 
 * Expected Excel Format:
 *   | Student Number | First Name | Last Name | Email                           |
 *   |----------------|------------|-----------|----------------------------------|
 *   | 2021-00001     | Juan       | Dela Cruz | jdelacruz@iskolarngbayan.pup.edu.ph |
 *   | 2021-00002     | Maria      | Santos    | msantos@iskolarngbayan.pup.edu.ph   |
 * 
 * Column headers are flexible - the parser will try to match common variations.
 */

// Composer autoload (Laravel project vendor directory)
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new RuntimeException('Composer autoload not found at: ' . $autoloadPath . '. Run `composer install` in the Laravel project root.');
}
require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelStudentParser
{
    /**
     * Column name mappings (lowercase variations => standard field name)
     */
    private array $columnMappings = [
        // Student ID variations
        'student_id' => 'student_id',
        'student id' => 'student_id',
        'student number' => 'student_id',
        'student_number' => 'student_id',
        'studentnumber' => 'student_id',
        'id number' => 'student_id',
        'id' => 'student_id',
        
        // First name variations
        'fname' => 'fname',
        'first name' => 'fname',
        'first_name' => 'fname',
        'firstname' => 'fname',
        'given name' => 'fname',
        
        // Last name variations
        'lname' => 'lname',
        'last name' => 'lname',
        'last_name' => 'lname',
        'lastname' => 'lname',
        'surname' => 'lname',
        'family name' => 'lname',
        
        // Full name (will be split)
        'name' => 'full_name',
        'full name' => 'full_name',
        'full_name' => 'full_name',
        'student name' => 'full_name',
        
        // Email variations
        'email' => 'email',
        'email address' => 'email',
        'email_address' => 'email',
        'institutional email' => 'email',
        'pup email' => 'email',
        'webmail' => 'email',
    ];

    /**
     * Parse an Excel file and return student data array
     * 
     * @param string $filePath Path to the Excel file
     * @return array ['success' => bool, 'students' => array, 'errors' => array, 'warnings' => array]
     */
    public function parse(string $filePath): array
    {
        $errors = [];
        $warnings = [];
        $students = [];

        // Check file exists
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'students' => [],
                'errors' => ['File not found: ' . $filePath],
                'warnings' => [],
            ];
        }

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return [
                    'success' => false,
                    'students' => [],
                    'errors' => ['Excel file must have a header row and at least one data row'],
                    'warnings' => [],
                ];
            }

            // Parse header row
            $headerRow = array_map('strtolower', array_map('trim', $rows[0]));
            $columnMap = $this->mapColumns($headerRow);

            // Validate required columns
            $missingColumns = [];
            if (!isset($columnMap['email'])) {
                $missingColumns[] = 'Email';
            }
            if (!isset($columnMap['fname']) && !isset($columnMap['full_name'])) {
                $missingColumns[] = 'First Name (or Full Name)';
            }
            if (!isset($columnMap['lname']) && !isset($columnMap['full_name'])) {
                $missingColumns[] = 'Last Name (or Full Name)';
            }

            if (!empty($missingColumns)) {
                return [
                    'success' => false,
                    'students' => [],
                    'errors' => ['Missing required columns: ' . implode(', ', $missingColumns)],
                    'warnings' => [],
                ];
            }

            // Parse data rows
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNumber = $i + 1;

                // Skip empty rows
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $student = $this->parseRow($row, $columnMap, $rowNumber, $warnings);

                if ($student) {
                    // Validate email
                    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row {$rowNumber}: Invalid email format '{$student['email']}'";
                        continue;
                    }

                    // Validate PUP domain
                    $emailDomain = substr(strrchr($student['email'], '@'), 1);
                    $allowedDomains = ['pup.edu.ph', 'iskolarngbayan.pup.edu.ph'];
                    if (!in_array($emailDomain, $allowedDomains)) {
                        $errors[] = "Row {$rowNumber}: Email must be a PUP institutional email, got '{$student['email']}'";
                        continue;
                    }

                    $students[] = $student;
                }
            }

            return [
                'success' => count($errors) === 0,
                'students' => $students,
                'errors' => $errors,
                'warnings' => $warnings,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'students' => [],
                'errors' => ['Failed to parse Excel file: ' . $e->getMessage()],
                'warnings' => [],
            ];
        }
    }

    /**
     * Map header columns to standard field names
     */
    private function mapColumns(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));
            if (isset($this->columnMappings[$header])) {
                $map[$this->columnMappings[$header]] = $index;
            }
        }

        return $map;
    }

    /**
     * Parse a single data row
     */
    private function parseRow(array $row, array $columnMap, int $rowNumber, array &$warnings): ?array
    {
        $student = [
            'student_id' => null,
            'fname' => '',
            'lname' => '',
            'email' => '',
        ];

        // Get student ID
        if (isset($columnMap['student_id'])) {
            $student['student_id'] = trim($row[$columnMap['student_id']] ?? '');
        }

        // Get email
        if (isset($columnMap['email'])) {
            $student['email'] = strtolower(trim($row[$columnMap['email']] ?? ''));
        }

        // Get names
        if (isset($columnMap['fname']) && isset($columnMap['lname'])) {
            $student['fname'] = trim($row[$columnMap['fname']] ?? '');
            $student['lname'] = trim($row[$columnMap['lname']] ?? '');
        } elseif (isset($columnMap['full_name'])) {
            // Split full name into first and last
            $fullName = trim($row[$columnMap['full_name']] ?? '');
            $nameParts = $this->splitFullName($fullName);
            $student['fname'] = $nameParts['fname'];
            $student['lname'] = $nameParts['lname'];
            
            if (empty($nameParts['lname'])) {
                $warnings[] = "Row {$rowNumber}: Could not determine last name from '{$fullName}'";
            }
        }

        // Skip if missing required data
        if (empty($student['email']) || empty($student['fname'])) {
            $warnings[] = "Row {$rowNumber}: Skipped - missing email or first name";
            return null;
        }

        // Default last name if missing
        if (empty($student['lname'])) {
            $student['lname'] = '-';
            $warnings[] = "Row {$rowNumber}: Last name empty, using '-'";
        }

        return $student;
    }

    /**
     * Split a full name into first and last name
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        
        if (count($parts) === 1) {
            return ['fname' => $parts[0], 'lname' => ''];
        }
        
        // Assume last part is last name, rest is first name
        $lname = array_pop($parts);
        $fname = implode(' ', $parts);
        
        return ['fname' => $fname, 'lname' => $lname];
    }

    /**
     * Check if a row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell ?? ''))) {
                return false;
            }
        }
        return true;
    }
}
