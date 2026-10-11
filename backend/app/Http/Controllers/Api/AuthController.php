<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Login, profil, dan logout. Dipakai bersama oleh murid, petugas, dan admin.
 */
class AuthController extends Controller
{
    /**
     * Login memakai NIS atau email. Mengembalikan token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identitas' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identitas = trim($data['identitas']);
        $kolom = filter_var($identitas, FILTER_VALIDATE_EMAIL) ? 'email' : 'nis';

        $user = User::where($kolom, $identitas)->first();

        // Pengecekan password tetap dijalankan walau akun tidak ditemukan, supaya
        // waktu respons tidak membocorkan apakah NIS atau email itu terdaftar.
        $hashPembanding = $user?->password ?? Hash::make('tidak-dipakai');
        $passwordBenar = Hash::check($data['password'], $hashPembanding);

        if (! $user || ! $passwordBenar) {
            return response()->json(['message' => 'NIS/email atau password salah.'], 401);
        }

        return response()->json([
            'token' => $user->createToken('aplikasi')->plainTextToken,
            'user' => $this->ringkasUser($user),
        ]);
    }

    /**
     * Data akun yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->ringkasUser($request->user())]);
    }

    /**
     * Logout: mencabut token yang dipakai pada permintaan ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /**
     * Data akun yang aman dikirim ke aplikasi (tanpa password dan token).
     *
     * @return array<string, mixed>
     */
    private function ringkasUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'nis' => $user->nis,
            'nisn' => $user->nisn,
            'kelas' => $user->kelas,
            'email' => $user->email,
            'foto' => $user->foto,
        ];
    }
}