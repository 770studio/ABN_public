@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Прогноз поступлений</h3>
            </div>
        </div>
        {{--фильтры--}}
        @include('filters.forecast_filter')
    </div>

@endsection