@extends('layouts.homeLayout')
@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Добавить пользователя</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">

                                {!! Form::open(['route' => ['user.store'],'method'=>'post']) !!}


                                <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
                                    <label for="name">ФИО</label>
                                    <input type="text"  id="name" class="form-control-line form-control" name="name" value="{{old('name')}}" autocomplete="off">

                                    @if ($errors->has('name'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('name') }}
                                        </div>
                                    @endif

                                </div>

                                <div class="form-group{{ $errors->has('role_id') ? ' has-danger' : '' }}">
                                    <label for="role_id">Роль</label>
                                    <select name="role_id" class="form-control" id="role_id">
                                        <option value="">Выбрать роль</option>
                                        @foreach($roles as $id=>$role)
                                            <option value="{{$id}}">{{$role}}</option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('role_id'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('role_id') }}
                                        </div>
                                    @endif

                                </div>

                                <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }}">
                                    <label for="email">Email</label>
                                    <input type="text"  id="email" class="form-control-line form-control" name="email" value="{{old('email')}}" autocomplete="off">

                                    @if ($errors->has('email'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('email') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                                    <label for="password">Пароль</label>
                                    <input type="password"  id="password" class="form-control-line form-control" name="password"  autocomplete="off">

                                    @if ($errors->has('password'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('password') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('password_confirmation') ? ' has-danger' : '' }}">
                                    <label for="password_confirmation">Еще раз пароль</label>
                                    <input type="password"  id="password_confirmation" class="form-control-line form-control" name="password_confirmation"  autocomplete="off">

                                    @if ($errors->has('password_confirmation'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('password_confirmation') }}
                                        </div>
                                    @endif

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