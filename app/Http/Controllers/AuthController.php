<?php

namespace App\Http\Controllers;

use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private function code(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function csv($v): ?string
    {
        if (is_array($v)) return implode(',', array_filter(array_map('trim', $v)));
        return $v;
    }

    public function register(Request $r)
    {
        $data = $r->validate([
            'email' => 'required|email|unique:users,email',
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9._]+$/', 'unique:users,username'],
            'password' => 'required|string|min:6',
            'genres' => 'nullable',
        ]);

        $code = $this->code();
        $user = User::create([
            'name' => $data['username'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $data['password'], // di-hash otomatis oleh casts()
            'genres' => $this->csv($data['genres'] ?? null),
            'verification_code' => $code,
        ]);

        ReadingList::create([
            'user_id' => $user->id,
            'name' => $user->username . "'s Reading List",
            'is_default' => true,
            'is_public' => true,
        ]);

        Log::info("Kode verifikasi {$user->email}: {$code}");

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user,
            // Tanpa server email, kode ditampilkan hanya saat APP_DEBUG=true.
            'verification_code' => config('app.debug') ? $code : null,
        ], 201);
    }

    public function login(Request $r)
    {
        $r->validate(['login' => 'required|string', 'password' => 'required|string']);

        $user = User::where('email', $r->login)->orWhere('username', $r->login)->first();
        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json(['message' => 'Email/nama pengguna atau kata sandi salah'], 401);
        }

        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function me(Request $r)
    {
        $user = $r->user();
        $user->loadCount(['followers', 'following', 'stories']);
        return $user;
    }

    public function updateProfile(Request $r)
    {
        $user = $r->user();
        $r->validate([
            'bio' => 'nullable|string|max:500',
            'username' => ['sometimes', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9._]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'genres' => 'nullable',
        ]);

        // Mengganti nama pengguna / email wajib memakai kata sandi saat ini.
        $changingUsername = $r->filled('username') && $r->username !== $user->username;
        $changingEmail = $r->filled('email') && $r->email !== $user->email;
        if ($changingUsername || $changingEmail) {
            if (!$r->filled('current_password') || !Hash::check($r->current_password, $user->password)) {
                return response()->json(['message' => 'Kata sandi saat ini salah'], 422);
            }
        }

        if ($r->exists('bio')) $user->bio = $r->input('bio');
        if ($r->filled('username')) { $user->username = $r->username; $user->name = $r->username; }
        if ($r->filled('email') && $r->email !== $user->email) {
            $user->email = $r->email;
            $user->email_verified_at = null;
        }
        if ($r->exists('genres')) $user->genres = $this->csv($r->input('genres'));
        $user->save();

        return $user;
    }

    public function changePassword(Request $r)
    {
        $r->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = $r->user();
        if (!Hash::check($r->current_password, $user->password)) {
            return response()->json(['message' => 'Kata sandi saat ini salah'], 422);
        }

        $user->update(['password' => $r->password]);
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Kata sandi diperbarui']);
    }

    public function forgotPassword(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        $user = User::where('email', $r->email)->first();
        $code = null;

        if ($user) {
            $code = $this->code();
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );
            Log::info("Kode reset {$user->email}: {$code}");
        }

        // Selalu sukses agar email yang terdaftar tidak bisa ditebak.
        return response()->json([
            'message' => 'Jika email terdaftar, kode reset sudah dikirim',
            'debug_code' => config('app.debug') ? $code : null,
        ]);
    }

    public function resetPassword(Request $r)
    {
        $r->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $r->email)->first();
        $valid = $row && now()->diffInMinutes($row->created_at, true) <= 60 && Hash::check($r->code, $row->token);
        if (!$valid) {
            return response()->json(['message' => 'Kode salah atau kedaluwarsa'], 422);
        }

        $user = User::where('email', $r->email)->firstOrFail();
        $user->update(['password' => $r->password]);
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $r->email)->delete();

        return response()->json(['message' => 'Kata sandi berhasil diatur ulang']);
    }

    public function verifyEmail(Request $r)
    {
        $r->validate(['code' => 'required|string']);
        $user = $r->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email sudah terverifikasi', 'user' => $user]);
        }
        if (!$user->verification_code || $user->verification_code !== $r->code) {
            return response()->json(['message' => 'Kode verifikasi salah'], 422);
        }

        $user->forceFill(['email_verified_at' => now(), 'verification_code' => null])->save();
        return response()->json(['message' => 'Email terverifikasi', 'user' => $user]);
    }

    public function resendVerification(Request $r)
    {
        $user = $r->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email sudah terverifikasi']);
        }

        $code = $this->code();
        $user->update(['verification_code' => $code]);
        Log::info("Kode verifikasi {$user->email}: {$code}");

        return response()->json([
            'message' => 'Kode verifikasi dikirim ulang',
            'verification_code' => config('app.debug') ? $code : null,
        ]);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'ok']);
    }

    public function deleteAccount(Request $r)
    {
        $r->validate(['password' => 'required|string']);
        $user = $r->user();
        if (!Hash::check($r->password, $user->password)) {
            return response()->json(['message' => 'Kata sandi salah'], 422);
        }

        $user->tokens()->delete();
        $user->delete();
        return response()->json(['message' => 'Akun dihapus']);
    }
}
