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

        /**
         * IP расчеты маршрутов
         */
        $spreadsheet->getActiveSheet()->setTitle('IP расчеты маршрутов');

        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;

        $orders = Order::all();

        foreach ($orders as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '31.202.139.47':
                        break;
                    default:
                        $orders_unic[$i] = $value->IP_ADDR;
                        $i++;
                };
            }
        }

        $orders_unic = array_unique($orders_unic);

        $sheet->setCellValue('B' . 1, 'Это список IP, с которых делали расчеты маршрутов');
        $i = 3;
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->setCellValue('A2', 'IP');
        foreach ($orders_unic as $value) {
            $sheet->setCellValue('A' . $i, $value);
            $i++;
        }

        /**
         * IP и страницы просещения
         */
        $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'IP и страницы просещения');


        $spreadsheet->addSheet($myWorkSheet, 1);
        $sheet = $spreadsheet->getSheet(1);

        $i = 0;

        $IPs = IP::all();

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '31.202.139.47':
                        break;
                    default:
                        $IPs_page[$i]['IP_ADDR'] = $value->IP_ADDR;
                        $IPs_page[$i]['page'] = $value->page;
                        $IPs_page[$i]['created_at'] = $value->created_at;
                        $i++;
                };
            }
        }

        $sheet->setCellValue('D' . 1, 'Это список IP,которые посещали конкретные страницы');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->setCellValue('A2', 'IP');
        $sheet->setCellValue('B2', 'Page');
        $sheet->setCellValue('C2', 'DateTime');
        $i = 3;

        foreach ($IPs_page as $value) {
            $sheet->setCellValue('A' . $i, $value ['IP_ADDR']);
            $sheet->setCellValue('B' . $i, $value ['page']);
            $sheet->setCellValue('C' . $i, $value ['created_at']);
              $i++;
        }

        /**
         * IP всеx посетившие сайт
         */
        $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'IP всеx посетившие сайт');


        $spreadsheet->addSheet($myWorkSheet, 2);
        $sheet = $spreadsheet->getSheet(2);

        $i = 0;

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '31.202.139.47':
                        break;
                    default:
                        $IPs_page[$i] = $value->IP_ADDR;
                        $i++;
                };
            }
        }

        $IPs_page_unic = array_unique($IPs_page);

        $sheet->setCellValue('B' . 1, 'Это список уникальных IP,которые поcетили сайт');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->setCellValue('A2', 'IP');

        $i = 3;
        foreach ($IPs_page_unic as $value) {
            $sheet->setCellValue('A' . $i, $value);
            $i++;
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

}
