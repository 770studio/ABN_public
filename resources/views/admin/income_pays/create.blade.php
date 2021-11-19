@extends('layouts.homeLayout')
@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить платеж</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        {!! Form::open(['route' => ['income_pays_payments.store'],'method'=>'post']) !!}
                        <div class="row">
                            <div class="col-6">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('docDate') ? ' has-danger' : '' }}">
                                            <label for="docDate">Дата документа</label>
                                            <input type="date" id="docDate" class="form-control-line form-control" name="docDate"
                                                   value="{{old('docDate')}}" autocomplete="off">
                                            @if ($errors->has('docDate'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('docDate') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('incomNumber') ? ' has-danger' : '' }}">
                                            <label for="incomNumber">Входящий номер</label>
                                            <input type="text" id="incomNumber" class="form-control-line form-control" name="incomNumber"
                                                   value="{{old('incomNumber')}}" autocomplete="off">
                                            @if ($errors->has('incomNumber'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('incomNumber') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('incomDate') ? ' has-danger' : '' }}">
                                            <label for="incomDate">Дата платежа</label>
                                            <input type="date" id="incomDate" class="form-control-line form-control" name="incomDate"
                                                   value="{{old('incomDate')}}" autocomplete="off">
                                            @if ($errors->has('incomDate'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('incomDate') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group{{ $errors->has('sum') ? ' has-danger' : '' }}">
                                            <label for="sum">Сумма платежа</label>
                                            <input type="text" id="sum" class="form-control-line form-control" name="sum"
                                                   value="{{old('sum')}}" autocomplete="off">
                                            @if ($errors->has('sum'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('sum') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="form-group{{ $errors->has('contractNumber') ? ' has-danger' : '' }}">
                                            <label for="contractNumber">Номер договора</label>
                                            <input type="text" id="contractNumber" class="form-control-line form-control" name="contractNumber"
                                                   value="{{old('contractNumber')}}" autocomplete="off">
                                            @if ($errors->has('contractNumber'))
                                                <div class="form-control-feedback">
                                                    {{ $errors->first('contractNumber') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group{{ $errors->has('payPurpose') ? ' has-danger' : '' }}">
                                    <label for="payPurpose">Цель оплаты</label>
                                    <select type="text" id="payPurpose" class="form-control-line form-control" name="payPurpose">
                                        <option value="погашение основной задолженности" {{old('payPurpose')=='погашение основной задолженности'? 'selected':''}}>погашение основной задолженности</option>
                                        <option value="погашение штрафов" {{old('payPurpose')=='погашение штрафов'? 'selected':''}}>погашение штрафов</option>
                                        <option value="возврат по БТИ" {{old('payPurpose')=='возврат по БТИ'? 'selected':''}}>возврат по БТИ</option>
                                        <option value="возврат при расторжении" {{old('payPurpose')=='возврат при расторжении'? 'selected':''}}>возврат при расторжении</option>
                                    </select>
                                </div>

                            </div>

                        </div>
                        <button type="submit" class="btn btn-lg btn-fill btn-warning mt-4 mb-4">Добавить</button>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
