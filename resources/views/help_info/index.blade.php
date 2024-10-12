@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="mb-4">Список информации</h1>

{{--        @if (session('success'))--}}
{{--            <div class="alert alert-success">--}}
{{--                {{ session('success') }}--}}
{{--            </div>--}}
{{--        @endif--}}

        <table class="table">
            <thead>
            <tr>
                <th>Номер страницы</th>
                <th>Информация</th>
                <th>Изображение</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($helpInfos as $helpInfo)
                <tr>
                    <td>{{ $helpInfo->page_number }}</td>
                    <td>{{ $helpInfo->info }}</td>
                    <td>
                        @if ($helpInfo->image_path)
                            <img src="{{ asset('storage/' . $helpInfo->image_path) }}" alt="Current Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                        @else
                            Нет изображения
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('help_info.edit', $helpInfo->id) }}" class="btn btn-warning">Редактировать</a>
                        <!-- Здесь можно добавить кнопку для удаления -->
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <a href="{{ route('help_info.create') }}" class="btn btn-primary">Добавить новую информацию</a>
    </div>
@endsection
