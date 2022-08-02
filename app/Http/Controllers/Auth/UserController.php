<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Request $request)
    {
        try {
            return auth()->user();
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function permissions()
    {
        $user = \Auth::user();
        return $user->getUserPermissions($user);
    }

    public function roles()
    {
        $user = \Auth::user();
        return $user->getUserRoles($user);
    }
}
