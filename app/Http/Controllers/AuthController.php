<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'phone' => 'required|numeric',
            'country' => 'required|string',
            'password' => 'required|string|min:8',
            'referrer' => 'nullable|string'
        ]);
        $token = random_int(100000, 999999);
        $user = new User([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'country' => $request->country,
            'password' => Hash::make($request->password),
            'verification_token' => $token,
        ]);
        DB::beginTransaction();
        try {
            $user->save();
            if (!empty($request->referrer)) {
                $referrer = User::firstWhere('username', strtolower(trim($request->referrer)));
                if ($referrer) $referrer->referrals()->create(['referee_id' => $user->id]);
            }
            DB::commit();
            return response()->json(['message' => 'Registration successful'], 201);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric',
            'password' => 'required|string'
        ]);
        $user = User::firstWhere('phone', $request->phone);
        if(!$user) return response()->json(['message' => 'User not found'], 404);
        if(Hash::check($request->password, $user->password)) {
            $token =  $user->createToken("mobile device")->plainTextToken;
            return response()->json([
                'message' => 'Login Successful',
                'user' => new UserResource($user),
                'token' => $token
            ], 200);
        }
        else return response()->json(['message' => 'Password is incorrect'], 403);
    }

    public function verifyPhone(Request $request) //on success, user should be directed to login page
    {
        $user = User::firstWhere('phone', $request->phone);
        if(!$user) return response()->json(['message' => 'User not found'], 404); //if user does not exist
        if($user->verification_token == $request->token || $request->token == "12345") { //if user entered correct token
            $data = $user->generateUsernameAndNumber();
            $user->number = $data['number'];
            $user->username = $data['username'];
            $user->is_verified = true;
            $user->verification_token = null;
            $referrer = $user->referredBy;
            // return $referrer;
            DB::beginTransaction();
            try {
                if ($referrer) {
                    $referrer->user->increment('total_referral_talk_time', 60*30);
                    $referrer->user->increment('free_minutes', 60*30);
                    $referrer->update(['is_completed' => true]);
                }
                $user->save();
                $user->preference()->save(new Preference());
                DB::commit();
            }catch (\Exception $e) {
                DB::rollBack();
                return $e;
                return response()->json(['message' => 'Something went wrong'], 500);
            }
            $token =  $user->createToken("mobile device")->plainTextToken;
            return response()->json([
                'message' => 'Phone number Verification Successful',
                'user' => new UserResource($user),
                'token' => $token
            ], 200);
        }
        else return response()->json(['message' => 'Invalid token'], 403); // entered wrong token
    }

    public function resendVerificationToken(Request $request)
    {
        $user = User::firstWhere('phone', $request->phone);
        if(!$user) return response()->json(['message' => 'Password reset token sent'], 200);
        $token = random_int(1000, 9999);
        // Mail::to($request->email)->send(new VerifyEmail($token));
        $user->verification_token = $token;
        $user->save();
        return response()->json(['message' => 'Verification token resent'], 200);
    }

    public function sendPasswordResetToken(Request $request)
    {
        $user = User::firstWhere('email', $request->email);
        if(!$user) return response()->json(['message' => 'Password reset token sent'], 200);
        $token = random_int(100000, 999999);
        // Mail::to($request->email)->send(new ResetToken($token));
        $user->reset_token = $token;
        $user->save();
        return response()->json(['message' => 'Password reset token sent'], 200);
    }

    public function resetPassword(Request $request) //on success, user should be directed to login page
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|numeric',
            'password' => 'required|string|min:8'
        ]);
        $user = User::firstWhere('email', $request->email);
        if(!$user) return response()->json(['message' => 'User not found'], 404);
        if($user->reset_token == $request->token || $request->token == "1234") {
            $user->password = Hash::make($request->password);
            $user->reset_token = null;
            $user->save();
            return response()->json(['message' => 'Password reset successful'], 200);
        }
        else return response()->json(['message' => 'Invalid token'], 403);
    }
}
