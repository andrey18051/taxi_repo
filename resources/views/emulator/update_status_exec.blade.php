@extends('layouts.admin')

@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                        Execution Status

                </div>
            </div>
        </div>
    </div>
</div>


<br>
    <div class="row justify-content-center">

                <div class="col-md-10 col-md-offset-1">
                    <div class="card">
                    <div class="card-body">


                            <div class="container">
                                <div class="row">

                                    <div class="form-outline mb-2 col-12" >
                                        <div class="row">
                                            <table style="border-collapse: collapse;" border="1">
                                                <thead>
                                                <tr style="border-bottom: 1px solid #000;">
                                                    <th style="border: 1px solid #000;">ID</th>
                                                    <th style="border: 1px solid #000;">UID</th>
                                                    <th style="border: 1px solid #000;">Тип</th>
                                                    <th style="border: 1px solid #000;">Execution_status</th>
                                                    <th style="border: 1px solid #000;">состояние</th>
                                                    <th style="border: 1px solid #000;">Время события</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @for ($i = 0; $i < count($data); $i++)
                                                    <tr>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->id}}</td>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->order}}</td>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->order_type}}</td>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->execution_status}}</td>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->cancel}}</td>
                                                        <td style="border: 1px solid #000;">{{$data[$i]->created_at}}</td>
                                                    </tr>
                                                @endfor
                                                </tbody>
                                            </table>



                                        </div>

                                    </div>

                                </div>


                            </div>



                    </div>
                </div>
            </div>


    </div>


<script>
    $(document).ready(function() {
        // Устанавливаем интервал перезагрузки страницы каждые 5000 миллисекунд (5 секунд)
        setInterval(function() {
            location.reload(); // Перезагрузка текущей страницы
        }, 5000);
    });
</script>
@endsection
