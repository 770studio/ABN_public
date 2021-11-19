@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Редактирование графика платежей</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">

                                {!! Form::open(['route' => ['payments.allow_edit.edit'],'method'=>'post']) !!}


                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="control-label">Разрешить группе "Консультанты" редактировать график платежей на этапе "Продано"</label>
                                        <div class="radio radio-success">
                                            <input type="radio" name="allow" id="allow_yes" value="yes"
                                            @if($role->IsAbleToEditSchedule() == 'yes')
                                                checked
                                            @endif
                                            >
                                            <label for="allow_yes"> Да </label>
                                        </div>
                                        <div class="radio radio-danger">
                                            <input type="radio" name="allow" id="allow_no" value="no"
                                                   @if($role->IsAbleToEditSchedule() == 'no')
                                                   checked
                                                    @endif
                                            >
                                            <label for="allow_no"> Нет </label>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-fill btn-warning">Сохранить</button>
                                {!! Form::close() !!}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>



    </div>


@endsection