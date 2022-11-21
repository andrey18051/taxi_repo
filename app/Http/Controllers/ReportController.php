<?php

namespace App\Http\Controllers;

use App\Models\IP;
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
        $reportIP_path = Storage::path('public/reports/reportIP.xlsx');

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setTitle('IP уникальные');

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
        $sheet->setCellValue('A' . $i, 'Это список IP, с которых делали расчеты маршрутов');
        $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'IP + page');

// Attach the "My Data" worksheet as the first worksheet in the Spreadsheet object
        $spreadsheet->addSheet($myWorkSheet, 1);
        $sheet = $spreadsheet->getSheet(1);

        $i = 0;

        $IPs = IP::all();

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                $IPs_unic[$i]['IP_ADDR'] = $value->IP_ADDR;
                $IPs_unic[$i]['page'] = $value->page;
                $i++;
            }
        }


        $i = 2;
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->setCellValue('A1', 'IP');
        $sheet->setCellValue('B1', 'Page');
        foreach ($IPs_unic as $value) {
            $sheet->setCellValue('A' . $i, $value ['IP_ADDR']);
            $sheet->setCellValue('B' . $i, $value ['page']);
              $i++;
        }
        $sheet->setCellValue('A' . $i, 'Это список IP,которые посещали конкретные страницы');
        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

}
