<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlacementRequest;
use App\Models\Company;
use App\Models\CompanyGeofence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlacementController extends Controller
{
    /**
     * Submit a new placement request.
     * 
     * POST /api/placement/submit
     */
    public function submit(Request $request)
    {
        $user = $request->user();
        
        // Check if user already has a pending or approved request
        $existingRequest = PlacementRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'reviewing', 'approved'])
            ->first();
            
        if ($existingRequest) {
            if ($existingRequest->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an approved placement.',
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending placement request.',
                'request' => $existingRequest,
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:500',
            'supervisor_name' => 'required|string|max:255',
            'supervisor_contact' => 'required|string|max:100',
            'supervisor_email' => 'nullable|email|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'proof_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Upload proof document to S3
        $proofUrl = null;
        $proofType = null;
        if ($request->hasFile('proof_document')) {
            $file = $request->file('proof_document');
            $extension = $file->getClientOriginalExtension();
            $filename = 'placement-docs/' . $user->id . '/' . Str::uuid() . '.' . $extension;
            
            // Store to S3
            Storage::disk('s3')->put($filename, file_get_contents($file));
            $proofUrl = Storage::url($filename);
            $proofType = $extension;
        }
        
        // Create placement request
        $placementRequest = PlacementRequest::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'supervisor_name' => $request->supervisor_name,
            'supervisor_contact' => $request->supervisor_contact,
            'supervisor_email' => $request->supervisor_email,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'proof_document_url' => $proofUrl,
            'proof_document_type' => $proofType,
            'status' => 'pending',
        ]);
        
        // Update user verification status
        $user->update(['verification_status' => 'pending']);
        
        return response()->json([
            'success' => true,
            'message' => 'Placement request submitted successfully!',
            'request' => $placementRequest,
        ], 201);
    }
    
    /**
     * Get current user's placement request status.
     * 
     * GET /api/placement/status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        $placementRequest = PlacementRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$placementRequest) {
            return response()->json([
                'success' => true,
                'has_request' => false,
                'status' => null,
                'message' => 'No placement request found. Please submit your OJT application.',
            ]);
        }
        
        // Get status tracker steps
        $steps = $this->getStatusSteps($placementRequest->status);
        
        return response()->json([
            'success' => true,
            'has_request' => true,
            'status' => $placementRequest->status,
            'request' => $placementRequest,
            'steps' => $steps,
            'can_clock_in' => $placementRequest->status === 'approved',
            'rejection_reason' => $placementRequest->rejection_reason,
        ]);
    }
    
    /**
     * Get status tracker steps.
     */
    private function getStatusSteps(string $status): array
    {
        $steps = [
            [
                'id' => 1,
                'title' => 'Application Submitted',
                'description' => 'Your OJT application has been received.',
                'status' => 'completed',
            ],
            [
                'id' => 2,
                'title' => 'Document Review',
                'description' => 'Admin is verifying your proof of hiring.',
                'status' => 'pending',
            ],
            [
                'id' => 3,
                'title' => 'Location Verification',
                'description' => 'Validating company coordinates.',
                'status' => 'pending',
            ],
            [
                'id' => 4,
                'title' => 'Final Approval',
                'description' => 'Awaiting final sign-off.',
                'status' => 'pending',
            ],
        ];
        
        switch ($status) {
            case 'reviewing':
                $steps[1]['status'] = 'in_progress';
                break;
            case 'approved':
                $steps[1]['status'] = 'completed';
                $steps[2]['status'] = 'completed';
                $steps[3]['status'] = 'completed';
                break;
            case 'rejected':
                $steps[1]['status'] = 'rejected';
                $steps[2]['status'] = 'rejected';
                $steps[3]['status'] = 'rejected';
                break;
        }
        
        return $steps;
    }
    
    /**
     * Cancel/resubmit placement request.
     * 
     * POST /api/placement/resubmit
     */
    public function resubmit(Request $request)
    {
        $user = $request->user();
        
        // Find rejected request
        $existingRequest = PlacementRequest::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->first();
            
        if (!$existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No rejected request found to resubmit.',
            ], 400);
        }
        
        // Delete the rejected request
        $existingRequest->delete();
        
        // Reset user verification status
        $user->update(['verification_status' => null]);
        
        return response()->json([
            'success' => true,
            'message' => 'You can now submit a new placement request.',
        ]);
    }
    
    /**
     * Get list of partnered companies for map display.
     * 
     * GET /api/companies/partnered
     */
    public function getPartneredCompanies(Request $request)
    {
        $companies = Company::where('is_partnered', true)
            ->where('is_verified', true)
            ->with(['activeGeofences'])
            ->get()
            ->map(function ($company) {
                $geofence = $company->activeGeofences->first();
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'address' => $company->address,
                    'phone' => $company->phone,
                    'email' => $company->email,
                    'website' => $company->website,
                    'description' => $company->description,
                    'job_roles' => $company->job_roles,
                    'requirements' => $company->requirements,
                    'latitude' => $geofence?->latitude,
                    'longitude' => $geofence?->longitude,
                    'radius_meters' => $geofence?->radius_meters ?? 50,
                ];
            });
            
        return response()->json([
            'success' => true,
            'companies' => $companies,
        ]);
    }
}
