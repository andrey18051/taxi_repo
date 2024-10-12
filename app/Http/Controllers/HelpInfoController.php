<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\HelpInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class HelpInfoController extends Controller
{
    public function index()
    {
        // Получаем все записи
        $helpInfos = HelpInfo::all();

        // Возвращаем представление с данными
        return view('help_info.index', compact('helpInfos'));
    }
    public function helpInfos()
    {
        $helpInfos = HelpInfo::all();

        // Возвращаем записи в формате JSON
        return response()->json($helpInfos);
    }

    public function create()
    {
        return view('help_info.create'); // Возвращает представление для создания
    }

    public function store(Request $request)
    {
        $request->validate([
            'page_number' => 'required|string|max:255',
            'info' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Проверка на изображение
        ]);

        // Обработка загрузки файла
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public'); // Сохранение файла
        }

        // Сохранение информации в базе данных
        HelpInfo::create([
            'page_number' => $request->page_number,
            'info' => $request->info,
            'image_path' => $imagePath, // Сохранение пути к изображению
        ]);

        return redirect()->route('help_info.create')->with('success', 'Информация успешно добавлена!');
    }

    // Метод для отображения формы редактирования
    public function edit($id)
    {
        // Получаем запись из базы данных
        $helpInfo = HelpInfo::findOrFail($id);

        // Возвращаем представление с данными записи
        return view('help_info.edit', compact('helpInfo'));
    }

    // Метод для обновления информации
    public function update(Request $request, $id)
    {
        // Валидация данных
        $request->validate([
            'page_number' => 'required|string',
            'info' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Ограничения для изображений
        ]);

        // Получаем запись
        $helpInfo = HelpInfo::findOrFail($id);

        // Обновляем информацию
        $helpInfo->page_number = $request->input('page_number');
        $helpInfo->info = $request->input('info');

        // Если загружено новое изображение
        if ($request->hasFile('image')) {
            // Сохранение изображения и получение пути
            $imagePath = $request->file('image')->store('images', 'public');
            $helpInfo->image_path = $imagePath;
        }

        // Сохраняем изменения
        $helpInfo->save();

        // Перенаправляем с сообщением об успехе
        return redirect()->route('help_info.index')->with('success', 'Информация успешно обновлена.');
    }

}

