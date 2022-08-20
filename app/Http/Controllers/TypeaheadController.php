<?php
namespace App\Http\Controllers;
use App\Models\Config;
use App\Models\Street;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TypeaheadController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function autocompleteSearch(Request $request)
    {
        $query = $request->get('query');

        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = config('app.taxi2012Url') . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($json_str, true);

        /**
         * Проверка версии геоданных и обновление или создание базы адресов
         * $json_arr['version_date'] - текущая версия улиц в базе
         * config('app.streetVersionDate') - дата версии в конфиге
         */

        $svd = Config::where('id', '1')->first();
        //Проверка версии геоданных и обновление или создание базы адресов
        if ($json_arr['version_date'] !== $svd->streetVersionDate || Street::all()->count() === 0) {
            $svd->streetVersionDate = $json_arr['version_date'];
            $svd->save();
            echo $svd->streetVersionDate;
            DB::table('streets')->truncate();
            $i = 0;
            do {
                $streets = new Street();
                $streets->name = $json_arr['geo_street'][$i]['name'];
                $streets->old_name = $json_arr['geo_street'][$i]['old_name'];
                $streets->save();
                $i++;
            }
            while ($i < count($json_arr['geo_street']));
        }

        $filterResult = Street::where('name', 'LIKE', '%' . $query . '%')->get();

        return  response()->json($filterResult);
    }
}
