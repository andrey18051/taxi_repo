<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use SebastianBergmann\Diff\Exception;

class ServerController extends Controller
{

    /**
     * URL API taxi
     * Одесса http://188.190.245.102:7303
     * Киев http://167.235.113.231:7306
     * Киев http://134.249.181.173:7208
     * Киев http://91.205.17.153:7208
     */

    public const SERVERS_PING = [
        '31.43.107.151',
        '167.235.113.231',
        '188.40.143.61',
        '134.249.181.173',
        '91.205.17.153',
    ];
    public const SERVERS_CONNECT = [
        '188.190.245.102:7303 ',
        '167.235.113.231:7306',
        '134.249.181.173:7208',
        '91.205.17.153:7208',
    ];

    public function pingInfo(string $host)
    {
        $output = shell_exec("ping $host");
//            $output = shell_exec("ping -c 1 $host");
        return iconv("cp866", "utf-8", $output);
    }

    public function connectInfo($domain): string
    {
        $curlInit = curl_init($domain);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) {
            return "Подключен";
        } else {
            $messageAdmin = "Ошибка подключения к серверу $domain";
            $alarmMessage = new TelegramController();
            $alarmMessage->sendAlarmMessage($messageAdmin);
            return "Ошибка подключения";
        }
    }

    public function serverInfo(): array
    {
        $i = 0;
        $arrServer = null;
        foreach (self::SERVERS_CONNECT as $value) {
            $arrServer[$i++] = $value;
            $arrServer[$i++] = self::connectInfo($value);
        }
        foreach (self::SERVERS_PING as $value) {
            $arrServer[$i++] = $value;
            $arrServer[$i++] = self::pingInfo($value);
        }

        return $arrServer;
    }
}
