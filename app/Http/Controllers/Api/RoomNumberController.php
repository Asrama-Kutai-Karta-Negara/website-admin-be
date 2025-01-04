<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\RoomNumber;
use Illuminate\Http\Request;

class RoomNumberController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roomNumbers = RoomNumber::orderBy('name', 'asc')->get();

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_ROOM_NUMBER, $roomNumbers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
