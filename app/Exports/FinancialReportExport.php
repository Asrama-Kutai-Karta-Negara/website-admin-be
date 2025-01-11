<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FinancialReportExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $reports;

    public function __construct($reports)
    {
        $this->reports = $reports;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->reports;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Judul laporan',
            'Tanggal Laporan',
            'Nominal',
            'Jenis Laporan'
        ];
    }
}
