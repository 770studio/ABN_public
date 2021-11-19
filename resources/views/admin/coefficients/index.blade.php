@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Коэффициенты субагентов</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-container">
                            <a href="{{route('coefficients.create')}}" class="btn btn-warning waves-effect waves-light">Добавить коэффициент</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap111" >
                                <thead>
                                <tr>
                                    <th class="text-center">Коэффициент</th>
                                    <th class="text-center">Количество договоров за месяц</th>
                                    <th class="text-center">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($coefficients as $coefficient)

                                    <tr>
                                        <td>{{$coefficient->coefficient}}</td>
                                        <td>{{$coefficient->contracts_count}}</td>
                                        <td class="text-center">
                                            <a href = "{{route('coefficients.edit',$coefficient->id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['coefficients.destroy', $coefficient->id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$coefficient->coefficient}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
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