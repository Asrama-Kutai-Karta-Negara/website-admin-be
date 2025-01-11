<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\OriginCampus;
use App\Models\OriginCity;
use App\Models\Resident;
use App\Models\RoomNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResidentController
{

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', null);
        $name = $request->input('name');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'updated_at');

        $maxLimit = 1000;
        $limit = is_numeric($limit) ? min((int)$limit, $maxLimit) : $maxLimit;

        $query = Resident::query();

        if ($name) {
            $query->byName($name);
        }
        if (isset($status)) {
            $query->byStatus($status);
        }

        if (in_array($sortBy, ['name', 'email', 'status', 'room_number', 'created_at', 'updated_at'])) {
            $orderBy = 'desc';
            if ($sortBy == 'name') {
                $orderBy = 'asc';
            }
            $query->orderBy($sortBy, $orderBy);
        }

        $residents = $query->paginate($limit);

        foreach ($residents as $resident) {
            $originCampus = OriginCampus::find($resident->origin_campus_id);
            if ($originCampus) {
                $resident->origin_campus = $originCampus->name;
            }

            $roomNumber = RoomNumber::find($resident->room_number_id);
            if ($roomNumber) {
                $resident->room_number = $roomNumber->name;
            }

            $originCities = OriginCity::find($resident->origin_city_id);
            if ($originCities) {
                $resident->origin_city = $originCities->name;
            }
        }

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_RESIDENT, $residents);
    }

    public function getIndex(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', null);
        $sortBy = $request->input('sort_by', 'updated_at');

        $maxLimit = 1000;
        $limit = is_numeric($limit) ? min((int)$limit, $maxLimit) : $maxLimit;

        $query = Resident::query();

        $query->select('id', 'name', 'room_number_id');

        if (in_array($sortBy, ['name', 'room_number_id'])) {
            $query->orderBy($sortBy, 'asc');
        }

        $residents = $query->paginate($limit);

        foreach ($residents as $resident) {
            $roomNumber = RoomNumber::find($resident->room_number_id);
            if ($roomNumber) {
                $resident->room_number = $roomNumber->name;
                $resident->name = $resident->name . ' - ' . $resident->room_number;
            }
        }

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_RESIDENT, $residents);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:150',
            'birth_date' => 'required|date|before:today|date_format:Y-m-d',
            'address' => 'required|string|max:255',
            'origin_city_id' => 'required|exists:origin_cities,id',
            'origin_campus_id' => 'required|exists:origin_campuses,id',
            'phone_number' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'room_number_id' => 'required|exists:room_numbers,id',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();

            $originCampus = OriginCampus::find($input['origin_campus_id']);
            if (!$originCampus) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Origin Campus'), 400);
            }

            $originCity = OriginCity::find($input['origin_city_id']);
            if (!$originCity) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Origin City'), 400);
            }

            $category = RoomNumber::find($input['room_number_id']);
            if (!$category) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Room Number'), 400);
            }

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
            'name' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0|max:150',
            'birth_date' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:255',
            'origin_city_id' => 'nullable|exists:origin_cities,id',
            'origin_campus_id' => 'nullable|exists:origin_campuses,id',
            'phone_number' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'room_number_id' => 'nullable|exists:room_numbers,id',
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
            $input = $request->only(['name', 'age', 'birth_date', 'address', 'origin_city_id', 'origin_campus_id', 'phone_number', 'room_number_id', 'status']);

            if (isset($input['origin_campus_id']) && $input['origin_campus_id'] !== null) {
                $originCampus = OriginCampus::find($input['origin_campus_id']);
                if (!$originCampus) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Origin Campus'), 400);
                }
            } else {
                unset($input['origin_campus_id']);
            }

            if (isset($input['origin_city_id']) && $input['origin_city_id'] !== null) {
                $originCity = OriginCity::find($input['origin_city_id']);
                if (!$originCity) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Origin City'), 400);
                }
            } else {
                unset($input['origin_city_id']);
            }

            if (isset($input['room_number_id']) && $input['room_number_id'] !== null) {
                $roomNumber = RoomNumber::find($input['room_number_id']);
                if (!$roomNumber) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Room Number'), 400);
                }
            } else {
                unset($input['room_number_id']);
            }

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

        $resident->status = 'inactive';
        $resident->save();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_RESIDENT);
    }
}
