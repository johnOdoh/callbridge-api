<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Plan;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(Request $request)
    {
        $request->validate([
            'display_name' => 'required|string',
            'bio' => 'nullable|string',
            'email' => 'nullable|email|unique:preferences,email',
            'visibility' => 'in:Public,Anonymous',
        ]);
        $request->user()->preference()->update($request->all());
        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function affiliate(Request $request)
    {
        $no_invited = $request->user()->referrals->count();
        $signed_up = $request->user()->referrals()->where('is_completed', true)->count();
        return response()->json([
            'people_invited' => $no_invited,
            'signed_up' => $signed_up,
            'talk_time_earned' => formatSeconds($request->user()->total_referral_talk_time),
            'referrals' => $request->user()->referrals()->latest()->get()->map(fn($affiliate) => [
                'name' => $affiliate->referee->fullname(),
                'image' => $affiliate->referee->preference?->image ? asset('storage/' . $affiliate->referee->preference->image) : null,
                'joined_at' => $affiliate->created_at->diffForHumans()
            ])
        ]);
    }

    public function subscribe(Request $request, Plan $plan)
    {
        $user = $request->user();
        if ($user->wallet < $plan->amount) return response()->json(['message' => 'Insufficient funds to subscribe'], 400);
        if ($user->subscription && $user->subscription->is_active) {
            return response()->json(['message' => 'You already have an active subscription'], 400);
        }
        $user->subscription()->create([
            'plan_id' => $plan->id,
            'ends_at' => Carbon::now()->addDays($plan->interval)
        ]);
        $user->decrement('wallet', $plan->amount);
        Transaction::create([
            'user_id' => $user->id,
            'amount' => $plan->amount,
            'status' => 'COMPLETED',
            'type' => 'debit',
            'description' => "Subscribed to {$plan->name} plan"
        ]);
        return response()->json(['message' => 'Subscription successful']);
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
            'image' => $user->preference?->image ? asset('storage/' . $user->preference->image) : null,
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
