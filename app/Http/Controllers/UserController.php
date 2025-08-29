<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function affiliate(Request $request)
    {
        $no_invited = $request->user()->referrals->count();
        $signed_up = $request->user()->referrals()->where('is_completed', true)->count();
        return response()->json([
            'people_invited' => $no_invited,
            'signed_up' => $signed_up,
            'talk_time_earned' => formatSeconds($request->user()->total_referral_talk_time),
            'referrals' => $request->user()->referrals()->latest()->get()->map(fn($affiliate) => [
                'name' => $affiliate->user->fullname(),
                'image' => $affiliate->user->preferences?->image ? asset('storage/' . $affiliate->user->preferences->image) : null,
                'joined_at' => $affiliate->created_at->diffForHumans()
            ])
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->noContent();
    }
}
