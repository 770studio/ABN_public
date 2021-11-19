@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Отчет по субагентам</h3>
            </div>
        </div>
        {{--фильтры--}}
        @include('filters.subagents_filter')
    </div>





@endsection