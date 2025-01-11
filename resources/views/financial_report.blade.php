<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Laporan Keuangan</h2>
    <table>
        <thead>
            <tr>
                <th>Judul laporan</th>
                <th>Tanggal Laporan</th>
                <th>Nominal</th>
                <th>Jenis Laporan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
            <tr>
                <td>{{ $report->title }}</td>
                <td>{{ $report->report_date }}</td>
                <td>{{ $report->report_amount }}</td>
                <td>{{ $report->report_categories }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>