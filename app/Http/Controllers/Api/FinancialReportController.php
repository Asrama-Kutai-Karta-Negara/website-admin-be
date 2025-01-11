<?php

namespace App\Http\Controllers\Api;

use App\Exports\FinancialReportExport;
use App\Http\Constants\ErrorMessages;
use App\Http\Constants\FileConstant;
use App\Http\Constants\SuccessMessages;
use App\Http\Constants\ValidationMessages;
use App\Http\Responses\ApiResponse;
use App\Models\FinancialReport;
use App\Models\Payment;
use App\Models\Resident;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class FinancialReportController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', null);
        $sortBy = $request->input('sort_by', 'updated_at');
        $category = $request->input('category');
        $title = $request->input('title');

        $maxLimit = 1000;
        $limit = is_numeric($limit) ? min((int)$limit, $maxLimit) : $maxLimit;

        $query = FinancialReport::query();

        if (isset($category)) {
            $query->byReportCategories($category);
        }

        if (isset($title)) {
            $query->byTitle($title);
        }

        if (in_array($sortBy, ['name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, 'desc');
        }

        $financialReports = $query->paginate($limit);
        foreach ($financialReports as $financialReport) {
            if ($financialReport->report_evidence != null) {
                $financialReport->report_evidence = Storage::url($financialReport->report_evidence);
            }
        }

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
            $fileName = null;
            $filePath = null;

            if ($request->hasFile('report_evidence')) {
                $file = $request->file('report_evidence');
                if (!$file->isValid()) {
                    return ApiResponse::error('File is not valid', 400);
                }

                $filePath = $file->store(FileConstant::FOLDER_REPORT, FileConstant::FOLDER_PUBLIC);
                $fileName = basename($filePath);
            }
            $financialReport = FinancialReport::create([
                'title' => $input['title'],
                'report_evidence' => $filePath,
                'report_file_name' => $fileName,
                'report_amount' => $input['report_amount'],
                'report_date' => $input['report_date'],
                'report_categories' => $input['report_categories'],
            ]);

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

    public function showFile($id)
    {
        $financialReport = FinancialReport::find($id);

        if (!$financialReport) {
            return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_NOT_FOUND, 'Financial Report'), 404);
        }

        try {
            $filePath = $financialReport->report_evidence;
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

    public function exportReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range_date' => 'required|integer|max:255',
            'format_file' => 'required|string|in:PDF,Excel',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 400);
        }

        try {

            $input = $request->all();
            $rangeDate = (int) $input['range_date'];
            $formatFile = $input['format_file'];

            $fileName = "financial_report_";
            $query = FinancialReport::query()
                ->select('title', 'report_date', 'report_amount', 'report_categories');

            if ($rangeDate !== 0) {
                $startDate = Carbon::now()->subMonths($rangeDate)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $query->whereBetween('report_date', [$startDate, $endDate]);
                $fileName = $fileName . $rangeDate . "_bulan";
            } else {
                $fileName = $fileName . "_semua_bulan";
            }

            $reports = $query->get();

            $filePath = FileConstant::FOLDER_EXPORT_REPORT . '/' . $fileName;

            if ($formatFile === 'PDF') {
                $pdfContent = view('financial_report', ['reports' => $reports])->render();
                $pdf = Pdf::loadHTML($pdfContent);
                $filePath = $filePath . '.pdf';
                Storage::disk(FileConstant::FOLDER_PUBLIC)
                    ->put($filePath, $pdf->output());

                $downloadUrl = Storage::url($filePath);

                $data = [
                    'file_name' => $fileName . '.pdf',
                    'file_url' => $downloadUrl,
                ];
                return ApiResponse::success(SuccessMessages::SUCCESS_GENERATE_REPORT, $data);
            } elseif ($formatFile === 'Excel') {
                $excelContent = Excel::raw(new FinancialReportExport($reports), \Maatwebsite\Excel\Excel::XLSX);

                $filePath = $filePath . '.xlsx';
                Storage::disk(FileConstant::FOLDER_PUBLIC)
                    ->put($filePath, $excelContent);

                $downloadUrl = Storage::url($filePath);

                $data = [
                    'file_name' => $fileName . '.xlsx',
                    'file_url' => $downloadUrl,
                ];
                return ApiResponse::success(SuccessMessages::SUCCESS_GENERATE_REPORT, $data);
            }

            return response()->json(['error' => 'Invalid format'], 400);
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
            $input = $request->only(['title', 'report_evidence', 'report_file_name', 'report_date', 'report_amount', 'report_categories']);

            if ($request->hasFile('report_evidence')) {
                if ($financialReport->report_evidence != null) {
                    Storage::disk(FileConstant::FOLDER_PUBLIC)->delete($financialReport->report_evidence);
                }

                $file = $request->file('report_evidence');
                $filePath = $file->store(FileConstant::FOLDER_REPORT, FileConstant::FOLDER_PUBLIC);
                $input['report_evidence'] = $filePath;

                $fileName = basename($filePath);
                $input['report_file_name'] = $fileName;
            }

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
            $payments = Payment::where('move_to_report', false)
                ->where('status', 'Sudah Dibayar')
                ->get();
            if ($payments->isEmpty()) {
                return ApiResponse::error(sprintf(ErrorMessages::MESSAGE_CANT_SYNC, 'payment'), 404);
            }

            foreach ($payments as $payment) {

                $resident = Resident::find($payment->resident_id);

                $input = [
                    'title' => ValidationMessages::SYNC_PAYMENT . '_' . $resident->name,
                    'report_date' => $payment->billing_date,
                    'report_amount' => $payment->billing_amount,
                    'report_categories' => 'Pemasukan',
                ];
                $financialReport = FinancialReport::create($input);
                if (!$financialReport) {
                    return ApiResponse::error(sprintf(ErrorMessages::FAILED_SYNC_MODEL, 'Payment'), 500);
                }

                $payment->update(['move_to_report' => true]);
            }

            return ApiResponse::success(SuccessMessages::SUCCESS_SYNC_PAYMENT, null);
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

        Storage::disk(FileConstant::FOLDER_PUBLIC)->delete($financialReport->report_evidence);
        $financialReport->delete();

        return ApiResponse::success(SuccessMessages::SUCCESS_DELETE_FINANCIAL_REPORT);
    }
}
