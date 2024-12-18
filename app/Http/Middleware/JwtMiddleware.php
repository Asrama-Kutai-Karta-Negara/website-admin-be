<?php

namespace App\Http\Middleware;

use App\Http\Constants\ErrorMessages;
use App\Http\Responses\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_EXPIRED, 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_INVALID, 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return ApiResponse::error(ErrorMessages::TOKEN_MISSING, 401);
        } catch (Exception $e) {
            return ApiResponse::error(ErrorMessages::UNAUTHORIZED_ACCESS, 401);
        }

        return $next($request);
    }
}
