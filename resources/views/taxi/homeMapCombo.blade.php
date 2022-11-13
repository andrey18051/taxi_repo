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
        <br>
        <div class="container text-center">
            <a class="btn btn-outline-danger btn-circle"
               @guest
               href="{{ route('callBackForm') }}"
               @else
               href="{{ route('callBackForm-phone', Auth::user()->user_phone) }}"
               @endguest
               title="Екстренна допомога">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"  fill="currentColor" class="bi bi-telephone-inbound" viewBox="0 0 16 16">
                    <path d="M15.854.146a.5.5 0 0 1 0 .708L11.707 5H14.5a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 1 0v2.793L15.146.146a.5.5 0 0 1 .708 0zm-12.2 1.182a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                </svg>
            </a>
        </div>
        <br>
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
