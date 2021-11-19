@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Обновление цен</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4>Даты последнего обновления</h4>
                        <h6>Доступность для сайта: {{$dates[0]}}</h6>
                        <h6>Параметры объектов: {{$dates[1]}}</h6>


                        <div class="btn-container">
                            <button type="button" class="btn btn-warning" id="sendBtn">Обновить параметры
                                объектов
                            </button>
                        </div>
                        <div id="alert" class="d-none alert"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
       $('#sendBtn').click(function (e){
           e.preventDefault();
           console.log('sending')
           $.ajax({

               url: "https://amocrm.akbars-development.ru/data/report_portal/objects_parser_v2.php",
               type: "get",
               data: {
                   update_type: 'params'
               },
               success: function (response) {
                   console.log('success');
                   $('#alert').text('Запрос успешно обработан').addClass('alert-success').removeClass('d-none');
               },
               error: function (xhr) {
                   console.log('error')
                   $('#alert').text('Произошла ошибка при выполнении запроса').addClass('alert-danger').removeClass('d-none');
               }
           });

       })

    </script>
@endsection
