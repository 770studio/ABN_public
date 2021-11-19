@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Редактировать субагента</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        {!! Form::open(['route' => ['subagent.update',$subagent->id],'method'=>'put']) !!}
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                                    <label for="name">Название</label>
                                    <input type="text"  id="name" class="form-control-line form-control" name="name" value="{{$subagent->name}}" autocomplete="off">

                                    @if ($errors->has('name'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('name') }}
                                        </div>
                                    @endif

                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('sub_contract_number') ? ' has-danger' : '' }}">
                                            <label for="sub_contract_number">Номер договора</label>
                                            <input type="text"  id="sub_contract_number" class="form-control-line form-control" name="sub_contract_number" value="{{$subagent->sub_contract_number}}" autocomplete="off">

                                            @if ($errors->has('sub_contract_number'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('sub_contract_number') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('sub_contract_date') ? ' has-danger' : '' }}">
                                            <label for="sub_contract_date">Дата договора</label>
                                            <input type="date"  id="sub_contract_date" class="form-control-line form-control" name="sub_contract_date" value="{{$subagent->sub_contract_date}}" autocomplete="off">

                                            @if ($errors->has('sub_contract_date'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('sub_contract_date') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('head_name') ? ' has-danger' : '' }}">
                                            <label for="head_name">ФИО директора</label>
                                            <input type="text"  id="head_name" class="form-control-line form-control" name="head_name" value="{{$subagent->head_name}}" autocomplete="off">

                                            @if ($errors->has('head_name'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('head_name') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('head_name_2') ? ' has-danger' : '' }}">
                                            <label for="head_name_2">ФИО директора (род.падеж)</label>
                                            <input type="text"  id="head_name_2" class="form-control-line form-control" name="head_name_2" value="{{$subagent->head_name_2}}" autocomplete="off">

                                            @if ($errors->has('head_name_2'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('head_name_2') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group{{ $errors->has('base_of_rules') ? ' has-danger' : '' }}">
                                            <label for="base_of_rules">Действует на основании</label>
                                            <input type="text"  id="base_of_rules" class="form-control-line form-control" name="base_of_rules" value="{{$subagent->base_of_rules}}" autocomplete="off">

                                            @if ($errors->has('base_of_rules'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('base_of_rules') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group{{ $errors->has('adress') ? ' has-danger' : '' }}">
                                            <label for="adress">Адрес</label>
                                            <input type="text"  id="adress" class="form-control-line form-control" name="adress" value="{{$subagent->adress}}" autocomplete="off">

                                            @if ($errors->has('adress'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('adress') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>



                            </div>
                            <div class="col-lg-6">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('ogrn') ? ' has-danger' : '' }}">
                                            <label for="ogrn">ОГРН/ОГРНИП</label>
                                            <input type="text"  id="ogrn" class="form-control-line form-control" name="ogrn" value="{{$subagent->ogrn}}" autocomplete="off">

                                            @if ($errors->has('ogrn'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('ogrn') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('inn') ? ' has-danger' : '' }}">
                                            <label for="inn">ИНН</label>
                                            <input type="text"  id="inn" class="form-control-line form-control" name="inn" value="{{$subagent->inn}}" autocomplete="off">

                                            @if ($errors->has('inn'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('inn') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('kpp') ? ' has-danger' : '' }}">
                                            <label for="kpp">КПП</label>
                                            <input type="text"  id="kpp" class="form-control-line form-control" name="kpp" value="{{$subagent->kpp}}" autocomplete="off">

                                            @if ($errors->has('kpp'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('kpp') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('bank_name') ? ' has-danger' : '' }}">
                                            <label for="bank_name">Банк</label>
                                            <input type="text"  id="bank_name" class="form-control-line form-control" name="bank_name" value="{{$subagent->bank_name}}" autocomplete="off">

                                            @if ($errors->has('bank_name'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('bank_name') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('bik') ? ' has-danger' : '' }}">
                                            <label for="bik">БИК</label>
                                            <input type="text"  id="bik" class="form-control-line form-control" name="bik" value="{{$subagent->bik}}" autocomplete="off">

                                            @if ($errors->has('bik'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('bik') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('rs') ? ' has-danger' : '' }}">
                                            <label for="rs">Р/С</label>
                                            <input type="text"  id="rs" class="form-control-line form-control" name="rs" value="{{$subagent->rs}}" autocomplete="off">

                                            @if ($errors->has('rs'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('rs') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('ks') ? ' has-danger' : '' }}">
                                            <label for="ks">К/С</label>
                                            <input type="text"  id="ks" class="form-control-line form-control" name="ks" value="{{$subagent->ks}}" autocomplete="off">

                                            @if ($errors->has('ks'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('ks') }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-fill btn-warning">Сохранить</button>
                            </div>

                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

    </div>


@endsection
