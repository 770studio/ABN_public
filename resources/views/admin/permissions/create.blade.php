
@extends('layouts.homeLayout')


@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить разрешение</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">

                                {!! Form::open(['route' => 'permission.store']) !!}

                                {{--@include('layouts.cabinet.errors')--}}

                                <div class="form-group{{ $errors->has('title') ? ' has-danger' : '' }}">
                                    <label for="title">Название разрешения</label>
                                    <input type="text"  id="title" class="form-control-line form-control" name="title" value="{{ old('title') }}" autocomplete="off">

                                    @if ($errors->has('title'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('title') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('display_name') ? ' has-danger' : '' }}">
                                    <label for="display_name">Отображаемое имя</label>
                                    <input type="text"  id="display_name" class="form-control-line form-control" name="display_name" value="{{ old('display_name') }}" autocomplete="off">

                                    @if ($errors->has('display_name'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('display_name') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('description') ? ' has-danger' : '' }}">
                                    <label for="description">Описание</label>
                                    <textarea class="form-control-line form-control" name="description" id="description"  rows="6">{{ old('description') }}</textarea>
                                    @if ($errors->has('description'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('description') }}
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