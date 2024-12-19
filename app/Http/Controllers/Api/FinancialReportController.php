<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\ErrorMessages;
use App\Http\Constants\SuccessMessages;
use App\Http\Constants\ValidationMessages;
use App\Http\Responses\ApiResponse;
use App\Models\FinancialReport;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FinancialReportController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'updated_at');
        $category = $request->input('category');

        $query = FinancialReport::query();

        if (isset($category)) {
            $query->byReportCategories($category);
        }

        if (in_array($sortBy, ['name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $financialReports = $query->paginate($limit);

        return ApiResponse::pagination(SuccessMessages::SUCCESS_GET_FINANCIAL_REPORT, $financialReports);
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
            'title' => 'required|string|max:255',
            'report_evidence' => 'nullable',
            'report_date' => 'required|date|date_format:Y-m-d',
            'report_amount' => 'required|numeric|min:0',
            'report_categories' => 'required|string|in:Pemasukan,Pengeluaran'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {
            $input = $request->all();

            $financialReport = FinancialReport::create($input);

            if (!$financialReport) {
                return ApiResponse::error(sprintf(ErrorMessages::FAILED_CREATE_MODEL, 'Financial Report'), 404);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_CREATE_FINANCIAL_REPORT, $financialReport, 201);
        } catch (\Exception $e) {
            Log::error('Financial Report creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $financialReport = FinancialReport::find($id);

        if (!$financialReport) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Financial Report'), 404);
        }

        return ApiResponse::success(SuccessMessages::SUCCESS_GET_FINANCIAL_REPORT, $financialReport);
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
            'title' => 'nullable|string|max:255',
            'report_evidence' => 'nullable',
            'report_date' => 'nullable|date|date_format:Y-m-d',
            'report_amount' => 'nullable|numeric|min:0',
            'report_categories' => 'nullable|string|in:Pemasukan,Pengeluaran'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        $financialReport = FinancialReport::find($id);

        if (!$financialReport) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Financial Report'), 404);
        }

        try {
            $input = $request->only(['title', 'report_evidence', 'report_date', 'report_amount', 'report_categories']);

            $financialReport->update(array_filter($input, function ($value) {
                return !is_null($value);
            }));

            $financialReport->update($input);

            return ApiResponse::success(SuccessMessages::SUCCESS_UPDATE_FINANCIAL_REPORT, $financialReport);
        } catch (\Exception $e) {
            Log::error('Financial Report creation failed: ' . $e->getMessage());

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function syncPayment()
    {
        try {
            $payments = Payment::where('move_to_report', false)->get();
            if ($payments->isEmpty()) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_CANT_SYNC, 'payment'), 404);
            }

            $totalAmount = $payments->sum('billing_amount');

            $currentDate = Carbon::now()->format('d_m_Y');
            $title = ValidationMessages::SYNC_PAYMENT . '_' . $currentDate;

            $input = [
                'title' => $title,
                'report_date' => Carbon::now(),
                'report_amount' => $totalAmount,
                'report_categories' => 'Pemasukan',
            ];

            $financialReport = FinancialReport::create($input);

            if (!$financialReport) {
                return ApiResponse::error(sprintf(ErrorMessages::FAILED_SYNC_MODEL, 'Payment'), 500);
            }

            foreach ($payments as $payment) {
                $payment->update(['move_to_report' => true]);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_SYNC_PAYMENT, $financialReport);
        } catch (\Exception $e) {
            Log::error('Payment synchronization failed: ' . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $financialReport = FinancialReport::find($id);

        if (!$financialReport) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Financial Report'), 404);
        }

        $financialReport->delete();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_FINANCIAL_REPORT);
    }
}
