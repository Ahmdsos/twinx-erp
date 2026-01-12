<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Company;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 * 
 * Hybrid tenant discovery middleware that supports:
 * - API Headers (X-Company-ID, X-Branch-ID) for API requests
 * - Session storage for web requests
 * - User's current_company_id/current_branch_id as fallback
 * 
 * Priority order:
 * 1. API Headers (if present)
 * 2. Session (if web request with session)
 * 3. User's stored current company/branch
 */
class TenantMiddleware
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admin without headers can access without context
        if ($user?->is_super_admin && !$this->hasTenantHeaders($request)) {
            $this->tenantContext->setFromUser($user);
            return $next($request);
        }

        // Priority 1: API Headers
        if ($this->hasTenantHeaders($request)) {
            if (!$this->setFromHeaders($request, $user)) {
                return $this->unauthorizedResponse($request, 'Invalid tenant headers or unauthorized access');
            }
        }
        // Priority 2: Session (Web)
        elseif ($this->hasSessionContext($request)) {
            if (!$this->setFromSession($request)) {
                return $this->noContextResponse($request);
            }
        }
        // Priority 3: User's default context
        elseif ($user) {
            if (!$this->tenantContext->setFromUser($user)) {
                return $this->noContextResponse($request);
            }
        }
        // No context available
        else {
            return $this->noContextResponse($request);
        }

        // Validate user has access to the selected branch
        if ($user && !$this->validateUserAccess($user)) {
            return $this->unauthorizedResponse($request, 'You do not have access to this branch');
        }

        return $next($request);
    }

    /**
     * Check if request has tenant headers.
     */
    private function hasTenantHeaders(Request $request): bool
    {
        return $request->hasHeader('X-Company-ID')
            && $request->hasHeader('X-Branch-ID');
    }

    /**
     * Check if session has tenant context.
     */
    private function hasSessionContext(Request $request): bool
    {
        return $request->hasSession()
            && session()->has('current_branch_id');
    }

    /**
     * Set context from request headers.
     */
    private function setFromHeaders(Request $request, $user): bool
    {
        $companyId = $request->header('X-Company-ID');
        $branchId = $request->header('X-Branch-ID');

        $company = Company::find($companyId);
        $branch = Branch::find($branchId);

        if (!$company || !$branch) {
            return false;
        }

        // Validate branch belongs to company
        if ($branch->company_id !== $company->id) {
            return false;
        }

        // Validate user has access (unless super admin)
        if ($user && !$user->is_super_admin && !$user->hasAccessToBranch($branch)) {
            return false;
        }

        $this->tenantContext->set($company, $branch);

        if ($user) {
            // Update user's current context
            $user->update([
                'current_company_id' => $company->id,
                'current_branch_id' => $branch->id,
            ]);
        }

        return true;
    }

    /**
     * Set context from session.
     */
    private function setFromSession(Request $request): bool
    {
        $branchId = session('current_branch_id');

        $branch = Branch::with('company')->find($branchId);

        if (!$branch || !$branch->company) {
            session()->forget(['current_company_id', 'current_branch_id']);
            return false;
        }

        $this->tenantContext->set($branch->company, $branch);

        return true;
    }

    /**
     * Validate user has access to current context.
     */
    private function validateUserAccess($user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $branch = $this->tenantContext->branch();

        if (!$branch) {
            return false;
        }

        return $user->hasAccessToBranch($branch);
    }

    /**
     * Return response when no context is available.
     */
    private function noContextResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant context required',
                'message' => 'Please provide X-Company-ID and X-Branch-ID headers, or select a branch.',
                'code' => 'TENANT_CONTEXT_MISSING',
            ], 400);
        }

        // Redirect to branch selection page for web
        return redirect()->route('tenant.select');
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorizedResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => $message,
                'code' => 'TENANT_ACCESS_DENIED',
            ], 403);
        }

        abort(403, $message);
    }
}
