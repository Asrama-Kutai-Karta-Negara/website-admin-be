<?php

namespace App\Http\Controllers\Api;

use App\Http\Constants\SuccessMessages;
use App\Http\Responses\ApiResponse;
use App\Models\FinancialReport;
use App\Models\Resident;
use App\Models\RoomNumber;
use Illuminate\Http\Request;

class StaticController extends Controller
{
    public function getResidentActive()
    {
        $activeResidentsCount = Resident::query()->where('status', 'active')->count();
        $totalResidentsCount = Resident::all()->count();
        $data = [
            'data_active' => $activeResidentsCount,
            'data_count' => $totalResidentsCount,
        ];
        return ApiResponse::success(SuccessMessages::SUCCESS_GET_RESIDENT, $data);
    }

    public function getOccupiedRoom()
    {
        $totalRooms = RoomNumber::count();

        $occupiedRooms = RoomNumber::whereHas('residents', function ($query) {
            $query->where('status', 'active');
        })->count();

        $data = [
            'data_active' => $occupiedRooms,
            'data_count' => $totalRooms,
        ];
        return ApiResponse::success(SuccessMessages::SUCCESS_GET_ROOM_NUMBER, $data);
    }

    public function getPemasukan($bulan)
    {
        if ($bulan < 1 || $bulan > 12) {
            return ApiResponse::error("Bulan tidak valid", 400);
        }
        $bulan = (int) $bulan;
        $startDate = now()->month($bulan)->startOfMonth()->toDateString();
        $endDate = now()->month($bulan)->endOfMonth()->toDateString();

        $query = FinancialReport::query()
            ->byReportCategories('Pemasukan')
            ->whereBetween('report_date', [$startDate, $endDate]);

        $weeklyIncome = [0, 0, 0, 0];
        $reports = $query->get();

        foreach ($reports as $report) {
            $reportDate = \Carbon\Carbon::parse($report->report_date);

            $weekOfMonth = $reportDate->weekOfMonth;

            if ($weekOfMonth >= 1 && $weekOfMonth <= 4) {
                $weeklyIncome[$weekOfMonth - 1] += $report->report_amount;
            }
        }

        $data = [
            'weekly_income' => $weeklyIncome,
            'total_income' => array_sum($weeklyIncome),
        ];
        return ApiResponse::success(SuccessMessages::SUCCESS_GET_FINANCIAL_REPORT, $data);
    }

    public function getPengeluran($bulan)
    {
        if ($bulan < 1 || $bulan > 12) {
            return ApiResponse::error("Bulan tidak valid", 400);
        }
        $bulan = (int) $bulan;
        $startDate = now()->month($bulan)->startOfMonth()->toDateString();
        $endDate = now()->month($bulan)->endOfMonth()->toDateString();

        $query = FinancialReport::query()
            ->byReportCategories('Pengeluaran')
            ->whereBetween('report_date', [$startDate, $endDate]);

        $weeklyIncome = [0, 0, 0, 0];
        $reports = $query->get();

        foreach ($reports as $report) {
            $reportDate = \Carbon\Carbon::parse($report->report_date);

            $weekOfMonth = $reportDate->weekOfMonth;

            if ($weekOfMonth >= 1 && $weekOfMonth <= 4) {
                $weeklyIncome[$weekOfMonth - 1] += $report->report_amount;
            }
        }

        $data = [
            'weekly_outcome' => $weeklyIncome,
            'total_outcome' => array_sum($weeklyIncome),
        ];
        return ApiResponse::success(SuccessMessages::SUCCESS_GET_FINANCIAL_REPORT, $data);
    }
}
