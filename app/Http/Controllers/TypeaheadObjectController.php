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

        /*$url = config('app.taxi2012Url') . '/api/geodata/search';
        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'q' => $query, //Обязательный. Несколько букв для поиска объекта.
            'offset' => 0, //Смещение при выборке (сколько пропустить).
            'limit' => 1000, //Кол-во возвращаемых записей (предел).
            'transliteration' => true, //Разрешить транслитерацию запроса при поиске.
            'qwertySwitcher' => true, //Разрешить преобразование строки запроса в случае ошибочного набора с неверной раскладкой клавиатуры (qwerty). Например, «ghbdtn» - это «привет».
            'fields' => 'name', /*Данным параметром можно указать перечень требуемых параметров, которые будут возвращаться в ответе. Разделяются запятой.
                Возможные значения:
                * (возвращает все поля)
                name
                old_name
                houses
                lat
                lng
                locale
        ]);*/
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
                $objects = new Objecttaxi();
                $objects->name = $json_arr['geo_object'][$i]['name'];
                $objects->save();
                $i++;
            }
            while ($i < count($json_arr['geo_object']));
        }
        $filterResult = Objecttaxi::where('name', 'LIKE', '%' . $query . '%')->get();
        return  response()->json($filterResult);
    }
}
