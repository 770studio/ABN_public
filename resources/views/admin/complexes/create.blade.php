@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить комплекс</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">
                                {!! Form::open(['route' => 'complexes.store']) !!}

                                <div class="form-group{{ $errors->has('complex') ? ' has-danger' : '' }}">
                                    <label for="complex">Комплекс</label>
                                    <input type="text"  id="complex" class="form-control-line form-control" name="complex" value="{{ old('complex') }}" autocomplete="off">

                                    @if ($errors->has('complex'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('complex') }}
                                        </div>
                                    @endif

                                </div>


                                <div class="form-group{{ $errors->has('sort') ? ' has-danger' : '' }}">
                                    <label for="sort">Порядок сортировки</label>
                                    <input type="text" class="form-control-line form-control" name="sort" id="sort"  value="{{ old('sort') }}" autocomplete="off">
                                    @if ($errors->has('sort'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('sort') }}
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