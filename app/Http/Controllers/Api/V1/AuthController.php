<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    /**
     * Login and get token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد غير صحيحة'],
            ]);
        }

        // Create token
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * Get current user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->current_company_id,
            'branch_id' => $user->current_branch_id,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }
}
