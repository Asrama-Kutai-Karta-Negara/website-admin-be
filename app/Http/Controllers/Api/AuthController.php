<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Constants\ValidationMessages;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required|same:password'
        ], [
            'name.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'name'),
            'email.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'email'),
            'email.email' => sprintf(ValidationMessages::FIELD_EMAIL, 'email'),
            'password.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'password'),
            'confirm_password.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'confirm_password'),
            'confirm_password.same' => sprintf(ValidationMessages::FIELD_REQUIRED, 'confirm_password', 'password')
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);

            $emailExists = User::where('email', $request->email)->exists();
            if ($emailExists) {
                return ApiResponse::error(ErrorMessages::EMAIL_ALREADY_EXISTS, 404);
            }
            $input['role'] = 'User';
            $user = User::create($input);

            if (!$user) {
                return ApiResponse::error(sprintf(ValidationMessages::FIELD_REQUIRED, 'user'), 404);
            }

            $token = JWTAuth::fromUser($user);
            $successData = [
                'token' => $token,
                'name' => $user->name,
            ];

            return ApiResponse::success(SuccessMessages::SUCCCESS_REGISTRATION, $successData);
        } catch (\Exception $e) {
            Log::error('User creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'email'),
            'email.email' => sprintf(ValidationMessages::FIELD_EMAIL, 'email'),
            'password.required' => sprintf(ValidationMessages::FIELD_REQUIRED, 'password'),
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if ($user->role != 'Admin') {
                return ApiResponse::error(ErrorMessages::INVALID_ROLE_ACCESS, 401);
            }
            if (!$user) {
                return ApiResponse::error(ErrorMessages::INVALID_CREDENTIALS, 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return ApiResponse::error(ErrorMessages::INVALID_CREDENTIALS, 401);
            }

            $token = JWTAuth::fromUser($user);

            $successData = [
                'name' => $user->name,
                'role' => $user->role,
                'email' => $user->email,
                'access_token' => $token,
            ];

            return ApiResponse::success(SuccessMessages::SUCCESS_LOGIN, $successData);
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function logout()
    {
        try {
            if (JWTAuth::getToken() && JWTAuth::check()) {
                JWTAuth::invalidate(JWTAuth::getToken());
                return ApiResponse::success(SuccessMessages::SUCCESS_LOGOUT);
            }
            return ApiResponse::error(ErrorMessages::TOKEN_INVALID_MISSING);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to logout, please try again.', 500);
        }
    }

    public function checkToken()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return ApiResponse::error(ErrorMessages::TOKEN_MISSING, 401);
            }

            if (JWTAuth::check()) {
                return ApiResponse::success(SuccessMessages::TOKEN_VALID);
            }

            return ApiResponse::error(ErrorMessages::TOKEN_INVALID, 401);
        } catch (TokenExpiredException $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_EXPIRED + "." + "Please login again.", 401);
        } catch (TokenInvalidException $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_INVALID + "." + "Please login again", 401);
        } catch (\Exception $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_FAILED_VERIFIED, 500);
        }
    }

    public function refreshToken()
    {
        try {
            $currentToken = JWTAuth::getToken();

            if (!$currentToken) {
                return ApiResponse::error(ErrorMessages::TOKEN_MISSING, 401);
            }
            $newToken = JWTAuth::refresh($currentToken);

            return ApiResponse::success(SuccessMessages::TOKEN_REFRESHED, [
                'access_token' => $newToken,
            ]);
        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
            return ApiResponse::error(ErrorMessages::TOKEN_FAILED_VERIFIED, 500);
        }
    }
}
