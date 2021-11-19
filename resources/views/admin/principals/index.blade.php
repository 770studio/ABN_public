@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Правообладатели</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-container">
                            <a href="{{route('principals.create')}}" class="btn btn-warning waves-effect waves-light">Добавить правообладателя</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap111" >
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Правообладатель</th>
                                    <th>№ договора</th>
                                    <th>Дата договора</th>
                                    <th>Директор</th>
                                    <th>Директор(р.п.)</th>
                                    <th>Адрес</th>
                                    <th>Реквизиты</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($principals as $principal)

                                    <tr>
                                        <td>{{$principal->id}}</td>
                                        <td>{{$principal->name}}</td>
                                        <td>{{$principal->agentcontract_number}}</td>
                                        <td>{{\Carbon\Carbon::createFromFormat('Y-m-d',$principal->agentcontract_date)->format(' d.m.Y')}}</td>
                                        <td>{{$principal->head_name}}</td>
                                        <td>{{$principal->head_name_2}}</td>
                                        <td>{{$principal->adress}}</td>
                                        <td width="20%">{{$principal->requisites}}</td>


                                        <td class="text-center" width="10%">
                                            <a href = "{{route('principals.edit',$principal->id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['principals.destroy', $principal->id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$principal->name}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
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