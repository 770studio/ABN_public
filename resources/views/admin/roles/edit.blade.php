
@extends('layouts.homeLayout')


@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Редактировать роль</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-6">

                                {!! Form::open(['route' => ['role.update',$role->id],'method'=>'put']) !!}

                                <div class="form-group{{ $errors->has('title') ? ' has-danger' : '' }}">
                                    <label for="title">Название роли</label>
                                    <input type="text"  id="title" class="form-control-line form-control" name="title" value="{{$role->title}}" autocomplete="off">

                                    @if ($errors->has('title'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('title') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('permissions') ? ' has-danger' : '' }}">
                                    <label>Разрешения</label><br>
                                    @foreach($permissions as $permission)
                                        <div class="col-6">
                                            <div class="form-check">
                                                {!! Form::checkbox('permissions[]',
                                                 $permission->id,
                                                 null,
                                                 [
                                                  'class' => 'form-check-input',
                                                  'id'=>'permission_' . $permission->id,
                                                  in_array($permission->id,$selectedPerms)?'checked':''
                                                 ]
                                                 ) !!}
                                                {!! Form::label('permission_'.$permission->id,
                                                 $permission->display_name,
                                                 [
                                                 'class'=>'form-check-label'
                                                 ]
                                                 ) !!}

                                            </div>
                                        </div>

                                    @endforeach



                                </div>
                                <div class="form-group{{ $errors->has('display_name') ? ' has-danger' : '' }}">
                                    <label for="display_name">Отображаемое имя</label>
                                    <input type="text"  id="display_name" class="form-control-line form-control" name="display_name" value="{{$role->display_name}}" autocomplete="off">

                                    @if ($errors->has('display_name'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('display_name') }}
                                        </div>
                                    @endif

                                </div>
                                <div class="form-group{{ $errors->has('description') ? ' has-danger' : '' }}">
                                    <label for="description">Описание</label>
                                    <textarea class="form-control-line form-control" name="description" id="description"  rows="6">{{$role->description}}</textarea>
                                    @if ($errors->has('description'))
                                        <div class="form-control-feedback">
                                            {{ $errors->first('description') }}
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