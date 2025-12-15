<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundAccountRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\UserResource; // Import the new request
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB facade

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

    /**
     * Fund the authenticated user's account.
     */
    public function fund(FundAccountRequest $request)
    {
        $user = $request->user();
        $amount = $request->validated('amount');

        DB::transaction(function () use ($user, $amount) {
            // Pessimistic lock the user row to prevent race conditions
            $user->fresh()->lockForUpdate();

            $user->balance = $user->balance->plus($amount);
            $user->save();
        });

        // Eager load assets to return a complete, updated profile
        $user->load('assets');

        return response()->json([
            'message' => 'Account funded successfully.',
            'user' => new UserResource($user),
            'assets' => AssetResource::collection($user->assets),
        ]);
    }
}
