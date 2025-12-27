<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CorporateProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Corporate Management Controller
 * Handles admin approval and management of corporate accounts
 */
class CorporateManagementController extends Controller
{
    /**
     * Get all corporate accounts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::where('user_type', 'corporate')
                     ->with('corporateProfile');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by company name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('corporateProfile', function($subQuery) use ($search) {
                      $subQuery->where('company_legal_name', 'like', "%{$search}%")
                               ->orWhere('trade_license_number', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 15);
        $corporateUsers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $corporateUsers,
        ], 200);
    }

    /**
     * Get pending corporate registrations
     * 
     * @return JsonResponse
     */
    public function pending(): JsonResponse
    {
        $pendingCorporates = User::where('user_type', 'corporate')
                                 ->where('status', 'pending')
                                 ->with('corporateProfile')
                                 ->latest()
                                 ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $pendingCorporates,
        ], 200);
    }

    /**
     * Get specific corporate account details
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::where('user_type', 'corporate')
                    ->with(['corporateProfile', 'addresses', 'activityLogs'])
                    ->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Corporate account not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * Approve corporate account
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:net_15,net_30,net_45,net_60,prepaid',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::where('user_type', 'corporate')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate account not found'
                ], 404);
            }

            if ($user->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is already approved'
                ], 400);
            }

            // Update user status
            $user->update(['status' => 'active']);

            // Update corporate profile with credit terms
            $corporateProfile = $user->corporateProfile;
            if ($corporateProfile) {
                $updateData = [];
                
                if ($request->has('credit_limit')) {
                    $updateData['credit_limit'] = $request->credit_limit;
                }
                
                if ($request->has('payment_terms')) {
                    $updateData['payment_terms'] = $request->payment_terms;
                }
                
                if (!empty($updateData)) {
                    $corporateProfile->update($updateData);
                }
            }

            // Log activity
            $user->activityLogs()->create([
                'action' => 'account_approved',
                'description' => 'Corporate account approved by admin' . 
                               ($request->admin_notes ? ': ' . $request->admin_notes : ''),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            // TODO: Send approval email notification to the corporate user

            return response()->json([
                'success' => true,
                'message' => 'Corporate account approved successfully',
                'data' => [
                    'user' => $user->fresh(),
                    'corporate_profile' => $corporateProfile->fresh(),
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate Approval Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Approval failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Reject corporate account
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::where('user_type', 'corporate')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate account not found'
                ], 404);
            }

            // Update user status
            $user->update(['status' => 'rejected']);

            // Log activity
            $user->activityLogs()->create([
                'action' => 'account_rejected',
                'description' => 'Corporate account rejected by admin: ' . $request->rejection_reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            // TODO: Send rejection email notification to the corporate user

            return response()->json([
                'success' => true,
                'message' => 'Corporate account rejected',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Corporate Rejection Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Rejection failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Suspend corporate account
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'suspension_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('user_type', 'corporate')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate account not found'
                ], 404);
            }

            $user->update(['status' => 'suspended']);

            // Log activity
            $user->activityLogs()->create([
                'action' => 'account_suspended',
                'description' => 'Corporate account suspended by admin: ' . $request->suspension_reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // TODO: Send suspension notification email

            return response()->json([
                'success' => true,
                'message' => 'Corporate account suspended',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Corporate Suspension Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Suspension failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Reactivate suspended corporate account
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function reactivate(int $id): JsonResponse
    {
        try {
            $user = User::where('user_type', 'corporate')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate account not found'
                ], 404);
            }

            $user->update(['status' => 'active']);

            // Log activity
            $user->activityLogs()->create([
                'action' => 'account_reactivated',
                'description' => 'Corporate account reactivated by admin',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // TODO: Send reactivation notification email

            return response()->json([
                'success' => true,
                'message' => 'Corporate account reactivated successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Corporate Reactivation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Reactivation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Get available payment terms options for dropdown
     * 
     * @return JsonResponse
     */
    public function getPaymentTermsOptions(): JsonResponse
    {
        try {
            // Get ENUM values from database
            $result = DB::select("SHOW COLUMNS FROM corporate_profiles WHERE Field = 'payment_terms'");
            
            if (empty($result)) {
                // Fallback to hardcoded values
                $paymentTerms = [
                    ['value' => 'prepaid', 'label' => 'Prepaid'],
                    ['value' => 'net_15', 'label' => 'Net 15 Days'],
                    ['value' => 'net_30', 'label' => 'Net 30 Days'],
                    ['value' => 'net_45', 'label' => 'Net 45 Days'],
                    ['value' => 'net_60', 'label' => 'Net 60 Days'],
                ];
            } else {
                // Extract ENUM values from database
                $type = $result[0]->Type;
                preg_match('/^enum\((.*)\)$/', $type, $matches);
                $enumValues = array_map(function($value) {
                    return trim($value, "'");
                }, explode(',', $matches[1]));
                
                // Format for frontend dropdown
                $paymentTerms = array_map(function($value) {
                    $label = match($value) {
                        'prepaid' => 'Prepaid',
                        'net_15' => 'Net 15 Days',
                        'net_30' => 'Net 30 Days',
                        'net_45' => 'Net 45 Days',
                        'net_60' => 'Net 60 Days',
                        default => ucfirst(str_replace('_', ' ', $value))
                    };
                    
                    return [
                        'value' => $value,
                        'label' => $label
                    ];
                }, $enumValues);
            }
            
            return response()->json([
                'success' => true,
                'data' => $paymentTerms,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get Payment Terms Options Error: ' . $e->getMessage());
            
            // Return fallback values on error
            return response()->json([
                'success' => true,
                'data' => [
                    ['value' => 'prepaid', 'label' => 'Prepaid'],
                    ['value' => 'net_15', 'label' => 'Net 15 Days'],
                    ['value' => 'net_30', 'label' => 'Net 30 Days'],
                    ['value' => 'net_45', 'label' => 'Net 45 Days'],
                    ['value' => 'net_60', 'label' => 'Net 60 Days'],
                ],
            ], 200);
        }
    }

    public function getCreditTerms(int $id): JsonResponse
    {
        $user = User::where('user_type', 'corporate')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Corporate account not found'
            ], 404);
        }

        $corporateProfile = $user->corporateProfile;

        if (!$corporateProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Corporate profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'credit_limit' => $corporateProfile->credit_limit,
                'payment_terms' => $corporateProfile->payment_terms,
            ],
        ], 200);
    }

    /**
     * Update corporate credit terms
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateCreditTerms(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'required|numeric|min:0',
            'payment_terms' => 'required|in:net_15,net_30,net_45,net_60,prepaid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('user_type', 'corporate')->find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate account not found'
                ], 404);
            }

            $corporateProfile = $user->corporateProfile;
            
            if (!$corporateProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Corporate profile not found'
                ], 404);
            }

            $corporateProfile->update([
                'credit_limit' => $request->credit_limit,
                'payment_terms' => $request->payment_terms,
            ]);

            // Log activity
            $user->activityLogs()->create([
                'action' => 'credit_terms_updated',
                'description' => "Credit terms updated: Limit = {$request->credit_limit}, Terms = {$request->payment_terms}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credit terms updated successfully',
                'data' => $corporateProfile->fresh(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update Credit Terms Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Update failed. Please try again.',
            ], 500);
        }
    }
}
