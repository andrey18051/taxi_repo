<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class IPReportService
{
    protected $email;
    protected $pageFilter;

    public function __construct(string $email, string $pageFilter = 'PAS')
    {
        $this->email = $email;
        $this->pageFilter = $pageFilter;
    }

    public function send()
    {
        try {
            $records = DB::table('i_p_s')
                ->where('page', 'like', '%' . $this->pageFilter . '%')
                ->where('created_at', '>=', Carbon::now()->subDay())
                ->orderBy('created_at', 'desc')
                ->get();

            if ($records->isEmpty()) {
                Log::info('Нет записей для отчета');
                return;
            }

            $htmlTable = $this->generateHtmlTable($records);
            $csvContent = $this->generateCsv($records);

            Mail::send('emails.ip_report', ['htmlTable' => $htmlTable, 'records' => $records], function ($message) use ($csvContent) {
                $message->to($this->email)
                    ->cc('cartaxi4@gmail.com')
                    ->subject('Отчет по IP записям за ' . Carbon::now()->subDay()->format('d.m.Y'))
                    ->attachData($csvContent, 'ip_report_' . Carbon::now()->format('Y-m-d') . '.csv', [
                        'mime' => 'text/csv',
                    ]);
            });

            Log::info('Отчет отправлен на ' . $this->email . ' и cartaxi4@gmail.com, записей: ' . $records->count());

        } catch (\Exception $e) {
            Log::error('Ошибка при отправке отчета: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateHtmlTable($records): string
    {
        if ($records->isEmpty()) {
            return '<p style="color: #999;">Нет записей за последние сутки</p>';
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-family: Arial, sans-serif;
                }
                th {
                    background-color: #4CAF50;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                td {
                    padding: 10px;
                    border: 1px solid #ddd;
                    text-align: left;
                }
                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                tr:hover {
                    background-color: #ddd;
                }
                .header {
                    background-color: #f8f9fa;
                    padding: 20px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    text-align: center;
                }
                .footer {
                    margin-top: 20px;
                    padding: 10px;
                    text-align: center;
                    font-size: 12px;
                    color: #999;
                }
                .count {
                    font-size: 18px;
                    font-weight: bold;
                    color: #4CAF50;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>📊 Отчет по IP записям</h2>
                <p>Фильтр: <strong>' . htmlspecialchars($this->pageFilter) . '</strong></p>
                <p>Период: <strong>' . Carbon::now()->subDay()->format('d.m.Y H:i') . ' - ' . Carbon::now()->format('d.m.Y H:i') . '</strong></p>
                <p>Всего записей: <span class="count">' . $records->count() . '</span></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>IP Адрес</th>
                        <th>Email</th>
                        <th>User Agent</th>
                        <th>Страница</th>
                        <th>Дата создания</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($records as $index => $record) {
            $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td><code>' . htmlspecialchars($record->IP_ADDR) . '</code></td>
                    <td>' . htmlspecialchars($record->email ?? '-') . '</td>
                    <td style="font-size: 12px;">' . htmlspecialchars($record->user_agent ?? '-') . '</td>
                    <td>' . htmlspecialchars($record->page ?? '-') . '</td>
                    <td>' . Carbon::parse($record->created_at)->format('d.m.Y H:i:s') . '</td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>
            <div class="footer">
                <p>Отчет сгенерирован автоматически | ' . Carbon::now()->format('d.m.Y H:i:s') . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function generateCsv($records)
    {
        $csv = "IP Адрес,Email,User Agent,Страница,Дата создания\n";

        foreach ($records as $record) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $record->IP_ADDR,
                $record->email ?? '',
                str_replace(',', ';', $record->user_agent ?? ''),
                $record->page ?? '',
                $record->created_at
            );
        }

        return $csv;
    }
}
