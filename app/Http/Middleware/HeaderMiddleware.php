<?php

namespace App\Http\Middleware;

use App\Http\Constants\ErrorMessages;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        $validApiKey = env('API_KEY');

        if (!$apiKey || $apiKey !== $validApiKey) {
            return ApiResponse::error(ErrorMessages::UNAUTHORIZED_API_KEY_ACCESS, Response::HTTP_UNAUTHORIZED);
        }

        $response = $next($request);

        return $response;
    }
}
