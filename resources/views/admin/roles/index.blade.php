
@extends('layouts.homeLayout')


@section('content')

    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Роли пользователей</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-container">
                            <a href="{{route('role.create')}}" class="btn btn-warning waves-effect waves-light">Добавить роль</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap111" id="roles_index">
                                <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Название</th>
                                    <th class="text-center">Отображаемое имя</th>
                                    <th class="text-center">Описание</th>
                                    <th class="text-center" style="width: 30%;">Права доступа</th>
                                    <th class="text-center">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($roles as $role)

                                    <tr>
                                        <td>{{$role->id}}</td>
                                        <td>{{$role->title}}</td>
                                        <td>{{$role->display_name}}</td>
                                        <td>{{$role->description}}</td>
                                        <td>{{$role->getPermissionDisplayName()}}</td>
                                        <td class="text-center">
                                            <a href = "{{route('role.edit',$role->id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['role.destroy', $role->id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$role->title}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
                                                <i class="fas fa-window-close"></i>
                                            </button>
                                            {{Form::close()}}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>


@endsection