@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">Сторонние платежи</h3>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">


                        <div class="btn-container">
                            <a href="{{route('income_pays_payments.create')}}" class="btn btn-warning">Добавить платеж</a>
                        </div>
                        @if($payments->count()>0)
                        <div class="table-responsive">
                            <table class="table table-bordered no-wrap1" id="incomePaysTable">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ИНН клиента</th>
                                    <th>Имя клиента</th>
                                    <th>ИНН организации</th>
                                    <th>Дата документа</th>
                                    <th>Входящий номер</th>
                                    <th>Дата платежа</th>
                                    <th>Сумма платежа</th>
                                    <th>Номер договора</th>
                                    <th>Дата договора</th>
                                    <th>Цель оплаты</th>
                                    <th>Номер сделки</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td class="p-id">{{$payment->id}}</td>
                                        <td>{{$payment->customerINN}}</td>
                                        <td>{{$payment->customerName}}</td>
                                        <td>{{$payment->orgINN}}</td>
                                        <td class="date">{{$payment->docDate}}</td>
                                        <td>{{$payment->incomNumber}}</td>
                                        <td class="date">{{$payment->incomDate}}</td>
                                        <td>{{$payment->sum}}</td>
                                        <td>{{$payment->contractNumber}}</td>
                                        <td class="date">{{$payment->contractDate}}</td>
                                        <td>{{$payment->payPurpose}}</td>
                                        <td>{{$payment->lead_id}}</td>
                                        <td class="actions">
                                            <a href = "{{route('income_pays_payments.edit',$payment->incomp_id)}}" class="btn  btn-outline-success" data-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            {{Form::open(['route'=>['income_pays_payments.destroy', $payment->incomp_id], 'method'=>'delete', 'id'=>'delete_form'])}}
                                            <button onclick="return confirm('Вы уверены? Удаляем {{$payment->id}}')" type="submit" class="btn  btn-outline-danger" data-toggle="tooltip" title="Удалить">
                                                <i class="fas fa-window-close"></i>
                                            </button>
                                            {{Form::close()}}
                                        </td>
                                    </tr>

                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                        @else
                        <div class="alert alert-danger">Платежей не найдено</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>


@endsection
