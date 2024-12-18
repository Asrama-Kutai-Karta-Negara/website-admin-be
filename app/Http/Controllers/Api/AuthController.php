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

            if (!$user) {
                return ApiResponse::error(ErrorMessages::INVALID_CREDENTIALS, 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return ApiResponse::error(ErrorMessages::INVALID_CREDENTIALS, 401);
            }

            $token = JWTAuth::fromUser($user);

            $successData = [
                'name' => $user->name,
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
            JWTAuth::invalidate(JWTAuth::getToken());

            return ApiResponse::success(SuccessMessages::SUCCESS_LOGOUT);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to logout, please try again.', 500);
        }
    }
}
