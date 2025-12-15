<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssetResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile with assets.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Eager load assets to prevent N+1 queries
        $user->load('assets');

        return response()->json([
            'user' => new UserResource($user),
            'assets' => AssetResource::collection($user->assets),
        ]);
    }
}
