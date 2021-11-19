@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Сотрировка комплексов</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-container">
                            <a href="{{route('complexes.create')}}" class="btn btn-warning waves-effect waves-light">Добавить комплекс</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap111" >
                                <thead>
                                <tr>
                                    <th class="text-center">Комплекс</th>
                                    <th class="text-center">Порядок сортировки</th>
                                    <th class="text-center">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($complexes as $complex)

                                    <tr>
                                        <td>{{$complex->complex}}</td>
                                        <td>{{$complex->sort}}</td>
                                        <td class="text-center">
                                            <a href = "{{route('complexes.edit',$complex->id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['complexes.destroy', $complex->id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$complex->coefficient}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
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