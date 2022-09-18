<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>jQuery UI</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="http://ajax.aspnetcdn.com/ajax/jquery.ui/1.10.3/themes/sunny/jquery-ui.css">
    <script type="text/javascript">
        var route = "{{ url('autocomplete-search2') }}";
        $.ajax({
            url: route,         /* Куда пойдет запрос */
            method: 'get',             /* Метод передачи (post или get) */
            dataType: 'json',          /* Тип данных в ответе (xml, json, script, html). */
            data: {text: 'Текст'},     /* Параметры передаваемые в запросе. */
            success: function(data){   /* функция которая будет выполнена после успешного запроса.  */

                  $(function() {
                     var flowers =data;
                     $('#acInput').autocomplete({
                         source: flowers
                     })
                 });
            }
        });

    </script>
</head>
<body>
<form>
    <div>
        <label for="acInput">Выберите название цветка: </label>
        <input id="acInput"/>
    </div>
</form>
</body>
</html>
