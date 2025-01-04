<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\FileConstant;
use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PaymentController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', null);
        $sortBy = $request->input('sort_by', 'updated_at');
        $residentName = $request->input('resident');
        $status = $request->input('sync_status');

        $maxLimit = 1000;
        $limit = is_numeric($limit) ? min((int)$limit, $maxLimit) : $maxLimit;

        $query = Payment::query();

        if (isset($residentName)) {
            $residentIds = Resident::where('name', 'like', '%' . $residentName . '%')
                ->pluck('id')
                ->toArray();
            $query->filterByArrayResidentId($residentIds);
        }

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
            $payment->payment_evidence = Storage::url($payment->payment_evidence);
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
            'payment_evidence' => 'required|file|mimes:jpg,png,jpeg|max:5120',
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

            $file = $request->file('payment_evidence');
            $filePath = $file->store(FileConstant::FOLDER_PAYMENTS, FileConstant::FOLDER_PUBLIC);

            if (!$file->isValid()) {
                return ApiResponse::error('File is not valid', 400);
            }
            $fileContent = file_get_contents($file->getRealPath());
            if (!$fileContent) {
                return ApiResponse::error('Error reading the file content', 500);
            }

            $fileName = basename($filePath);

            $payment = Payment::create([
                'resident_id' => $input['resident_id'],
                'payment_evidence' => $filePath,
                'payment_file_name' => $fileName,
                'billing_date' => $input['billing_date'],
                'billing_amount' => $input['billing_amount'],
                'status' => $input['status'],
            ]);

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

    public function showFile($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Payment'), 404);
        }

        try {
            $filePath = $payment->file;
            if (!Storage::disk(FileConstant::FOLDER_PUBLIC)->exists($filePath)) {
                return ApiResponse::error('File not found', 404);
            }

            $fileContent = Storage::disk(FileConstant::FOLDER_PUBLIC)->get($filePath);

            $mimeType = File::mimeType(Storage::disk(FileConstant::FOLDER_PUBLIC)->path($filePath));

            return response($fileContent, Response::HTTP_OK)
                ->header('Content-Type', $mimeType);
        } catch (\Exception $e) {
            Log::error('Error retrieving file: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
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
            'payment_evidence' => 'nullable|file|mimes:jpg,png,jpeg|max:5120',
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
            $input = $request->only(['payment_evidence', 'payment_file_name', 'billing_date', 'billing_amount', 'status', 'resident_id']);

            if (isset($input['resident_id']) && $input['resident_id'] !== null) {
                $resident = Resident::find($input['resident_id']);
                if (!$resident) {
                    return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Resident'), 400);
                }
            } else {
                unset($input['category_id']);
            }

            if ($request->hasFile('payment_evidence')) {
                Storage::disk(FileConstant::FOLDER_PUBLIC)->delete($payment->payment_evidence);

                $file = $request->file('payment_evidence');
                $filePath = $file->store(FileConstant::FOLDER_PAYMENTS, FileConstant::FOLDER_PUBLIC);
                $input['payment_evidence'] = $filePath;

                $fileName = basename($filePath);
                $input['payment_file_name'] = $fileName;
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
