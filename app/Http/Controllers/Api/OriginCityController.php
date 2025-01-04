<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\OriginCity;
use Illuminate\Http\Request;

class OriginCityController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $originCities = OriginCity::orderBy('name', 'asc')->get();

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_ORIGIN_CITY, $originCities);
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
