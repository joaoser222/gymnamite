<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        return response()->json([
            'version' => $user->updated_at?->toISOString(),
            'permissions' => $user->permissionNames()->all(),
        ]);
    }
}
