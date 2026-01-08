<?php
/**
 * InternTrack API Client
 * 
 * This class handles all communication between the PHP website
 * and the Laravel API. The Laravel API is the single source of
 * truth for student accounts.
 * 
 * Usage:
 *   $api = new InternTrackApiClient('https://your-laravel-api.com/api');
 *   $api->setToken($professorToken);
 *   $result = $api->stageStudentImport('students.xlsx', $studentsArray);
 */

class InternTrackApiClient
{
    private string $baseUrl;
    private ?string $token = null;
    private int $timeout = 30;

    /**
     * Constructor
     * 
     * @param string $baseUrl The Laravel API base URL (e.g., 'http://localhost:8000/api')
     */
    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Set the authentication token (Sanctum bearer token)
     * 
     * @param string $token The bearer token from professor/admin login
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Login a professor/admin and get their API token
     * 
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'token' => string|null, 'user' => array|null, 'error' => string|null]
     */
    public function login(string $email, string $password): array
    {
        $response = $this->request('POST', '/login', [
            'email' => $email,
            'password' => $password,
        ], false);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->token = $response['data']['token'];
        }

        return [
            'success' => $response['success'],
            'token' => $response['data']['token'] ?? null,
            'user' => $response['data']['user'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Stage a student import batch
     * 
     * Sends parsed Excel data to Laravel API for validation and staging.
     * Does NOT create user accounts yet - just validates and stores for preview.
     * 
     * @param string $fileName Original Excel filename
     * @param array $students Array of student data [['student_id' => '', 'fname' => '', 'lname' => '', 'email' => ''], ...]
     * @return array ['success' => bool, 'import' => array|null, 'summary' => array|null, 'error' => string|null]
     */
    public function stageStudentImport(string $fileName, array $students): array
    {
        $response = $this->request('POST', '/student-imports', [
            'file_name' => $fileName,
            'students' => $students,
        ]);

        return [
            'success' => $response['success'],
            'import' => $response['data']['import'] ?? null,
            'summary' => $response['data']['summary'] ?? null,
            'error' => $response['error'] ?? null,
            'errors' => $response['data']['errors'] ?? null,
        ];
    }

    /**
     * Get all import batches for the logged-in professor
     * 
     * @return array ['success' => bool, 'imports' => array|null, 'error' => string|null]
     */
    public function getImports(): array
    {
        $response = $this->request('GET', '/student-imports');

        return [
            'success' => $response['success'],
            'imports' => $response['data']['imports'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Get details of a specific import batch with all rows
     * 
     * @param int $importId
     * @return array ['success' => bool, 'import' => array|null, 'error' => string|null]
     */
    public function getImport(int $importId): array
    {
        $response = $this->request('GET', "/student-imports/{$importId}");

        return [
            'success' => $response['success'],
            'import' => $response['data']['import'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Confirm an import batch
     * 
     * This creates the actual user accounts in Laravel, activates them,
     * and sends password reset emails to all students.
     * 
     * @param int $importId
     * @return array ['success' => bool, 'import' => array|null, 'summary' => array|null, 'email_results' => array|null, 'error' => string|null]
     */
    public function confirmImport(int $importId): array
    {
        $response = $this->request('POST', "/student-imports/{$importId}/confirm");

        return [
            'success' => $response['success'],
            'import' => $response['data']['import'] ?? null,
            'summary' => $response['data']['summary'] ?? null,
            'email_results' => $response['data']['email_results'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Cancel a pending import batch
     * 
     * @param int $importId
     * @return array ['success' => bool, 'message' => string|null, 'error' => string|null]
     */
    public function cancelImport(int $importId): array
    {
        $response = $this->request('POST', "/student-imports/{$importId}/cancel");

        return [
            'success' => $response['success'],
            'message' => $response['data']['message'] ?? null,
            'error' => $response['error'] ?? null,
        ];
    }

    /**
     * Make an HTTP request to the Laravel API
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint (e.g., '/student-imports')
     * @param array $data Request body data
     * @param bool $requiresAuth Whether to include the bearer token
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'status_code' => int]
     */
    private function request(string $method, string $endpoint, array $data = [], bool $requiresAuth = true): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($requiresAuth) {
            if (!$this->token) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'No authentication token set. Call setToken() or login() first.',
                    'status_code' => 0,
                ];
            }
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'cURL Error: ' . $curlError,
                'status_code' => $statusCode,
            ];
        }

        $responseData = json_decode($response, true);

        $success = $statusCode >= 200 && $statusCode < 300;

        return [
            'success' => $success,
            'data' => $responseData,
            'error' => $success ? null : ($responseData['message'] ?? 'Unknown error'),
            'status_code' => $statusCode,
        ];
    }
}
