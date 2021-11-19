@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить правообладателя</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">
                                {!! Form::open(['route' => 'principals.store']) !!}

                                <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                                    <label for="name">Правообладатель</label>
                                    <input type="text"  id="name" class="form-control-line form-control" name="name" value="{{ old('name') }}" autocomplete="off">

                                    @if ($errors->has('name'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('name') }}
                                        </div>
                                    @endif

                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('agentcontract_number') ? ' has-danger' : '' }}">
                                            <label for="agentcontract_number">№ договора</label>
                                            <input type="text" class="form-control-line form-control" name="agentcontract_number" id="agentcontract_number"  value="{{ old('agentcontract_number') }}" autocomplete="off">
                                            @if ($errors->has('agentcontract_number'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('agentcontract_number') }}
                                                </div>
                                            @endif

                                        </div>

                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('agentcontract_date') ? ' has-danger' : '' }}">
                                            <label for="agentcontract_date">Дата договора</label>
                                            <input type="date" class="form-control-line form-control" name="agentcontract_date" id="agentcontract_date"  value="{{ old('agentcontract_date') }}" autocomplete="off">
                                            @if ($errors->has('agentcontract_date'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('agentcontract_date') }}
                                                </div>
                                            @endif

                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('head_name') ? ' has-danger' : '' }}">
                                            <label for="head_name">Директор</label>
                                            <input type="text" class="form-control-line form-control" name="head_name" id="head_name"  value="{{ old('head_name') }}" autocomplete="off">
                                            @if ($errors->has('head_name'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('head_name') }}
                                                </div>
                                            @endif

                                        </div>

                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('head_name_2') ? ' has-danger' : '' }}">
                                            <label for="head_name_2">Директор(род.падеж)</label>
                                            <input type="text" class="form-control-line form-control" name="head_name_2" id="head_name_2"  value="{{ old('head_name_2') }}" autocomplete="off">
                                            @if ($errors->has('head_name_2'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('head_name_2') }}
                                                </div>
                                            @endif

                                        </div>

                                    </div>

                                </div>

                                <div class="form-group{{ $errors->has('adress') ? ' has-danger' : '' }}">
                                    <label for="adress">Адрес</label>
                                    <input type="text"  id="adress" class="form-control-line form-control" name="adress" value="{{ old('adress') }}" autocomplete="off">

                                    @if ($errors->has('adress'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('adress') }}
                                        </div>
                                    @endif

                                </div>

                                <div class="form-group{{ $errors->has('requisites') ? ' has-danger' : '' }}">
                                    <label for="requisites">Реквизиты</label>
                                    <textarea type="text"  id="requisites" class="form-control-line form-control" name="requisites"  autocomplete="off">{{ old('requisites')}}</textarea>

                                    @if ($errors->has('requisites'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('requisites') }}
                                        </div>
                                    @endif

                                </div>

                                <button type="submit" class="btn btn-fill btn-warning">Создать</button>
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>


@endsection