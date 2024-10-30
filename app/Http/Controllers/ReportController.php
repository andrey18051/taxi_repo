<?php

namespace App\Http\Controllers;

use App\Mail\DriverInfo;
use App\Mail\DriverReportsInfo;
use App\Models\BonusBalance;
use App\Models\BonusBalancePas1;
use App\Models\BonusBalancePas2;
use App\Models\BonusBalancePas4;
use App\Models\BonusTypes;
use App\Models\IP;
use App\Models\NewsList;
use App\Models\Order;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function bonusReport(Request $request)
    {
        $user = User::where('email', $request->email)->get();

//        dd($bonusRecords->toArray());

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Бонусы');
        $sheet = $spreadsheet->getActiveSheet();

        $bonusRecords = BonusBalance::where('users_id', $user->toArray()[0]['id'])->get();
        if ($bonusRecords != null) {
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список движения бонусов');
            $sheet->setCellValue('C1', 'клиента ' . $user->toArray()[0]['name']) ;
            $sheet->setCellValue('D1', $user->toArray()[0]['email']);

            $sheet->setCellValue('A2', 'N п/п');
            $sheet->setCellValue('B2', 'UID');
            $sheet->setCellValue('C2', 'Операция');
            $sheet->setCellValue('D2', 'Зачисление');
            $sheet->setCellValue('E2', 'Списание');
            $sheet->setCellValue('F2', 'Блокировка');
            $sheet->setCellValue('G2', 'Создано');
            $sheet->setCellValue('H2', 'Обновлено');


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

            $coordN = count($bonusRecords) + 2;

            $sheet->getStyle('A2:H2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:H' . $coordN)->applyFromArray([
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
            $bonusAdd = 0;
            $bonusDel = 0;
            $bonusBloke = 0;

            foreach ($bonusRecords as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                if ($value->orderwebs_id != 0) {
                    $sheet->setCellValue('B' . $i, Orderweb::find($value->orderwebs_id)->dispatching_order_uid);
                }
                $sheet->setCellValue('C' . $i, BonusTypes::find($value->bonus_types_id)->name);
                $sheet->setCellValue('D' . $i, $value->bonusAdd);
                $bonusAdd += $value->bonusAdd;
                $sheet->setCellValue('E' . $i, $value->bonusDel);
                $bonusDel += $value->bonusDel;
                $sheet->setCellValue('F' . $i, $value->bonusBloke);
                $bonusBloke += $value->bonusBloke;
                $sheet->setCellValue('G' . $i, date('d-m-Y H:m:s', strtotime( $value->created_at)));
                $sheet->setCellValue('H' . $i, date('d-m-Y H:m:s', strtotime( $value->updated_at)));
                $i++;
            }
            $sheet->setCellValue('C' . $i, "ИТОГО");
            $sheet->setCellValue('D' . $i, $bonusAdd);
            $sheet->setCellValue('E' . $i, $bonusDel);
            $sheet->setCellValue('F' . $i, $bonusBloke);
            $i++;
            $sheet->setCellValue('C' . $i, "Баланс");
            $sheet->setCellValue('D' . $i, $bonusAdd - $bonusDel - $bonusBloke);
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период');
        }
        // Добавляем лист PAS 1
        $newSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'PAS 1');
        $spreadsheet->addSheet($newSheet);

        // Переключаемся на PAS 1
        $spreadsheet->setActiveSheetIndexByName('PAS 1');
        $sheet = $spreadsheet->getActiveSheet();
        $bonusRecords = BonusBalancePas1::where('users_id', $user->toArray()[0]['id'])->get();
        if ($bonusRecords != null) {
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список движения бонусов');
            $sheet->setCellValue('C1', 'клиента ' . $user->toArray()[0]['name']) ;
            $sheet->setCellValue('D1', $user->toArray()[0]['email']);

            $sheet->setCellValue('A2', 'N п/п');
            $sheet->setCellValue('B2', 'UID');
            $sheet->setCellValue('C2', 'Операция');
            $sheet->setCellValue('D2', 'Зачисление');
            $sheet->setCellValue('E2', 'Списание');
            $sheet->setCellValue('F2', 'Блокировка');
            $sheet->setCellValue('G2', 'Создано');
            $sheet->setCellValue('H2', 'Обновлено');


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

            $coordN = count($bonusRecords) + 2;

            $sheet->getStyle('A2:H2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:H' . $coordN)->applyFromArray([
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
            $bonusAdd = 0;
            $bonusDel = 0;
            $bonusBloke = 0;

            foreach ($bonusRecords as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                if ($value->orderwebs_id != 0) {
                    $sheet->setCellValue('B' . $i, Orderweb::find($value->orderwebs_id)->dispatching_order_uid);
                }
                $sheet->setCellValue('C' . $i, BonusTypes::find($value->bonus_types_id)->name);
                $sheet->setCellValue('D' . $i, $value->bonusAdd);
                $bonusAdd += $value->bonusAdd;
                $sheet->setCellValue('E' . $i, $value->bonusDel);
                $bonusDel += $value->bonusDel;
                $sheet->setCellValue('F' . $i, $value->bonusBloke);
                $bonusBloke += $value->bonusBloke;
                $sheet->setCellValue('G' . $i, date('d-m-Y H:m:s', strtotime( $value->created_at)));
                $sheet->setCellValue('H' . $i, date('d-m-Y H:m:s', strtotime( $value->updated_at)));
                $i++;
            }
            $sheet->setCellValue('C' . $i, "ИТОГО");
            $sheet->setCellValue('D' . $i, $bonusAdd);
            $sheet->setCellValue('E' . $i, $bonusDel);
            $sheet->setCellValue('F' . $i, $bonusBloke);
            $i++;
            $sheet->setCellValue('C' . $i, "Баланс");
            $sheet->setCellValue('D' . $i, $bonusAdd - $bonusDel - $bonusBloke);
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период');
        }

        // Добавляем  лист PAS 2
        $newSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'PAS 2');
        $spreadsheet->addSheet($newSheet);

        // Переключаемся на PAS 2
        $spreadsheet->setActiveSheetIndexByName('PAS 2');
        $sheet = $spreadsheet->getActiveSheet();
        $bonusRecords = BonusBalancePas2::where('users_id', $user->toArray()[0]['id'])->get();
        if ($bonusRecords != null) {
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список движения бонусов');
            $sheet->setCellValue('C1', 'клиента ' . $user->toArray()[0]['name']) ;
            $sheet->setCellValue('D1', $user->toArray()[0]['email']);

            $sheet->setCellValue('A2', 'N п/п');
            $sheet->setCellValue('B2', 'UID');
            $sheet->setCellValue('C2', 'Операция');
            $sheet->setCellValue('D2', 'Зачисление');
            $sheet->setCellValue('E2', 'Списание');
            $sheet->setCellValue('F2', 'Блокировка');
            $sheet->setCellValue('G2', 'Создано');
            $sheet->setCellValue('H2', 'Обновлено');


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

            $coordN = count($bonusRecords) + 2;

            $sheet->getStyle('A2:H2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:H' . $coordN)->applyFromArray([
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
            $bonusAdd = 0;
            $bonusDel = 0;
            $bonusBloke = 0;

            foreach ($bonusRecords as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                if ($value->orderwebs_id != 0) {
                    $sheet->setCellValue('B' . $i, Orderweb::find($value->orderwebs_id)->dispatching_order_uid);
                }
                $sheet->setCellValue('C' . $i, BonusTypes::find($value->bonus_types_id)->name);
                $sheet->setCellValue('D' . $i, $value->bonusAdd);
                $bonusAdd += $value->bonusAdd;
                $sheet->setCellValue('E' . $i, $value->bonusDel);
                $bonusDel += $value->bonusDel;
                $sheet->setCellValue('F' . $i, $value->bonusBloke);
                $bonusBloke += $value->bonusBloke;
                $sheet->setCellValue('G' . $i, date('d-m-Y H:m:s', strtotime( $value->created_at)));
                $sheet->setCellValue('H' . $i, date('d-m-Y H:m:s', strtotime( $value->updated_at)));
                $i++;
            }
            $sheet->setCellValue('C' . $i, "ИТОГО");
            $sheet->setCellValue('D' . $i, $bonusAdd);
            $sheet->setCellValue('E' . $i, $bonusDel);
            $sheet->setCellValue('F' . $i, $bonusBloke);
            $i++;
            $sheet->setCellValue('C' . $i, "Баланс");
            $sheet->setCellValue('D' . $i, $bonusAdd - $bonusDel - $bonusBloke);
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период');
        }

        // Добавляем  лист PAS 4
        $newSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'PAS 4');
        $spreadsheet->addSheet($newSheet);

        // Переключаемся на PAS 4
        $spreadsheet->setActiveSheetIndexByName('PAS 4');
        $sheet = $spreadsheet->getActiveSheet();

        $bonusRecords = BonusBalancePas4::where('users_id', $user->toArray()[0]['id'])->get();
        if ($bonusRecords != null) {
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);

            $sheet->setCellValue('B1', 'Это список движения бонусов');
            $sheet->setCellValue('C1', 'клиента ' . $user->toArray()[0]['name']) ;
            $sheet->setCellValue('D1', $user->toArray()[0]['email']);

            $sheet->setCellValue('A2', 'N п/п');
            $sheet->setCellValue('B2', 'UID');
            $sheet->setCellValue('C2', 'Операция');
            $sheet->setCellValue('D2', 'Зачисление');
            $sheet->setCellValue('E2', 'Списание');
            $sheet->setCellValue('F2', 'Блокировка');
            $sheet->setCellValue('G2', 'Создано');
            $sheet->setCellValue('H2', 'Обновлено');


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

            $coordN = count($bonusRecords) + 2;

            $sheet->getStyle('A2:H2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');

            $sheet->getStyle('A3:H' . $coordN)->applyFromArray([
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
            $bonusAdd = 0;
            $bonusDel = 0;
            $bonusBloke = 0;

            foreach ($bonusRecords as $value) {
                $sheet->setCellValue('A' . $i, $i - 2);
                if ($value->orderwebs_id != 0) {
                    $sheet->setCellValue('B' . $i, Orderweb::find($value->orderwebs_id)->dispatching_order_uid);
                }
                $sheet->setCellValue('C' . $i, BonusTypes::find($value->bonus_types_id)->name);
                $sheet->setCellValue('D' . $i, $value->bonusAdd);
                $bonusAdd += $value->bonusAdd;
                $sheet->setCellValue('E' . $i, $value->bonusDel);
                $bonusDel += $value->bonusDel;
                $sheet->setCellValue('F' . $i, $value->bonusBloke);
                $bonusBloke += $value->bonusBloke;
                $sheet->setCellValue('G' . $i, date('d-m-Y H:m:s', strtotime( $value->created_at)));
                $sheet->setCellValue('H' . $i, date('d-m-Y H:m:s', strtotime( $value->updated_at)));
                $i++;
            }
            $sheet->setCellValue('C' . $i, "ИТОГО");
            $sheet->setCellValue('D' . $i, $bonusAdd);
            $sheet->setCellValue('E' . $i, $bonusDel);
            $sheet->setCellValue('F' . $i, $bonusBloke);
            $i++;
            $sheet->setCellValue('C' . $i, "Баланс");
            $sheet->setCellValue('D' . $i, $bonusAdd - $bonusDel - $bonusBloke);
        } else {
            $sheet->setCellValue('A1', 'Нет данных в период');
        }


        $reportIP_path = Storage::path('public/reports/reportBonus.xlsx');
        $reportIP = new Xlsx($spreadsheet);
        $reportIP->save($reportIP_path);
        return response()->download($reportIP_path);
    }


    public function reportBalanceDriver()
    {
        // Получаем данные водителей и их баланс
        $dataDriverArray = (new FCMController)->driverAll();
        $balanceRecords = (new FCMController)->driverAllBalanceRecord();

        // Группировка записей баланса по driver_uid
        $groupedBalanceRecords = [];
        foreach ($balanceRecords as $balanceRecord) {
            $driverUid = $balanceRecord['driver_uid'];
            if (!isset($groupedBalanceRecords[$driverUid])) {
                $groupedBalanceRecords[$driverUid] = [];
            }
            $groupedBalanceRecords[$driverUid][] = $balanceRecord;
        }

        // Создаем новую таблицу
        $spreadsheet = new Spreadsheet();
        if (is_array($dataDriverArray)) {
            foreach ($dataDriverArray as $index => $driver) {
                // Получаем полные данные о водителе
                $driverData = (new FCMController)->readFullUsersFirestore($driver['uid']);
                $driverUid = $driver['uid'];

                if (isset($driverData['driverNumber']) && isset($groupedBalanceRecords[$driverUid])) {
                    // Сортировка записей баланса по убыванию даты
                    usort($groupedBalanceRecords[$driverUid], function ($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });

                    // Создаем новую вкладку для каждого водителя
                    if ($index === 0) {
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle($driverData['driverNumber']); // первая вкладка
                    } else {
                        $sheet = $spreadsheet->createSheet();
                        $sheet->setTitle($driverData['driverNumber']); // остальные вкладки
                    }

                    // Добавляем данные водителя в верхней части каждой вкладки
                    $sheet->setCellValue('A1', 'Позывной');
                    $sheet->setCellValue('B1', $driverData['driverNumber'] ?? '');

                    $sheet->setCellValue('A2', 'UID Гугл');
                    $sheet->setCellValue('B2', $driverData['uid'] ?? '');

                    $sheet->setCellValue('A3', 'Телефон');
                    $sheet->setCellValue('B3', $driverData['phoneNumber'] ?? '');

                    $sheet->setCellValue('A4', 'ФИО');
                    $sheet->setCellValue('B4', $driverData['name'] ?? '');

                    $sheet->setCellValue('A5', 'Аккаунт проверен');
                    $sheet->setCellValue('B5', $driverData['verified'] ? 'Да' : 'Нет');

                    $sheet->setCellValue('A6', 'Email');
                    $sheet->setCellValue('B6', $driverData['email'] ?? '');


                    // Автоматическая подгонка размера колонок под длину содержимого
                    foreach (range('A', 'H') as $columnID) {
                        $sheet->getColumnDimension($columnID)->setAutoSize(true);
                    }

                    // Установка заголовков для таблицы балансов
                    $sheet->setCellValue('A9', 'Дата');
                    $sheet->setCellValue('B9', 'Тип операции');
                    $sheet->setCellValue('C9', 'Заказ');
                    $sheet->setCellValue('D9', 'Сумма');
                    $sheet->setCellValue('E9', 'Комиссия');
                    $sheet->setCellValue('F9', 'Текущий баланс');
                    $sheet->setCellValue('G9', 'Статус');
                    $sheet->setCellValue('H9', 'Админ');

                    // Применение стилей к заголовкам

                    $sheet->getStyle('A9:H9')->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '808080'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                    ]);

                    $sheet->getStyle('A9:H9')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('7FFFD4');


                    // Заполнение данными о балансе для каждого водителя (в порядке убывания даты)
                    $row = 10; // Начальная строка для данных о балансе
                    foreach ($groupedBalanceRecords[$driverUid] as $balance) {
                        $sheet->setCellValue('A' . $row, $balance['created_at'] ?? '');
                        if (isset($balance['selectedTypeCode'])) {
                            if ($balance['selectedTypeCode'] != 0) {
                                $selectedTypeCode = $balance['selectedTypeCode'];
                            } else {
                                $selectedTypeCode = '';
                            }
                        } else {
                            $selectedTypeCode = '';
                        };
                        // Проверяем наличие маршрута и формируем текст для selectedTypeCode
                        if (isset($balance['routefrom'], $balance['routeto'])) {
                            $selectedTypeCode = "Маршрут от " . $balance['routefrom'] . " до " . $balance['routeto'];
                        }

// Записываем значение в ячейку
                        $sheet->setCellValue('B' . $row, $selectedTypeCode);


                        $sheet->setCellValue('C' . $row, $balance['web_cost'] ?? '');
                        $sheet->setCellValue('D' . $row, $balance['amount'] ?? '');

// Проверка на равенство amount и commission
                        if (isset($balance['commission'], $balance['amount'])) {
                            $commission = ''; // Не выводим комиссию, если она равна сумме
                        } elseif (isset($balance['commission'])) {
                            $commission = $balance['commission'];
                        } else {
                            $commission = ''; // На случай, если комиссия не задана
                        }

                        $sheet->setCellValue('E' . $row, $commission);


                        $sheet->setCellValue('F' . $row, $balance['current_balance'] ?? '');
                        $status = '';
                        switch ($balance['status']) {
                            case "payment_nal":
                                $status = 'Пополнение админом';
                                break;
                            case "holdDownReturnToBalance":
                                $status = 'Отмена холда по заявке на вывод с баланса';
                                break;
                            case "holdDownComplete":
                                $status = 'Выполнение заявки на вывод с баланса';
                                break;
                            case "holdDown":
                                $status = 'Холд по заявке на вывод с баланса';
                                break;
                            case "hold":
                                $status = 'Холд при взятии заказа';
                                break;
                            case "return":
                                $status = 'Возврат холда при отказе от взятого заказа';
                                break;
                            case "delete":
                                $status = 'Списание комиссии за взятый заказ';
                                break;
                        }
                        $sheet->setCellValue('G' . $row, $status ?? '');
                        $sheet->setCellValue('H' . $row, $balance['admin_name'] ?? '');
                        $sheet->getStyle('A' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                        ]);

                        $sheet->getStyle('B' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::HORIZONTAL_LEFT,
                                'wrapText' => true,
                            ],
                        ]);
                        $sheet->getStyle('C' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::HORIZONTAL_RIGHT,
                                'wrapText' => true,
                            ],
                        ]);
                        $sheet->getStyle('D' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::HORIZONTAL_RIGHT,
                                'wrapText' => true,
                            ],
                        ]);
                        $sheet->getStyle('E' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::HORIZONTAL_RIGHT,
                                'wrapText' => true,
                            ],
                        ]);

                        $sheet->getStyle('F' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::HORIZONTAL_LEFT,
                                'wrapText' => true,
                            ],
                        ]);

                        $sheet->getStyle('G' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                        ]);
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '808080'],
                                ],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                        ]);
                        $row++;
                    }
                }
            }
        }

        // Сохранение файла
        $writer = new Xlsx($spreadsheet);
        $fileName = 'drivers_balance_report.xlsx';
        $path = storage_path('app/public/reports/' . $fileName); // Указывает путь к папке storage/app/
        $writer->save($path); // Сохраните файл в storage/app/

        $formatTime = (new FCMController)->currentKievDateTime();
        $subject_email =  "Отчет по балансу водителей на " .  $formatTime;


        $paramsAdmin = [
            'subject' => $subject_email,
        ];


        Mail::to('taxi.easy.ua@gmail.com')->send(new DriverReportsInfo($paramsAdmin));

        Mail::to('cartaxi4@gmail.com')->send(new DriverReportsInfo($paramsAdmin));

        // Удаление файла после отправки
        if (file_exists($path)) {
            unlink($path); // Удаляем файл
        }
        return "Успех";
    }



}
