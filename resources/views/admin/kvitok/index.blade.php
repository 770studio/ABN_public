<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{config('app.url')}}/img/favicon.png">
    <title>{{ config('app.name') }}</title>

    <link rel="stylesheet" href="{{config('app.url')}}/css/login.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  <style>
    body{
    	font-size:12px;
         }
   .table td, .table th {
     padding: .25rem;
    
    } 	
  </style>
</head>
<body>
<!-- ============================================================== -->
<!-- Preloader - style you can find in spinners.css -->
<!-- ============================================================== -->
<div class="preloader">
    <svg class="circular" viewBox="25 25 50 50">
        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10"/>
    </svg>
</div>
<!-- ============================================================== -->
<!-- Main wrapper - style you can find in pages.scss -->
<!-- ============================================================== -->
<section id="wrapper">
    <div class="container">
        <h1 class="mt-4 mb-4">Квиток</h1>
        @if(isset($error))
            <div class="alert alert-danger">
                {{$error}}
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr style="border:1px solid #ddd;">
                    <th width="30%" style="border-right:1px solid #ddd;"><b>Название</b></th>
                    <th width="70%"><b>Значение</b></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Название комплекса</td>
                    <td>{{@$data->object_zhk ? $data->object_zhk : '-'}}</td>
                </tr>
                <tr>
                    <td>№ Дома (строительный)</td>
                    <td>{{@$data->object_building_number ? $data->object_building_number : '-'}}</td>
                </tr>
                <tr>
                    <td>№ Квартиры</td>
                    <td>{{@$data->object_number ? $data->object_number : '-'}}</td>
                </tr>
                <tr>
                    <td>ФИО Клиента</td>
                    <td>
                        @foreach($contacts as $contact)
                            <p class="mb-0">{{$contact->name ? $contact->name : '-'}}</p>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td>Вид собственности</td>

                    <td>
                        @php($type = json_decode(@$data->property_type))

                        @if($type)
                            @if($type->type == 'personal')
                                единоличная
                            @elseif($type->type == 'together')
                                общая совместная
                            @elseif($type->type == 'part')
                                @foreach($type->part as $partItem)
                                    долевая {{$partItem}}
                                @endforeach
                            @else
                            -
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>№ Телефона контакта</td>
                    <td>
                        @foreach($contacts as $contact)
                            <p class="mb-0">+{{$contact->phone ? $contact->phone : '-'}}</p>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td>Форма оплаты</td>
                    <td>{{@$data->payment_type ? $data->payment_type : '-'}}</td>
                </tr>
                <tr>
                    <td>Общая стоимость квартиры</td>
                    <td>{{@$data->contract_sum ? number_format($data->contract_sum,2,',', ' ') : '-'}}</td>
                </tr>
                <tr>
                    <td>Акция</td>
                    <td>{{@$data->sale ? $data->sale : '-'}}</td>
                </tr>
                <tr>
                    <td>Стоимость 1 кв.м</td>
                    <td>{{@$data->object_price_psm ? number_format($data->object_price_psm,2,',', ' ') : '-'}}</td>
                </tr>
                <tr>
                    <td>Эл.почта</td>
                    <td>
                        @foreach($contacts as $contact)
                            <p class="mb-0">{{@$contact->email ? $contact->email : '-'}}</p>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td>Консультант</td>
                    <td>{{@$data->consultant ? $data->consultant : '-'}}</td>
                </tr>
                <tr>
                    <td>Оформитель</td>
                    <td>{{@$data->clerk ? $data->clerk : '-'}}</td>
                </tr>
                <tr>
                    <td>Дата бронирования</td>
                    <td>{{@$data->booking_date ? $data->booking_date : '-'}}</td>
                </tr>
                <tr>
                    <td>Этаж</td>
                    <td>{{@$data->object_floor ? $data->object_floor : '-'}}</td>
                </tr>
                <tr>
                    <td>Подъезд</td>
                    <td>{{@$data->object_entrance ? $data->object_entrance : '-'}}</td>
                </tr>
                <tr>
                    <td>Количество комнат</td>
                    <td>{{@$data->object_rooms_quantity ? $data->object_rooms_quantity : '-'}}</td>
                </tr>
                <tr>
                    <td>Общая проект. Площадь (кв.м.)</td>
                    <td>{{@$data->object_square ? $data->object_square : '-'}}</td>
                </tr>
                <tr>
                    <td>Жилая проект. Площадь (кв.м.)</td>
                    <td>{{@$data->living_space ? $data->living_space : '-'}}</td>
                </tr>
                <tr>
                    <td>Площадь комнаты 1, м2</td>
                    <td>{{@$data->room_1_area ? $data->room_1_area : '-'}}</td>
                </tr><tr>
                    <td>Площадь комнаты 2, м2</td>
                    <td>{{@$data->room_2_area ? $data->room_2_area : '-'}}</td>
                </tr><tr>
                    <td>Площадь комнаты 3, м2</td>
                    <td>{{@$data->room_3_area ? $data->room_3_area : '-'}}</td>
                </tr><tr>
                    <td>Площадь комнаты 4, м2</td>
                    <td>{{@$data->room_4_area ? $data->room_4_area : '-'}}</td>
                </tr><tr>
                    <td>Площадь кухни</td>
                    <td>{{@$data->kitchen_space ? $data->kitchen_space : '-'}}</td>
                </tr><tr>
                    <td>Санузел 1</td>
                    <td>{{@$data->bathroom_1_area ? $data->bathroom_1_area : '-'}}</td>
                </tr><tr>
                    <td>Санузел 2</td>
                    <td>{{@$data->bathroom_2_area ? $data->bathroom_2_area : '-'}}</td>
                </tr><tr>
                    <td>Кладовка</td>
                    <td>{{@$data->storeroom_area ? $data->storeroom_area : '-'}}</td>
                </tr><tr>
                    <td>Коридор</td>
                    <td>{{@$data->hall_area ? $data->hall_area : '-'}}</td>
                </tr>
                <tr>
                    <td>Балкон</td>
                    <td>{{@$data->balcony_area ? $data->balcony_area : '-'}}</td>
                </tr><tr>
                    <td>Лоджия</td>
                    <td>{{@$data->loggia_area ? $data->loggia_area : '-'}}</td>
                </tr><tr>
                    <td>Кол-во лоджий</td>
                    <td>{{@$data->number_of_loggias ? $data->number_of_loggias : '-'}}</td>
                </tr><tr>
                    <td>Кол-во балконов</td>
                    <td>{{@$data->number_of_balconies ? $data->number_of_balconies : '-'}}</td>
                </tr><tr>
                    <td>Банк</td>
                    <td>{{@$data->mortgage_bank ? $data->mortgage_bank : '-'}}</td>
                </tr><tr>
                    <td>Срок рассрочки</td>
                    <td>{{@$data->installment_period ? $data->installment_period : '-'}}</td>
                </tr><tr>
                    <td>Cубсидии?</td>
                    <td>{{@$data->subsidies ? $data->subsidies : '-'}}</td>
                </tr><tr>
                    <td>Индивидуальные условия</td>
                    <td>{{@$data->individual_conditions ? $data->individual_conditions : '-'}}</td>
                </tr><tr>
                    <td>Сумма первоначального взноса</td>
                    <td>{{@$data->first_payment ? number_format($data->first_payment,2,',', ' ') : '-'}}</td>
                </tr>
                <tr>
                    <td>Особые условия по оплате?</td>
                    <td>{{@$data->special_payment_conditions ? $data->special_payment_conditions : '-'}}</td>
                </tr>

                </tbody>
            </table>
        </div>
        @endif
    </div>


</section>
<script src="{{config('app.url')}}/js/login.js"></script>
</body>
</html>
