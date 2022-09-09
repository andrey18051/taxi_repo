@extends('layouts.taxi')

@section('content')


     <div class="text-center">
         <a  class="btn btn-outline-secondary  col-3 order-md-last"
                                  onclick="showHide('block_street')">Пошук за адресою</a>
         <a  class="btn btn-outline-secondary offset-1 col-3 order-md-last"
             onclick="showHide('block_object')">Пошук по об'єктах</a>
         <a  class="btn btn-outline-secondary offset-1 col-3"  onclick="showHide('block_map')">Пошук по мапі</a>

         <h1>Таксі Київ (Київська область)</h1>
     </div>
    <!--     Пошук по мапі.-->

     <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_map" style="background-color: hsl(0, 0%, 96%); display:none">
         <div class="container">
             <main>
                 <div class="text-center">
                     <p class="lead">Пошук по мапі. Перемістіть маркери для розрахунку вартості поїздки.</p>
                 </div>
                 <form action="{{route('search-cost-map')}}" id="form_object">
                     @csrf
                     <div class="row g-5">
                         <div class="col-md-7 col-lg-8">
                             <div>
                                 <label>
                                     <!--lat-->
                                     <input type="hidden" id="lat" name="lat"/>
                                 </label>
                                 <label>
                                     <!--lng-->
                                     <input type="hidden" id="lng" name="lng" />
                                 </label>
                             </div>
                             <div>
                             <label>
                                <!-- lat-->
                                 <input type="hidden" id="lat2" name="lat2" />
                             </label>
                             <label>
                                 <!--lng-->
                                 <input type="hidden" id="lng2" name="lng2"/>
                             </label>


                         <div id="googleMap" style="width:100%;height:300px;"></div>


                             <div class="row g-3">
                                 <div class="col-sm-8">
<!--                                                                 <label for="user_phone" class="form-label">Телефон</label> -->
                                     <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" placeholder="0936665544" value="">
                                 </div>
                                 <div class="col-sm-4">
<!--                                                                  <label for="user_full_name" class="form-label">Ім'я</label> -->
                                     <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="Новий замовник">

                                 </div>
                                 <div class="col-12">
<!--                                     <label for="search4" class="form-label">Звідки</label>-->
                                     <input type="hidden" class="form-control" id="search4" name="search4" autocomplete="off" placeholder="Пошук об'єкта" value="" required>
                                 </div>

                                 <div class="col-3" >
                                     <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1" onclick="showHide('block_city_map')">
                                     <label class="form-check-label" for="route_undefined">По місту</label>
                                 </div>

                                 <div id="block_city_map" class="container"  style="display:block">
                                     <div class="col-12">
<!--                                         <label for="search5" class="form-label">Куди</label>-->
                                         <input type="hidden" class="form-control" id="search5" name="search5" autocomplete="off" placeholder="Пошук об'єкта" >
                                     </div>
                                 </div>
                                 <div class="col-sm-11">
<!--                                                                       <label for="comment" class="form-label">Коментар</label>-->
                                     <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
<!--                                                                     <textarea type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання"></textarea>-->

                                 </div>

                                 <div class="col-sm-1">
<!--                                                                      <label for="add_cost" class="form-label">Додати до вартості (грн)</label>-->
                                     <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                                 </div>

                                 <script src="https://www.google.com/recaptcha/api.js"></script>
                                 <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                             </div>

                         </div>
                         </div>
                         <div class="col-md-5 col-lg-4 order-md-last">

                             <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                                onclick="showHide('block_id_map')">Додаткові параметри</a><br/><br/>

                             <div id="block_id_map" style="display: none">
                                 <ul class="list-group mb-3">
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <label class="form-label" for="required_time">Час подачі</label>
                                         <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="null">
                                     </li>
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <div class="form-check">
                                             <input type="checkbox" class="form-check-input" id="wagon" name="wagon">
                                             <label class="form-check-label" for="wagon">Универсал</label>
                                         </div>
                                     </li>
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <div class="form-check">
                                             <input type="checkbox" class="form-check-input" id="minibus" name="minibus">
                                             <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                         </div>
                                     </li>
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <div class="form-check">
                                             <input type="checkbox" class="form-check-input" id="premium" name="premium">
                                             <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                         </div>
                                     </li>
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <div class="col-md-12">
                                             <label for="$flexible_tariff_name" class="form-label">Тариф</label>
                                             <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" >
                                                 <option></option>
                                                 @for ($i = 0; $i < count($json_arr); $i++)
                                                     <option>{{$json_arr[$i]['name']}}</option>
                                                 @endfor
                                             </select>
                                         </div>
                                     </li>
                                     <li class="list-group-item d-flex justify-content-between lh-sm">
                                         <div class="col-md-12">
                                             <label for="$flexible_tariff_name" class="form-label">Тип оплати замовлення</label>
                                             <select class="form-select" id="flexible_tariff_name" name="payment_type" required>
                                                 <option>готівка</option>
                                                 <!--                                            <option>безготівка</option>-->
                                             </select>
                                         </div>
                                     </li>
                                 </ul>
                             </div>
                         </div>

                     </div><button class="w-100 btn btn-primary btn-lg" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
                 </form>
             </main>


         </div>
     </div>

    <!--     Пошук за адресою.-->

    <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_street" style="background-color: hsl(0, 0%, 96%); display:block">
      <div class="container">
        <main>
            <div class="text-center">
                <p class="lead">Пошук за адресою. Заповнити поля для розрахунку вартості поїздки.</p>
            </div>
            <form action="{{route('search-cost')}}" id="form">
                @csrf
                <div class="row g-5">

                    <div class="col-md-7 col-lg-8">

                            <div class="row g-3">
                                <div class="col-sm-8">
<!--                                    <label for="user_phone" class="form-label">Телефон</label>-->
                                    <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" placeholder="0936665544" value="">
                                </div>
                                <div class="col-sm-4">
<!--                                    <label for="user_full_name" class="form-label">Ім'я</label>-->
                                    <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="Новий замовник">

                                </div>

                                    <div class="col-9">
                                        <label for="search" class="form-label">Звідки</label>
                                        <input type="text" class="form-control" id="search" name="search" autocomplete="off" placeholder="Пошук вулиці" value="" required>
                                    </div>

                                    <div class="col-3">
                                        <label for="from_number" class="form-label">Будинок</label>
                                        <input type="text" id="from_number" name="from_number" placeholder="?" autocomplete="off" class="form-control" style="text-align: center" value="" required/>
                                    </div>

                                <div class="col-3" >
                                    <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1" onclick="showHide('block_city')">
                                    <label class="form-check-label" for="route_undefined">По місту</label>
                                </div>

                                <div id="block_city" class="container"  style="display:block">
                                    <div class="row">
                                        <div class="col-9">
                                            <label for="search1" class="form-label">Куди</label>
                                            <input type="text" class="form-control" id="search1" name="search1" autocomplete="off" placeholder="Пошук вулиці" >
                                        </div>

                                        <div class="col-3">
                                            <label for="to_number" class="form-label">Будинок</label>
                                            <input type="text" id="to_number" name="to_number" placeholder="?" autocomplete="off" class="form-control" style="text-align: center" value="" />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-11">
<!--                                    <label for="comment" class="form-label">Коментар</label>-->
                                    <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
<!--                                    <textarea type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання"></textarea>-->

                                </div>

                                <div class="col-sm-1">
<!--                                    <label for="add_cost" class="form-label">Додати до вартості (грн)</label>-->
                                    <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                                </div>

                        <script src="https://www.google.com/recaptcha/api.js"></script>
                        <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                    </div>

                    </div>
                    <div class="col-md-5 col-lg-4 order-md-last">

                            <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                               onclick="showHide('block_id_street')">Додаткові параметри</a><br/><br/>

                        <div id="block_id_street" style="display: none;">
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <label class="form-label" for="required_time">Час подачі</label>
                                    <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="null">
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="wagon" name="wagon">
                                        <label class="form-check-label" for="wagon">Универсал</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="minibus" name="minibus">
                                        <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="premium" name="premium">
                                        <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тариф</label>
                                        <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" >
                                            <option></option>
                                            @for ($i = 0; $i < count($json_arr); $i++)
                                                <option>{{$json_arr[$i]['name']}}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between lh-sm">
                                    <div class="col-md-12">
                                        <label for="$flexible_tariff_name" class="form-label">Тип оплати замовлення</label>
                                        <select class="form-select" id="flexible_tariff_name" name="payment_type" required>
                                            <option>готівка</option>
<!--                                            <option>безготівка</option>-->
                                        </select>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div><button class="w-100 btn btn-primary btn-lg" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
            </form>
        </main>


    </div>
    </div>

     <!--     Пошук по об'єктах.-->

     <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_object" style="background-color: hsl(0, 0%, 96%); display:none">
        <div class="container">
            <main>
                <div class="text-center">
                    <p class="lead">Пошук по об'єктах. Заповнити поля для розрахунку вартості поїздки.</p>
                </div>
                <form action="{{route('search-cost-object')}}" id="form_object">
                    @csrf
                    <div class="row g-5">

                        <div class="col-md-7 col-lg-8">

                            <div class="row g-3">
                                <div class="col-sm-8">
                                    <!--                                    <label for="user_phone" class="form-label">Телефон</label>-->
                                    <input type="hidden" class="form-control" id="user_phone" name="user_phone" pattern="[0-9]{10}" placeholder="0936665544" value="">
                                </div>
                                <div class="col-sm-4">
                                    <!--                                    <label for="user_full_name" class="form-label">Ім'я</label>-->
                                    <input type="hidden" id="user_full_name" name="user_full_name" placeholder="Андрій"  class="form-control" value="Новий замовник">

                                </div>
                                <div class="col-12">
                                    <label for="search" class="form-label">Звідки</label>
                                    <input type="text" class="form-control" id="search2" name="search2" autocomplete="off" placeholder="Пошук об'єкта" value="" required>
                                </div>
                                <div class="col-3" >
                                    <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1" onclick="showHide('block_city_object')">
                                    <label class="form-check-label" for="route_undefined">По місту</label>
                                </div>

                                <div id="block_city_object" class="container"  style="display:block">
                                    <div class="col-12">
                                        <label for="search1" class="form-label">Куди</label>
                                        <input type="text" class="form-control" id="search3" name="search3" autocomplete="off" placeholder="Пошук об'єкта" >
                                    </div>
                                </div>
                                <div class="col-sm-11">
                                    <!--                                    <label for="comment" class="form-label">Коментар</label>-->
                                    <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
                                    <!--                                    <textarea type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання"></textarea>-->

                                </div>

                                <div class="col-sm-1">
                                    <!--                                    <label for="add_cost" class="form-label">Додати до вартості (грн)</label>-->
                                    <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                                </div>

                                <script src="https://www.google.com/recaptcha/api.js"></script>
                                <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                            </div>

                        </div>
                        <div class="col-md-5 col-lg-4 order-md-last">

                            <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                               onclick="showHide('block_id_object')">Додаткові параметри</a><br/><br/>

                            <div id="block_id_object" style="display: none">
                                <ul class="list-group mb-3">
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <label class="form-label" for="required_time">Час подачі</label>
                                        <input type="datetime-local" step="any"  id="required_time"  name="required_time" value="null">
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="wagon" name="wagon">
                                            <label class="form-check-label" for="wagon">Универсал</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="minibus" name="minibus">
                                            <label class="form-check-label" for="minibus">Мікроавтобус</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="premium" name="premium">
                                            <label class="form-check-label" for="premium">Машина преміум-класса</label>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div class="col-md-12">
                                            <label for="$flexible_tariff_name" class="form-label">Тариф</label>
                                            <select class="form-select" id="flexible_tariff_name" name="flexible_tariff_name" >
                                                <option></option>
                                                @for ($i = 0; $i < count($json_arr); $i++)
                                                    <option>{{$json_arr[$i]['name']}}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div class="col-md-12">
                                            <label for="$flexible_tariff_name" class="form-label">Тип оплати замовлення</label>
                                            <select class="form-select" id="flexible_tariff_name" name="payment_type" required>
                                                <option>готівка</option>
                                                <!--                                            <option>безготівка</option>-->
                                            </select>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div><button class="w-100 btn btn-primary btn-lg" type="submit" style="margin-top: 5px">Розрахувати вартість поїздки</button>
                </form>
            </main>


        </div>
    </div>
    <script type="text/javascript">
        /**
         * Функция Скрывает/Показывает блок
         * @author ox2.ru дизайн студия
         **/
        function showHide(element_id) {
            //Если элемент с id-шником element_id существует
            if (document.getElementById(element_id)) {
                //Записываем ссылку на элемент в переменную obj
                var obj = document.getElementById(element_id);
                //Если css-свойство display не block, то:
                if (element_id == "block_street") {
                    if (obj.style.display != "block") {
                        document.getElementById("block_object").style.display = 'none';
                        document.getElementById("block_map").style.display = 'none';
                        document.getElementById("block_street").style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
                if (element_id == "block_object") {
                    if (obj.style.display != "block") {
                        document.getElementById("block_street").style.display = 'none';
                        document.getElementById("block_map").style.display = 'none';
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_map") {
                    if (obj.style.display != "block") {
                        document.getElementById("block_street").style.display = 'none';
                        document.getElementById("block_object").style.display = 'none';
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_id_map") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_id_street") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_id_object") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_city_map") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }

                if (element_id == "block_city_object") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
                if (element_id == "block_city") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
                if (element_id == "block_id") {
                    if (obj.style.display != "block") {
                        obj.style.display = "block"; //Показываем элемент
                    }
                    else obj.style.display = "none"; //Скрываем элемент
                }
            }
            //Если элемент с id-шником element_id не найден, то выводим сообщение
            else alert("Элемент с id: " + element_id + " не найден!");
        }

        /**
         * Карта Гугл
         */
        function myMap() {
            var marker;
            var myLatlng = {
                lat: 50.418843668133,
                lng: 30.539846933016
            };
            var marker2;
            var myLatlng2 = {
                lat: 50.376733115795,
                lng: 30.609379358341
            };

            var mapProp= {
                zoom: 10,
                center: myLatlng
            };
            var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);

            document.getElementById('lat').value = myLatlng.lat;
            document.getElementById('lng').value = myLatlng.lng;

            document.getElementById('lat2').value = myLatlng2.lat;
            document.getElementById('lng2').value = myLatlng2.lng;

            marker = new google.maps.Marker({
                position: myLatlng,
                map: map,
                draggable: true,
                label: 'Звідки'
            });

            marker.addListener('dragend', function(e) {
                var position = marker.getPosition();
                updateCoordinates(position.lat(), position.lng())
            });

            map.addListener('click', function(e) {
                marker.setPosition(e.latLng);
                updateCoordinates(e.latLng.lat(), e.latLng.lng())
            });

            marker2 = new google.maps.Marker({
                position: myLatlng2,
                map: map,
                draggable: true,
                label: 'Куди'
            });

/*

            var req = new XMLHttpRequest();
            req.open("GET", "/driversPosition", false);
         //   req.responseType = 'json';
            req.send(null);
            alert(req.responseText);
*/





/*

            const image =
                "https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png";

            // Set LatLng and title text for the markers. The first marker (Boynton Pass)
            // receives the initial focus when tab is pressed. Use arrow keys to
            // move between markers; press tab again to cycle through the map controls.
            const tourStops = [
                [{ lat: 50.416525, lng: 30.520825 }, "Мікроавтобус"],
                [{ lat: 50.43962, lng: 30.51525 }, "Мінібус"],

            ];

            // Create an info window to share between markers.
            const infoWindow = new google.maps.InfoWindow();

            // Create the markers.
            tourStops.forEach(([position, title], i) => {
                const marker = new google.maps.Marker({
                    position,
                    map,
                    title: `${i + 1}. ${title}`,
                    label: `${i + 1}`,
                    icon: image,
                    optimized: false,
                });

                // Add a click listener for each marker, and set up the info window.
                marker.addListener("click", () => {
                    infoWindow.close();
                    infoWindow.setContent(marker.getTitle());
                    infoWindow.open(marker.getMap(), marker);
                });
            });
*/


            marker2.addListener('dragend', function(e) {
                var position2 = marker2.getPosition();
                updateCoordinates2(position2.lat(), position2.lng())
            });

            map.addListener('click', function(e) {
                marker2.setPosition(e.latLng);
                updateCoordinates2(e.latLng.lat(), e.latLng.lng())
            });

        }

        function updateCoordinates(lat, lng) {
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
        }
        function updateCoordinates2(lat, lng) {
            document.getElementById('lat2').value = lat;
            document.getElementById('lng2').value = lng;
        }
    </script>
     <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc&callback=myMap"></script>

@endsection
