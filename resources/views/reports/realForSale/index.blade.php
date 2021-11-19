@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Отчет о передаче недвижимости на реализацию </h3>
            </div>
        </div>
        {{--фильтры--}}
        @include('filters.real_for_sale_filter')
    </div>

@endsection