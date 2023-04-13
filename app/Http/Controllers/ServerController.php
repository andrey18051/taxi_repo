<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ServerController extends Controller
{

    /**
     * URL API taxi
     * Одесса http://31.43.107.151:7303
     * Киев http://167.235.113.231:7306
     * Киев http://134.249.181.173:7208
     * Киев http://91.205.17.153:7208
     */

    public function connectAPIInfo(string $host)
    {

        try {


            $output = shell_exec("ping $host");
            return iconv("cp866", "utf-8", $output);

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function serverInfo()
    {

        $arrServer[0] = self::connectAPIInfo('31.43.107.151');
        $arrServer[1] = self::connectAPIInfo('167.235.113.231');
        $arrServer[2] = self::connectAPIInfo('134.249.181.173');
        $arrServer[3] = self::connectAPIInfo('91.205.17.153');

        return $arrServer;
    }
}
