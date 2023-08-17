<?php

namespace App\Http\Controllers;

use App\Models\IP;
use App\Models\NewsList;
use App\Models\Order;
use App\Models\Orderweb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Stevebauman\Location\Facades\Location;

class ReportController extends Controller
{
    public function reportIpRoute(Request $request)
    {

        $dateFrom = date('Y-m-d', strtotime($request->dateFrom . '-1 day'));
        $dateTo = date('Y-m-d', strtotime($request->dateTo . '+1 day'));

        $reportIP_path = Storage::path('public/reports/reportIpRoute.xlsx');


        /**
         * IP расчеты маршрутов
         */
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('IP расчеты маршрутов');
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;

        $orders = Order::whereBetween('created_at', [$dateFrom, $dateTo])->get();

//        foreach ($orders as $value) {
//            if ($value->IP_ADDR !== null) {
//                switch ($value->IP_ADDR) {
//                    case '31.202.139.47':
//                        break;
//                    case '127.0.0.1':
//                        break;
//                    case null:
//                        break;
//                    default:
//                        $orders_unic[$i] = $value->IP_ADDR;
//                        $i++;
//                };
//            }
//        }
        if($orders !== null) {
//            $orders_unic = array_unique($orders_unic);

            $sheet->setCellValue('B' . 1, 'Расчеты с ' . $dateFrom . ' по ' . $dateTo);

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

            $coordN = $orders->count() + 2;


            $sheet->getStyle('A2:O2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A2:O' . $coordN)->applyFromArray([
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
            $sheet->getColumnDimension('O')->setAutoSize(true);
            $sheet->getColumnDimension('P')->setAutoSize(true);

            $sheet->setCellValue('A2', 'N');
            $sheet->setCellValue('B2', 'Заказчик');
            $sheet->setCellValue('C2', 'Телефон');
            $sheet->setCellValue('D2', 'Универсал');
            $sheet->setCellValue('E2', 'Микроавтобус');
            $sheet->setCellValue('F2', 'Премиум');
            $sheet->setCellValue('G2', 'Тариф');
            $sheet->setCellValue('H2', 'По городу');
            $sheet->setCellValue('I2', 'Откуда');
            $sheet->setCellValue('J2', '');
            $sheet->setCellValue('K2', 'Куда');
            $sheet->setCellValue('L2', '');
            $sheet->setCellValue('M2', 'Способ оплаты');
            $sheet->setCellValue('N2', 'IP_ADDR');
            $sheet->setCellValue('O2', 'Дата и время');

//dd($orders);
            foreach ($orders  as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                $sheet->setCellValue('B' . $i, $value->user_full_name );
                $sheet->setCellValue('C' . $i, $value->user_phone );
                if ($value->wagon !== 0) {
                    $sheet->setCellValue('D' . $i, $value->wagon );
                }
                if ($value->minibus !== 0) {
                    $sheet->setCellValue('E' . $i, $value->minibus );
                }
                if ($value->premium !== 0) {
                    $sheet->setCellValue('F' . $i, $value->premium );
                }
                $sheet->setCellValue('G' . $i, $value->flexible_tariff_name );
                if ($value->route_undefined  == 1) {
                    $sheet->setCellValue('H' . $i, 'Да');
                }
                $sheet->setCellValue('I' . $i, $value->routefrom );
                $sheet->setCellValue('J' . $i,$value->routefromnumber );
                $sheet->setCellValue('K' . $i, $value->routeto );
                $sheet->setCellValue('L' . $i, $value->routetonumber );
                if ($value->payment_type  == 1) {
                    $sheet->setCellValue('M' . $i, 'Безналичные');
                } else {
                    $sheet->setCellValue('M' . $i, 'Наличные');
                }
                $sheet->setCellValue('N' . $i, $value->IP_ADDR );
                $sheet->setCellValue('O' . $i, $value->created_at );

                $i++;
            }
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

    public function reportIpPage(Request $request)
    {

        $dateFrom = date('Y-m-d', strtotime($request->dateFrom . '-1 day'));
        $dateTo = date('Y-m-d', strtotime($request->dateTo . '+1 day'));

        $reportIP_path = Storage::path('public/reports/reportIpPage.xlsx');

        /**
         * IP и страницы посещения
         */

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Заказы поездок');
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $IPs_page = null;
        $IPs = IP::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '127.0.0.1':
                        break;
                    case null:
                        break;
                    default:
                        $IPs_page[$i]['IP_ADDR'] = $value->IP_ADDR;
                        $IPs_page[$i]['page'] = $value->page;
                        $IPs_page[$i]['created_at'] = $value->created_at;
                        $i++;
                };
            }
        }
        if($i !== 0) {
            $sheet->setCellValue('B' . 1, 'Это список IP,которые посещали конкретные страницы'
                . 'с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));

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
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

    public function reportIpUniq(Request $request)
    {

        $dateFrom = date('Y-m-d', strtotime($request->dateFrom . '-1 day'));
        $dateTo = date('Y-m-d', strtotime($request->dateTo . '+1 day'));

        $reportIP_path = Storage::path('public/reports/reportIpUniq.xlsx');

        /**
         * IP всеx посетивших сайт
         */

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Уникальные IP');
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $IPs_page = null;

        $IPs = IP::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '31.202.139.47':
                        break;
                    case '127.0.0.1':
                        break;
                    case null:
                        break;
                    default:
                        $IPs_page[$i] = $value->IP_ADDR;
                        $i++;
                };
            }
        }

        if($i !== 0) {
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
            $sheet->getColumnDimension('O')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список уникальных IP,которые поcетили сайт');
            $sheet->setCellValue('C1', 'с ' . date('Y-m-d', strtotime($dateFrom . '+1 day'))) ;
            $sheet->setCellValue('D1', 'по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
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
            $sheet->setCellValue('O2', 'Всего посещений');


            $sheet->getStyle('A2:I2')->applyFromArray([
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

            $sheet->getStyle('A2:O2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:O' . $coordN)->applyFromArray([
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
                if($LocationData != null){
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
                    $sheet->setCellValue('O' . $i, IP::whereBetween('created_at', [$dateFrom, $dateTo])
                        ->where('IP_ADDR', $value)->count());
                    $i++;
                }

            }

        } else {
            $sheet->setCellValue('A1', 'Нет данных в период с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

    public function reportIpUniqShort(Request $request)
    {

        $dateFrom = date('Y-m-d', strtotime($request->dateFrom . '-1 day'));
        $dateTo = date('Y-m-d', strtotime($request->dateTo . '+1 day'));

        $reportIP_path = Storage::path('public/reports/reportIpUniq.xlsx');

        /**
         * IP всеx посетивших сайт
         */

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Уникальные IP');
        $sheet = $spreadsheet->getActiveSheet();

        $i = 0;
        $IPs_page = null;

        $IPs = IP::whereBetween('created_at', [$dateFrom, $dateTo])->get();

        foreach ($IPs as $value) {
            if ($value->IP_ADDR !== null) {
                switch ($value->IP_ADDR) {
                    case '31.202.139.47':
                        break;
                    case '127.0.0.1':
                        break;
                    case null:
                        break;
                    default:
                        $IPs_page[$i] = $value->IP_ADDR;
                        $i++;
                };
            }
        }

        if($i !== 0) {
            $IPs_page_unic = array_unique($IPs_page);
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список уникальных IP,которые поcетили сайт');

            $sheet->setCellValue('A2', 'IP');
            $sheet->setCellValue('B2', 'Всего посещений с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
            $sheet->setCellValue('C2', 'От куда ');


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

            $coordN = count($IPs_page_unic) + 2;

            $sheet->getStyle('A2:C2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:C' . $coordN)->applyFromArray([
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
//            dd($IPs_page_unic);
            foreach ($IPs_page_unic as $value) {
                $sheet->setCellValue('A' . $i, $value);
                $sheet->setCellValue('B' . $i, IP::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('IP_ADDR', $value)->count());
                $android_search = IP::whereBetween('created_at', [$dateFrom, $dateTo])->where('IP_ADDR', $value)->first()->page;
//                dd($android_search);
                $android = "Сайт";

                if (strpos($android_search, "PAS1")) {
                    $android = "Андроид приложение PAS1";
                }
                if (strpos($android_search, "PAS2")) {
                    $android = "Андроид приложение PAS2";
                }
                if (strpos($android_search, "PAS3")) {
                    $android = "Андроид приложение PAS3";
                }
                if (strpos($android_search, "PAS4")) {
                    $android = "Андроид приложение PAS4";
                }

                $sheet->setCellValue('C' . $i, $android);
                $i++;
            }

        } else {
            $sheet->setCellValue('A1', 'Нет данных в период с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
        }

        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

    public function reportIpOrder(Request $request)
    {

        $dateFrom = date('Y-m-d', strtotime($request->dateFrom . '-1 day'));
        $dateTo = date('Y-m-d', strtotime($request->dateTo . '+1 day'));

        $reportIP_path = Storage::path('public/reports/reportIpOrder.xlsx');

        /**
         * Заказы поездок
         */
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Заказы поездок');
        $sheet = $spreadsheet->getActiveSheet();


        $orderWebsCount = Orderweb::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        if ($orderWebsCount !== 0) {
            $orderWebs = Orderweb::whereBetween('created_at', [$dateFrom, $dateTo])->get();
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
            $sheet->getColumnDimension('O')->setAutoSize(true);


            $sheet->setCellValue('B1', 'Это список заказов');
            $sheet->setCellValue('C1', 'с ' . date('Y-m-d', strtotime($dateFrom . '+1 day'))) ;
            $sheet->setCellValue('D1', 'по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));

            $sheet->setCellValue('A2', 'N');
            $sheet->setCellValue('B2', 'Заказчик');
            $sheet->setCellValue('C2', 'Дополнительно, грн');
            $sheet->setCellValue('D2', 'Андроид');

            $sheet->setCellValue('E2', 'Тариф');
            $sheet->setCellValue('F2', 'По городу');
            $sheet->setCellValue('G2', 'Откуда');
            $sheet->setCellValue('H2', '');
            $sheet->setCellValue('I2', 'Куда');
            $sheet->setCellValue('J2', '');
            $sheet->setCellValue('K2', 'Способ оплаты');
            $sheet->setCellValue('L2', 'Итого стоимость поездки, грн');
            $sheet->setCellValue('M2', 'Идентификатор');
            $sheet->setCellValue('N2', 'Сервер');
            $sheet->setCellValue('O2', 'Дата и время');


            $sheet->getStyle('A2:O2')->applyFromArray([
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

            $coordN = count($orderWebs) + 2;

            $sheet->getStyle('A2:O2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:O' . $coordN)->applyFromArray([
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
            foreach ($orderWebs as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                $sheet->setCellValue('B' . $i, $value->user_full_name);
                $sheet->setCellValue('C' . $i, $value->add_cost);

                $sheet->setCellValue('D' . $i, $value->comment);

                $sheet->setCellValue('E' . $i, $value->flexible_tariff_name);
                if ($value->route_undefined == 1) {
                    $sheet->setCellValue('G' . $i, 'Да');
                }
                $sheet->setCellValue('F' . $i, $value->routefrom);
                $sheet->setCellValue('H' . $i, $value->routefromnumber);
                $sheet->setCellValue('I' . $i, $value->routeto);
                $sheet->setCellValue('J' . $i, $value->routetonumber);
                if ($value->payment_type  == 0 || $value->payment_type  == "") {
                    $sheet->setCellValue('K' . $i, 'Наличные');
                } else {
                    $sheet->setCellValue('K' . $i, 'Безналичные');
                }
                $sheet->setCellValue('L' . $i, $value->web_cost);
                $sheet->setCellValue('M' . $i, $value->dispatching_order_uid);
                $sheet->setCellValue('N' . $i, $value->server);
                $sheet->setCellValue('O' . $i, $value->created_at);
                $i++;
            }
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период с ' . date('Y-m-d', strtotime($dateFrom . '+1 day')) . ' по ' . date('Y-m-d', strtotime($dateTo . '-1 day')));
        }



        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }

    public function siteMap(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {

        Storage::delete('public/reports/sitemap.xml');
        Storage::copy('public/reports/sitemapOld.xml', 'public/reports/sitemap.xml');

        $siteMap_path = Storage::path('public/reports/sitemap.xml');
        for ($i = NewsList::all()->count(); $i >= 1; $i--) {
            $newIndexPage =
    "
    <url>
         <loc>https://m.easy-order-taxi.site/breakingNews/$i</loc>
         <changefreq>monthly</changefreq>
    </url>";
            Storage :: append('public/reports/sitemap.xml', $newIndexPage);
        }


        Storage::append('public/reports/sitemap.xml', ' </urlset>');
        $url = Storage::url('public/reports/sitemap.xml');
//dd(url('/public/sitemap.xml'));
//        Storage::copy('public/reports/sitemap.xml', '/public/sitemap.xml');
        return response()->download($siteMap_path);

    }
}
