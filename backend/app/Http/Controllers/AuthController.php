<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserSchemaResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AuthController extends Controller
{
    public function login(Request $req) {
        $data = $req->validate([
            'nis' => 'nullable',
            'nip' => 'nullable',
            'password' => 'required',
        ]);

        // Prevent NIS and NIP being sent on the same request
        if ($req->has('nip') && $req->has('nis')) {
            throw new UnprocessableEntityHttpException('Hanya salah satu dari NIS atau NIP yang bisa digunakan untuk login.');
        }

        // Get the correct user schema
        /**
         * @var User $user
         */
        $user = null;

        // Handle NIS case
        if (isset($data['nis'])) {
            $user = UserSchemaResolver::resolveFromNis($data['nis']);
        }
        
        // Handle NIP case
        if (isset($data['nip'])) {
            $user = UserSchemaResolver::resolveFromNip($data['nip']);
        }

        // Create new access token
        $token = $user->login();
        
        return response([
            'message' => 'Login berhasil',
            'token' => $token,
            // 'user' => $user
        ]);
    }

    public function logout(Request $req) {
        /** @var User $user */
        $user = $req->user('api');
        $user->logout();

        return response(null, 204);
    }

    public function profile(Request $req) {
        $user = UserSchemaResolver::resolveFromUserModel($req->user('api'));
        $user->last_login_at = $user->lastLoginAt();
        $user->total_login_time = $user->totalLoginTime();

        return response($user);
    }
}
