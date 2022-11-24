@extends('layouts.taxiNewComboMap')

@section('content')
    <div class="container" style="background-color: hsl(0, 0%, 96%)">

        <!--     Пошук по мапі.-->
        <div class="px-1 py-1 px-md-5 text-center text-lg-start" id="block_map">

            <div class="container" style="text-align: center">
                <h2 class="gradient"><b>Київ та область</b></h2>
                <p class="text-center gradient">Пересуньте маркери щоб відзначити звідки та куди їхати.</p>
            </div>
            <div class="container text-center">
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Аэропорт Борисполь терминал Д", "taxi.transferBorispol"])}}">
                            <img src="{{ asset('img/borispol.png') }}" style="width: 200px; height: auto"
                                 title="Трансфер до аеропорту Бориспіль">
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Аэропорт Жуляны новый (ул.Медовая 2)", "taxi.transferJulyany"])}}">
                            <img src="{{ asset('img/sikorskogo.png') }}" style="width: 200px; height: auto"
                                 title="Трансфер до аеропорту Київ">
                        </a>
                    </div>
                    <br>
                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["ЖД Южный", "taxi.transferUZ"])}}">
                            <img src="{{ asset('img/UZ.png') }}" style="width: 200px; height: auto"
                                 title="Трансфер до залізничного вокзалу">
                        </a>
                    </div>

                    <div class="col-lg-3 col-6">
                        <a href="{{route('transfer',
                                                     ["Центральный автовокзал (у шлагбаума пл.Московская 3)", "taxi.transferAuto"])}}">
                            <img src="{{ asset('img/auto.jpeg') }}" style="width: 200px; height: auto"
                                 title="Трансфер до автовокзалу">
                        </a>
                    </div>
                </div>
            </div>
            <br>
                  <form action="{{route('search-cost-map')}}" id="form_object">
                     @csrf
                     <div class="row">
                         <div class="col-sm-8 col-lg-8">
                             <input type="hidden" id="lat" name="lat"/>
                             <input type="hidden" id="lng" name="lng" />
                             <input type="hidden" id="lat2" name="lat2" />
                             <input type="hidden" id="lng2" name="lng2"/>

                             @guest
                                 <input type="hidden" id="user_phone" name="user_phone"  value="+380936665544">
                                 <input type="hidden" id="user_full_name" name="user_full_name"  value="Гість">
                             @else
                                 <input type="hidden"  id="user_phone" name="user_phone" value="{{Auth::user()->user_phone}}">
                                 <input type="hidden" id="user_full_name" name="user_full_name"   value="{{Auth::user()->name}}">
                             @endguest

                             <input type="hidden" class="form-control" id="search4" name="search4" autocomplete="off" placeholder="Пошук об'єкта" value="" required>
                             <input type="hidden" class="form-control" id="search5" name="search5" autocomplete="off" placeholder="Пошук об'єкта" >
                             <input type="hidden" class="form-control" id="comment" name="comment" placeholder="Додати побажання" />
                             <input type="hidden" id="add_cost" name="add_cost" value="0" class="form-control" />
                             <div class="container">
                                 <div class="row">
                                     <div class="col-12">
                                        <div id="googleMap" style="width:100%;height:150px;"></div>
                                     </div>
                                 </div>
                             </div>

                             <div class="container" style="text-align: left">
                                <input type="checkbox" class="form-check-input" id="route_undefined"  name="route_undefined" value="1">
                                <label class="form-check-label" for="route_undefined">По місту</label>
                             </div>

                             <div class="container">
                                 <script defer src="https://www.google.com/recaptcha/api.js"></script>
                                 <div class="g-recaptcha" data-sitekey="{{ config('app.RECAPTCHA_SITE_KEY') }}"></div>
                            </div>
                     </div>

                         <div class="col-sm-4 col-lg-4" style="margin-top: 5px">

                             <a href="javascript:void(0)" class="btn btn-outline-success col-12 order-md-last"
                                onclick="showHide('block_id')">Додаткові параметри</a><br/><br/>

                             <div id="block_id" style="display: none">
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

                     </div>
                      <div class="container text-center">
                          <button class="w-100 btn btn-primary btn-lg"  type="submit">Розрахувати вартість поїздки</button>
                      </div>
                 </form>
         </div>
    </div>

    <script defer type="text/javascript">
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
                lat: 50.568235937668135,
                lng: 30.26999524844567
            };
            var marker2;
            var myLatlng2 = {
                lat: 50.51499815972034,
                lng: 30.23909620059411
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
     <script defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCoyJk5j4GRS41GYwZTRJduPnV5k8SDCoc&callback=myMap"></script>

@endsection
