@extends('layouts.homeLayout')
@section('content')
    @push('plans-scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css" rel="stylesheet"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.css" rel="stylesheet"/>
        {{--<script type="text/javascript" src="{{ URL::asset('js/plans_editor.js') }}"></script>--}}
        <script type="text/javascript" src="{{ URL::asset('js/jquery.mask.js') }}"></script>
    @endpush

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Планы менеджеров</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="message"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap111">
                                <thead>
                                <tr>
                                    <th class="text-center">Имя</th>
                                    <th class="text-center">План</th>
                                    <th class="text-center">Год</th>
                                    <th class="text-center">Добавление / Удаление</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                            {{ csrf_field() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.plans_editor')
@endsection
