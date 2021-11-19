@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Профиль</h3>
            </div>
        </div>

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">

                        <strong>ФИО: </strong><span>{{$user->name}}</span>
                            <br><br>
                        <strong>Email/логин: </strong><span>{{$user->email}}</span>

                        <hr>

                    <button type="button" data-toggle="modal" data-target="#changePassModal" id="changePassProfileBtn" class="btn btn-fill btn-warning waves-effect waves-light">
                        Смена пароля</button>




                </div>
            </div>
        </div>


    </div>
    </div>
    {{--смена пароля--}}
    <div id="changePassModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" style="display: none;" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Смена пароля</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                {!! Form::open(['route' => ['profile.changePassword'],'method'=>'put']) !!}
                <div class="modal-body">
                    @if($errors->has('password'))

                        <script>
                            $('#changePassModal').modal('show');
                        </script>
                    @endif
                    <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                        <label>Пароль</label>
                        <input type="password"  class="form-control-line form-control" name="password" value="{{ old('password') }}" autocomplete="off">

                        @if ($errors->has('password'))
                            <div class="form-control-feedback">
                                {{ $errors->first('password') }}
                            </div>
                        @endif
                    </div>
                    <div class="form-group{{ $errors->has('password_confirmation') ? ' has-danger' : '' }}">
                        <label>Пароль еще раз</label>
                        <input type="password"  class="form-control-line form-control" name="password_confirmation"  autocomplete="off">

                        @if ($errors->has('password_confirmation'))
                            <div class="form-control-feedback">
                                {{ $errors->first('password_confirmation') }}
                            </div>
                        @endif
                    </div>



                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-fill btn-success waves-effect waves-light">Изменить пароль</button>
                </div>
                {!! Form::close() !!}
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

@endsection