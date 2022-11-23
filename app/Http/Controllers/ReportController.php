<?php

namespace App\Http\Controllers;

use App\Models\IP;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Stevebauman\Location\Facades\Location;

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

        $sheet->getStyle('A2')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '808080'
                    ]
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);

        $coordN = count($orders_unic) + 2;

        $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

        $sheet->getStyle('A3:A' . $coordN)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);



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

        $sheet->setCellValue('B' . 1, 'Это список IP,которые посещали конкретные страницы');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->setCellValue('A2', 'IP');
        $sheet->setCellValue('B2', 'Page');
        $sheet->setCellValue('C2', 'DateTime');

        $sheet->getStyle('A2:C2')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '808080'
                    ]
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);

        $coordN = count($IPs_page) + 2;

        $sheet->getStyle('A2:C2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

        $sheet->getStyle('A3:C' . $coordN)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);

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


        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);

        $sheet->setCellValue('B1', 'Это список уникальных IP,которые поcетили сайт');
        $sheet->setCellValue('A2', 'IP');
        $sheet->setCellValue('B2', 'countryName');
        $sheet->setCellValue('C2', 'countryCode');
        $sheet->setCellValue('D2', 'regionCode');
        $sheet->setCellValue('E2', 'regionName');
        $sheet->setCellValue('F2', 'cityName');
        $sheet->setCellValue('G2', 'zipCode');
        $sheet->setCellValue('H2', 'isoCode');
        $sheet->setCellValue('I2', 'postalCode');
        $sheet->setCellValue('J2', 'latitude');
        $sheet->setCellValue('K2', 'longitude');
        $sheet->setCellValue('L2', 'metroCode');
        $sheet->setCellValue('M2', 'areaCode');
        $sheet->setCellValue('N2', 'timezone');


        $sheet->getStyle('A2:N2')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '808080'
                    ]
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);

        $coordN = count($IPs_page_unic) + 2;

        $sheet->getStyle('A2:N2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

                $sheet->getStyle('A3:N' . $coordN)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ]
                ]);

        $i = 3;
        foreach ($IPs_page_unic as $value) {
            $sheet->setCellValue('A' . $i, $value);
            $LocationData = Location::get($value);
            $sheet->setCellValue('B' . $i, $LocationData->countryName);
            $sheet->setCellValue('C' . $i, $LocationData->countryCode);
            $sheet->setCellValue('D' . $i, $LocationData->regionCode);
            $sheet->setCellValue('E' . $i, $LocationData->regionName);
            $sheet->setCellValue('F' . $i, $LocationData->cityName);
            $sheet->setCellValue('G' . $i, $LocationData->zipCode);
            $sheet->setCellValue('H' . $i, $LocationData->isoCode);
            $sheet->setCellValue('I' . $i, $LocationData->postalCode);
            $sheet->setCellValue('J' . $i, $LocationData->latitude);
            $sheet->setCellValue('K' . $i, $LocationData->longitude);
            $sheet->setCellValue('L' . $i, $LocationData->metroCode);
            $sheet->setCellValue('M' . $i, $LocationData->areaCode);
            $sheet->setCellValue('N' . $i, $LocationData->timezone);
            $i++;
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

}
