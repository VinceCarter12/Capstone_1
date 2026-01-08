<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyGeofence;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get all companies with their geofences.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanies(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $companies = Company::with('geofences')->get();

        return response()->json([
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'address' => $company->address,
                    'phone' => $company->phone,
                    'geofences' => $company->geofences->map(function ($geofence) {
                        return [
                            'id' => $geofence->id,
                            'name' => $geofence->name,
                            'latitude' => $geofence->latitude,
                            'longitude' => $geofence->longitude,
                            'radius_meters' => $geofence->radius_meters,
                            'is_active' => $geofence->is_active,
                        ];
                    }),
                    'student_count' => $company->users()->where('role', 'student')->count(),
                ];
            }),
        ], 200);
    }

    /**
     * Create a new company.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createCompany(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        $company = Company::create($validated);

        return response()->json([
            'message' => 'Company created successfully.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address,
                'phone' => $company->phone,
            ],
        ], 201);
    }

    /**
     * Update a company.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateCompany(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Company updated successfully.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address,
                'phone' => $company->phone,
            ],
        ], 200);
    }

    /**
     * Add a geofence to a company.
     * 
     * @param Request $request
     * @param int $companyId
     * @return JsonResponse
     */
    public function addGeofence(Request $request, int $companyId): JsonResponse
    {
        $this->authorizeAdmin($request);

        $company = Company::findOrFail($companyId);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $geofence = $company->geofences()->create([
            'name' => $validated['name'] ?? 'Main Office',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'] ?? 50,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Geofence added successfully.',
            'geofence' => [
                'id' => $geofence->id,
                'company_id' => $geofence->company_id,
                'name' => $geofence->name,
                'latitude' => $geofence->latitude,
                'longitude' => $geofence->longitude,
                'radius_meters' => $geofence->radius_meters,
                'is_active' => $geofence->is_active,
            ],
        ], 201);
    }

    /**
     * Update a geofence.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateGeofence(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $geofence = CompanyGeofence::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:10|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        $geofence->update($validated);

        return response()->json([
            'message' => 'Geofence updated successfully.',
            'geofence' => [
                'id' => $geofence->id,
                'company_id' => $geofence->company_id,
                'name' => $geofence->name,
                'latitude' => $geofence->latitude,
                'longitude' => $geofence->longitude,
                'radius_meters' => $geofence->radius_meters,
                'is_active' => $geofence->is_active,
            ],
        ], 200);
    }

    /**
     * Delete a geofence.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deleteGeofence(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $geofence = CompanyGeofence::findOrFail($id);
        $geofence->delete();

        return response()->json([
            'message' => 'Geofence deleted successfully.',
        ], 200);
    }

    /**
     * Assign a student to a company.
     * 
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignUserToCompany(Request $request, int $userId): JsonResponse
    {
        $this->authorizeAdmin($request);

        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user->update([
            'company_id' => $validated['company_id'],
        ]);

        $company = Company::find($validated['company_id']);

        return response()->json([
            'message' => 'User assigned to company successfully.',
            'user' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
            ],
        ], 200);
    }

    /**
     * Get all students with their company assignments.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudents(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $students = User::where('role', 'student')
            ->with('company')
            ->get();

        return response()->json([
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'fname' => $student->fname,
                    'lname' => $student->lname,
                    'email' => $student->email,
                    'student_id' => $student->student_id,
                    'ojt_status' => $student->ojt_status,
                    'company' => $student->company ? [
                        'id' => $student->company->id,
                        'name' => $student->company->name,
                    ] : null,
                ];
            }),
        ], 200);
    }

    /**
     * Check if the current user is authorized as admin/faculty.
     * 
     * @param Request $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();

        if (!$user || $user->role !== 'faculty') {
            abort(403, 'Unauthorized. Only faculty members can access this resource.');
        }
    }
}

