<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function cekNis(Request $request)
    {
        $request->validate([
            'nis' => 'required'
        ]);

        $siswa = Siswa::where('nis', $request->nis)->first();

        if (!$siswa) {
            return response()->json([
                'message' => 'NIS tidak ditemukan'
            ], 404);
        }

        $user = User::firstOrCreate(
            ['id_siswa' => $siswa->id_siswa],
            [
                'username'     => $siswa->nis,
                'password'     => Hash::make('login-siswa'),
                'nama_lengkap' => $siswa->nama_siswa,
                'role'         => 'siswa',
                'email'        => null
            ]
        );

        return response()->json([
            'siswa' => $siswa,
            'has_email' => !is_null($user->email)
        ]);
    }

    public function kirimOtp(Request $request)
    {
        $request->validate([
            'nis'   => 'required',
            'email' => 'required|email'
        ]);

        $siswa = Siswa::where('nis', $request->nis)->first();

        if (!$siswa) {
            return response()->json([
                'message' => 'NIS tidak ditemukan'
            ], 404);
        }

        $user = User::where('id_siswa', $siswa->id_siswa)->first();

        if (!$user->email) {
            $user->update([
                'email' => $request->email
            ]);
        }

        $otp = rand(100000, 999999);

        Cache::put(
            'otp_login_' . $user->id_user,
            $otp,
            now()->addMinutes(5)
        );

        Mail::raw(
            "Kode OTP kamu: $otp (berlaku 5 menit)",
            function ($mail) use ($user) {
                $mail->to($user->email)
                     ->subject('Kode Login Kedai Sehat');
            }
        );

        return response()->json([
            'message' => 'OTP berhasil dikirim'
        ]);
    }

    public function verifikasiOtp(Request $request)
    {
        $request->validate([
            'nis'   => 'required',
            'otp'   => 'required'
        ]);

        $siswa = Siswa::where('nis', $request->nis)->firstOrFail();
        $user  = User::where('id_siswa', $siswa->id_siswa)->firstOrFail();

        $cacheOtp = Cache::get('otp_login_' . $user->id_user);

        if ($cacheOtp != $request->otp) {
            return response()->json([
                'message' => 'OTP salah atau kadaluarsa'
            ], 401);
        }

        Cache::forget('otp_login_' . $user->id_user);

        $token = $user->createToken('kedai-sehat')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau password salah'
            ], 401);
        }

        $token = $user->createToken('kedai-sehat')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
