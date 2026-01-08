<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentImport;
use App\Models\StudentImportRow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudentImportController extends Controller
{
    /**
     * List all import batches for the authenticated professor/admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only faculty/admin can view imports
        if (!in_array($user->role, ['faculty', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only faculty or admin can view imports.',
            ], 403);
        }

        $imports = StudentImport::where('uploaded_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'imports' => $imports,
        ], 200);
    }

    /**
     * Stage a new import batch from Excel data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Only faculty/admin can upload
        if (!in_array($user->role, ['faculty', 'admin'])) {
            return response()->json([
                'message' => 'Unauthorized. Only faculty or admin can upload student data.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'nullable|string|max:50',
            'students.*.fname' => 'required|string|max:100',
            'students.*.lname' => 'required|string|max:100',
            'students.*.email' => 'required|email|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $students = $request->input('students');
        $allowedDomains = ['pup.edu.ph', 'iskolarngbayan.pup.edu.ph'];

        DB::beginTransaction();

        try {
            // Create the import batch
            $import = StudentImport::create([
                'uploaded_by' => $user->id,
                'file_name' => $request->input('file_name'),
                'total_rows' => count($students),
                'successful_rows' => 0,
                'failed_rows' => 0,
                'status' => 'pending',
            ]);

            $successCount = 0;
            $failCount = 0;

            foreach ($students as $index => $studentData) {
                $rowStatus = 'pending';
                $errorMessage = null;

                // Validate email domain
                $emailDomain = substr(strrchr($studentData['email'], '@'), 1);
                if (!in_array($emailDomain, $allowedDomains)) {
                    $rowStatus = 'failed';
                    $errorMessage = 'Email must be a PUP institutional email (@pup.edu.ph or @iskolarngbayan.pup.edu.ph)';
                    $failCount++;
                }
                // Check for duplicate email in users table
                elseif (User::where('email', $studentData['email'])->exists()) {
                    $rowStatus = 'duplicate';
                    $errorMessage = 'A user with this email already exists';
                    $failCount++;
                }
                // Check for duplicate email in current batch
                elseif (StudentImportRow::where('import_id', $import->id)
                    ->where('email', $studentData['email'])
                    ->exists()) {
                    $rowStatus = 'duplicate';
                    $errorMessage = 'Duplicate email within this upload batch';
                    $failCount++;
                }
                // Check for duplicate email in other pending imports
                elseif (StudentImportRow::whereHas('import', function ($query) {
                    $query->where('status', 'pending');
                })->where('email', $studentData['email'])
                    ->where('status', 'pending')
                    ->exists()) {
                    $rowStatus = 'duplicate';
                    $errorMessage = 'This email is already in another pending import batch';
                    $failCount++;
                } else {
                    $successCount++;
                }

                // Create the import row
                StudentImportRow::create([
                    'import_id' => $import->id,
                    'student_id' => $studentData['student_id'] ?? null,
                    'fname' => $studentData['fname'],
                    'lname' => $studentData['lname'],
                    'email' => $studentData['email'],
                    'status' => $rowStatus,
                    'error_message' => $errorMessage,
                ]);
            }

            // Update batch counts
            $import->update([
                'successful_rows' => $successCount,
                'failed_rows' => $failCount,
            ]);

            DB::commit();

            // Reload with rows
            $import->load('rows');

            return response()->json([
                'message' => 'Import batch staged successfully',
                'import' => $import,
                'summary' => [
                    'total' => count($students),
                    'valid' => $successCount,
                    'invalid' => $failCount,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to stage import batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get details of a specific import batch with all rows.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $import = StudentImport::with('rows')->find($id);

        if (!$import) {
            return response()->json([
                'message' => 'Import batch not found',
            ], 404);
        }

        // Only the uploader or admin can view
        if ($import->uploaded_by !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized to view this import batch',
            ], 403);
        }

        return response()->json([
            'import' => $import,
        ], 200);
    }

    /**
     * Confirm the import batch: create user accounts, activate, send password reset emails.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request, $id)
    {
        $user = $request->user();

        $import = StudentImport::with('rows')->find($id);

        if (!$import) {
            return response()->json([
                'message' => 'Import batch not found',
            ], 404);
        }

        // Only the uploader or admin can confirm
        if ($import->uploaded_by !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized to confirm this import batch',
            ], 403);
        }

        if ($import->status !== 'pending') {
            return response()->json([
                'message' => 'This import batch has already been ' . $import->status,
            ], 400);
        }

        DB::beginTransaction();

        try {
            $createdCount = 0;
            $failedCount = 0;
            $createdUsers = [];

            foreach ($import->rows as $row) {
                // Only process pending rows (skip already failed/duplicate)
                if ($row->status !== 'pending') {
                    continue;
                }

                // Double-check email doesn't exist (race condition protection)
                if (User::where('email', $row->email)->exists()) {
                    $row->update([
                        'status' => 'duplicate',
                        'error_message' => 'A user with this email was created after staging',
                    ]);
                    $failedCount++;
                    continue;
                }

                try {
                    // Create the user account with a random temporary password
                    $tempPassword = Str::random(32);
                    
                    $newUser = User::create([
                        'fname' => $row->fname,
                        'lname' => $row->lname,
                        'email' => $row->email,
                        'password' => Hash::make($tempPassword),
                        'role' => 'student',
                        'student_id' => $row->student_id,
                        'ojt_status' => 'pending',
                        'is_activated' => true,
                        'activated_at' => now(),
                    ]);

                    // Update the row with created user reference
                    $row->update([
                        'status' => 'created',
                        'user_id' => $newUser->id,
                    ]);

                    $createdUsers[] = $newUser;
                    $createdCount++;

                } catch (\Exception $e) {
                    $row->update([
                        'status' => 'failed',
                        'error_message' => 'Failed to create user: ' . $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            }

            // Update import batch status
            $import->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'successful_rows' => $createdCount,
                'failed_rows' => $import->failed_rows + $failedCount,
            ]);

            DB::commit();

            // Send password reset emails to all created users (outside transaction)
            $emailResults = [];
            foreach ($createdUsers as $createdUser) {
                try {
                    $status = Password::sendResetLink(['email' => $createdUser->email]);
                    $emailResults[] = [
                        'email' => $createdUser->email,
                        'success' => $status === Password::RESET_LINK_SENT,
                    ];
                } catch (\Exception $e) {
                    $emailResults[] = [
                        'email' => $createdUser->email,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Reload import with updated rows
            $import->load('rows');

            return response()->json([
                'message' => 'Import batch confirmed successfully',
                'import' => $import,
                'summary' => [
                    'accounts_created' => $createdCount,
                    'failed' => $failedCount,
                    'emails_sent' => count(array_filter($emailResults, fn($r) => $r['success'])),
                ],
                'email_results' => $emailResults,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to confirm import batch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel/delete a pending import batch.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $import = StudentImport::find($id);

        if (!$import) {
            return response()->json([
                'message' => 'Import batch not found',
            ], 404);
        }

        // Only the uploader or admin can cancel
        if ($import->uploaded_by !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized to cancel this import batch',
            ], 403);
        }

        if ($import->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending import batches can be cancelled',
            ], 400);
        }

        $import->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Import batch cancelled successfully',
        ], 200);
    }
}
