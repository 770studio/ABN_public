@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Субагенты</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">


                        <div class="btn-container">
                            <a href="{{route('subagent.create')}}" class="btn btn-warning">Добавить субагента</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Директор</th>
                                    <th>№ договора</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($subagents as $subagent)

                                    <tr>
                                        <td>{{$subagent->id}}</td>
                                        <td><a class="sa_item_link" href="{{route('subagent.edit',$subagent->id)}}">{{$subagent->name}}</a></td>
                                        <td>{{$subagent->head_name}}</td>
                                        <td>№{{$subagent->sub_contract_number}} <span class="sa_contract_date"> от {{\Carbon\Carbon::createFromFormat('Y-m-d',$subagent->sub_contract_date)->format('d.m.Y')}}</span></td>
                                        <td>
                                            <a href = "{{route('subagent.edit',$subagent->id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['subagent.destroy', $subagent->id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$subagent->name}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
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
