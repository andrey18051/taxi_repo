{{-- resources/views/help_info/edit.blade.php --}}

@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1 class="mb-4">Изменить информацию для страницы {{ $helpInfo->page_number }}</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('help_info.update', $helpInfo->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')  <!-- Указываем метод PUT для обновления -->

            <div class="mb-3">
                <label for="page_number" class="form-label">Номер страницы:</label>
                <input type="text" name="page_number" id="page_number" class="form-control" value="{{ $helpInfo->page_number }}" required>
            </div>

            <div class="mb-3">
                <label for="info" class="form-label">Информация:</label>
                <textarea name="info" id="info" rows="5" class="form-control" required>{{ $helpInfo->info }}</textarea>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Загрузить новое изображение (оставьте пустым, если не хотите менять):</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
                @if ($helpInfo->image_path)
                    <img src="{{ asset('storage/' . $helpInfo->image_path) }}" alt="Current Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                @endif

            </div>

            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>
    </div>

@endsection
