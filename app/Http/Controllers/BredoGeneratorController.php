<?php

namespace App\Http\Controllers;

use App\Models\KeyWord;
use App\Models\NewsList;
use App\Models\Order;
use App\Models\Quite;
use App\Models\TextString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BredoGeneratorController extends Controller
{
    /**
     * @throws \Exception
     */
    public function textGenerate(): array
    {
        /**
         * Новости
         */

        $keywordsObj = KeyWord::all(); // массив ключевых слов
        $i = 0;
        foreach ($keywordsObj as $value) {
            $keyWordsArr[$i++] = $value["name"];
        }

        $textArrObj = TextString::all(); // массив дополнительных строк для новостей
        $i = 0;
        foreach ($textArrObj as $value) {
            $textArrNews[$i++] = $value["name"];
        }


        $quitesArr = Quite::all()->toArray(); //Заголовки

        $shortNews = "📢 " . mb_convert_case($keyWordsArr[rand(0, count($keyWordsArr) - 1)], MB_CASE_TITLE, "UTF-8") . ". "
            . $quitesArr[rand(0, count($quitesArr) - 1)]['name'];

        $newsArr = NewsList::all()->toArray(); //Старые новости

        $fullNews = "🚧 ";

        while (strlen($fullNews) <= 2000) {
            //разбили старую новсть на строки
            $fullNewsArr = explode('.', $newsArr[rand(0, count($newsArr) - 1)]['full']);

            //Строка из старой новости
            $oldNewsString = $fullNewsArr[rand(0, count($fullNewsArr) - 1)];

            //Замена ключевых слов
            $oldNewsStringOld = $oldNewsString;
            foreach ($keyWordsArr as $value) {
                $oldNewsString = str_replace($value, $keyWordsArr[rand(0, count($keyWordsArr) - 1)], $oldNewsString);
            }

            if (strcmp($oldNewsString, $oldNewsStringOld) === 0) {
                $oldNewsString = $oldNewsString . " " . $keyWordsArr[rand(0, count($keyWordsArr) - 1)];
            }
            //Формирование новости
 //           $fullNews = $fullNews . mb_convert_case($keyWordsArr[rand(0, count($keyWordsArr) - 1)], MB_CASE_TITLE, "UTF-8") . ". ";
            $fullNews = ucfirst($fullNews)
                . ucfirst($oldNewsString) . ". "
                . ucfirst($textArrNews[rand(0, count($textArrNews) - 1)]) . ". ";
        }

        $author = "🚖 Служба Таксі Лайт Юа";

        return [$shortNews, $fullNews, $author];
    }

    public function allNews()
    {
        return response()->json(NewsList::all());
    }

    public function breakingNews($id)
    {
        IPController::getIP("/breakingNews/$id");
        $news = NewsList::where('id', $id)->first();
        $randomNewsArr = self::randomNews($id);
        return view('taxi.breakingNews', ['news' => $news, 'randomNewsArr' => $randomNewsArr]);
    }

    /**
     * @throws \Exception
     */
    public function randomNews($id): array
    {
        $news = NewsList::where('id', '<>', $id)->get();

        $randomNewsArr[] = null;
        $i = 0;
        foreach ($news as $value) {
            $newsArr[$i]["id"] = $value["id"];
            $newsArr[$i++]["short"] = $value["short"];
        }

        for ($i = 0; $i <= 4; $i++) {
            $randomNewsArr[$i] = $newsArr[random_int(0, count($newsArr) - 1)];
        }
        return $randomNewsArr;
    }
}
