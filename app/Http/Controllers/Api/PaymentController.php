<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'updated_at');
        $residentId = $request->input('resident_id');
        $status = $request->input('sync_status');

        $query = Payment::query();

        if (isset($residentId)) {
            $query->filterByResidentId($residentId);
        }

        var_dump($status);
        if (isset($status)) {
            $status = filter_var($status, FILTER_VALIDATE_BOOLEAN);
            $query->byStatus($status);
        }

        if (in_array($sortBy, ['name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $payments = $query->paginate($limit);

        foreach ($payments as $payment) {
            $resident = Resident::find($payment->resident_id);
            if ($resident) {
                $payment->resident_name = $resident->name;
            }
        }

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_PAYMENT, $payments);
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
        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|exists:residents,id',
            'payment_evidence' => 'required',
            'billing_date' => 'required|date|date_format:Y-m-d',
            'billing_amount' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();

            $resident = Resident::find($input['resident_id']);
            if (!$resident) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 400);
            } else {
                $input['resident_id'] = $resident->id;
            }
            $payment = Payment::create($input);

            if (!$payment) {
                return ApiResponse::error(sprintf(ErrorMessages::FAILED_CREATE_MODEL, 'payment'), 404);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_CREATE_PAYMENT, $payment, 201);
        } catch (\Exception $e) {
            Log::error('Payment creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Payment'), 404);
        }

        $resident = Resident::find($payment->resident_id);
        if ($resident) {
            $payment->resident_name = $resident->name;
        }

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_PAYMENT, $payment);
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
        $validator = Validator::make($request->all(), [
            'resident_id' => 'nullable|exists:residents,id',
            'payment_evidence' => 'nullable',
            'billing_date' => 'nullable|date|before:today|date_format:Y-m-d',
            'billing_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:Belum Dibayar,Sudah Dibayar'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $payment = Payment::find($id);

        if (!$payment) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Payment'), 404);
        }

        try {
            $input = $request->only(['payment_evidence', 'billing_date', 'billing_amount', 'status', 'resident_id']);

            if (isset($input['resident_id']) && $input['resident_id'] !== null) {
                $resident = Resident::find($input['resident_id']);
                if (!$resident) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 400);
                }
            } else {
                unset($input['category_id']);
            }

            $payment->update(array_filter($input, function ($value) {
                return !is_null($value);
            }));

            $payment->update($input);

            return ApiResponse::success(SuccessMessages::SUCCESS_UPDATE_PAYMENT, $payment);
        } catch (\Exception $e) {
            Log::error('Payment creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Payment'), 404);
        }

        $payment->delete();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_PAYMENT);
    }
}
