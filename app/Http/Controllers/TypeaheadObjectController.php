<?php
namespace App\Http\Controllers;
use App\Models\Config;
use App\Models\Objecttaxi;
use App\Models\Street;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TypeaheadObjectController extends Controller
{
    public function index()
    {
        return view('home');
    }

    public function autocompleteSearch(Request $request)
    {
        $query = $request->get('query');

        $username = config('app.username');
        $password = hash('SHA512', config('app.password'));
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);


        $url = config('app.taxi2012Url') . '/api/geodata/objects';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($response,true);

        $svd = Config::where('id', '1')->first();
        //Проверка версии геоданных и обновление или создание базы адресов
        if ($json_arr['version_date'] !== $svd->objectVersionDate || Objecttaxi::all()->count() === 0) {
            $svd->objectVersionDate = $json_arr['version_date'];
            $svd->save();
            echo $svd->objectVersionDate;
            DB::table('objecttaxis')->truncate();
            $i = 0;
            do {
                $streets = $json_arr['geo_object'][$i]["localizations"];
                foreach ($streets as $val) {
                    if ($val["locale"] == "UK") {
                        $objects = new Objecttaxi();
                        $objects->name = $val['name'];
                        $objects->save();

                    }
                }
                $i++;
            }
            while ($i < count($json_arr['geo_object'])) ;

        }
        $filterResult = Objecttaxi::where('name', 'LIKE', '%' . $query . '%')->get();
        return  response()->json($filterResult);
    }
}
