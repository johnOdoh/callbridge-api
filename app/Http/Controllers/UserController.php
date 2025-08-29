<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

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

    public function leaderboard(Request $request)
    {
        $leaderboard = User::select('users.*', DB::raw('COUNT(affiliates.id) as total_referrals'))
            ->join('affiliates', 'users.id', '=', 'affiliates.referrer_id')
            ->groupBy('users.id')
            ->orderByDesc('total_referrals')
            ->get();
        return $leaderboard->map(fn($user) => [
            'name' => $user->fullname(),
            'image' => $user->preferences?->image ? asset('storage/' . $user->preferences->image) : null,
            'referrals' => $user->total_referrals,
            'talk_time_earned' => formatSeconds($user->total_referral_talk_time)
        ]);
    }

    public function updateImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        $user = $request->user();
        if ($user->preference->image)
            File::delete(public_path('storage/'.$user->preference->image));
        if ($path = $request->file('image')->store('dps', 'public')) {
            $user->preference()->update(['image' => $path]);
            return response()->json(['message' => 'Profile image updated successfully']);
        }
        return response()->json(['message' => 'Failed to update profile image'], 500);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->noContent();
    }
}
