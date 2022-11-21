<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function reportIP()
    {
        $reportIP_path = Storage::path('public\reports\reportIP.xlsx');

        if (file_exists($reportIP_path)) {
            unlink($reportIP_path);
        }

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;

        $orders = Order::all();

        foreach ($orders as $value) {
            if ($value->IP_ADDR !== null) {
                $orders_unic[$i] = $value->IP_ADDR;
                $i++;
            }
        }

        $orders_unic = array_unique($orders_unic);

        $i = 2;
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->setCellValue('A1', 'IP');
        foreach ($orders_unic as $value) {
            $sheet->setCellValue('A' . $i, $value);
            $i++;
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path, basename($reportIP_path));
    }

}
