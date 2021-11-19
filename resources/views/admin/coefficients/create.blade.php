@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить коэффициент</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">
                                {!! Form::open(['route' => 'coefficients.store']) !!}

                                <div class="form-group{{ $errors->has('coefficient') ? ' has-danger' : '' }}">
                                    <label for="coefficient">Коэффициент</label>
                                    <input type="text"  id="coefficient" class="form-control-line form-control" name="coefficient" value="{{ old('coefficient') }}" autocomplete="off">

                                    @if ($errors->has('coefficient'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('coefficient') }}
                                        </div>
                                    @endif

                                </div>


                                <div class="form-group{{ $errors->has('contracts_count') ? ' has-danger' : '' }}">
                                    <label for="contracts_count">Количество договоров за месяц</label>
                                    <input type="text" class="form-control-line form-control" name="contracts_count" id="contracts_count"  value="{{ old('contracts_count') }}" autocomplete="off">
                                    @if ($errors->has('contracts_count'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('contracts_count') }}
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