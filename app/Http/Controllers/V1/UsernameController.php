<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Username;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UsernameController extends Controller
{
    /**
     * @param string $username
     * @return JsonResponse
     * @group Username
     */
    public function index(string $username): JsonResponse
    {
        $username = Username::where('username', $username)->firstOrFail();
        return response()->json([
            'username' => $username->username,
            'mobile' => $username->phone,
        ], Response::HTTP_OK);
    }
}
