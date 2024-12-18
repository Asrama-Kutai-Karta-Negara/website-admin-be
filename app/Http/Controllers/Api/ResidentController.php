<?php

namespace App\Http\Controllers\API;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResidentController
{

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $name = $request->input('name');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'updated_at');

        $query = Resident::query();

        if ($name) {
            $query->byName($name);
        }
        if (isset($status)) {
            $query->byStatus($status);
        }

        if (in_array($sortBy, ['name', 'email', 'status', 'room_number', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $residents = $query->paginate($limit);

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_RESIDENT, $residents);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'birth_date' => 'required|date|before:today',
            'address' => 'required|string|max:255',
            'origin_city' => 'required|string|max:100',
            'origin_campus' => 'required|string|max:100',
            'phone_number' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'room_number' => 'required|string|max:50',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();
            $resident = Resident::create($input);

            if (!$resident) {
                return ApiResponse::error(sprintf(ErrorMessages::FAILED_CREATE_MODEL, 'resident'), 404);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_CREATE_RESIDENT, $resident, 201);
        } catch (\Exception $e) {
            Log::error('Resident creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $resident = Resident::find($id);

        if (!$resident) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 404);
        }

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_RESIDENT, $resident);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'birth_date' => 'required|date|before:today',
            'address' => 'required|string|max:255',
            'origin_city' => 'required|string|max:100',
            'origin_campus' => 'required|string|max:100',
            'phone_number' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'room_number' => 'required|string|max:50',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $resident = Resident::find($id);

        if (!$resident) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 404);
        }

        try {
            $input = $request->only(['name', 'email', 'phone_number', 'room_number', 'status']);


            $resident->update(array_filter($input, function ($value) {
                return !is_null($value);
            }));

            $resident->update($input);

            return ApiResponse::success(SuccessMessages::SUCCESS_UPDATE_RESIDENT, $resident);
        } catch (\Exception $e) {
            Log::error('Resident creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $resident = Resident::find($id);

        if (!$resident) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 404);
        }

        $resident->delete();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_RESIDENT);
    }
}
