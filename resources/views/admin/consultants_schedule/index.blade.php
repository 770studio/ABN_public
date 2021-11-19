@extends('layouts.homeLayout')
@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 col-8 align-self-center">
                <h3 class="text-themecolor mb-0 mt-0">График работы консультантов</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">
                        <button class="btn btn-success mt-3 mb-4"
                                onclick="location.href='{{route('consultants_schedule.index',[$currentMonth,$currentYear])}}'">
                            {{$monthRus}} {{$currentYear}}
                        </button>
                        <button class="btn btn-success mt-3 mb-4"
                                onclick="location.href='{{route('consultants_schedule.index',[$nextMonth,$nextMonthYear])}}'">
                            {{$nextMonthRus}} {{$nextMonthYear}}
                        </button>


                        <div class="table-responsive tableFixHead" id="consultants_schedule_table">
                            <table class="table table-bordered no-wrap111">
                                <thead>
                                <tr class="text-center d-flex">
                                    <th class="col-fixed" rowspan="2">
                                        @if(request()->route()->month && request()->route()->year)
                                            @switch(request()->route()->month)
                                                @case (1)
                                                @php($monthRus = 'Январь')
                                                @break

                                                @case(2)
                                                @php($monthRus = 'Февраль')
                                                @break

                                                @case(3)
                                                @php($monthRus = 'Март')
                                                @break

                                                @case(4)
                                                @php($monthRus = 'Апрель')
                                                @break

                                                @case(5)
                                                @php($monthRus = 'Май')
                                                @break

                                                @case(6)
                                                @php($monthRus = 'Июнь')
                                                @break

                                                @case(7)
                                                @php($monthRus = 'Июль')
                                                @break

                                                @case(8)
                                                @php($monthRus = 'Август')
                                                @break

                                                @case(9)
                                                @php($monthRus = 'Сентябрь')
                                                @break

                                                @case(10)
                                                @php($monthRus = 'Октябрь')
                                                @break

                                                @case(11)
                                                @php($monthRus = 'Ноябрь')
                                                @break

                                                @case(12)
                                                @php($monthRus = 'Декабрь')
                                                @break
                                            @endswitch
                                            {{$monthRus}} {{request()->route()->year}}
                                        @else
                                            {{$monthRus}} {{$currentYear}}
                                        @endif
                                    </th>
                                    @foreach($monthCalendar as $day)
                                        <th class="col">{{$day['day_of_week']}}</th>
                                    @endforeach
                                    <th class="col-tel" rowspan="2"></th>
                                </tr>
                                <tr class="text-center d-flex">
                                    <td class="col-fixed" id="fixed-td">ФИО</td>
                                    @foreach($monthCalendar as $day)
                                        <th class="col"
                                            id="day_{{$day['day']}}_{{$day['month']}}_{{$day['year']}}">{{$day['day']}}</th>
                                    @endforeach
                                    <td class="col-tel">Телефон</td>
                                </tr>
                                </thead>
                                <tbody>


                                @foreach($consultants->groupBy('department') as $dep=>$consultantsByDep)
                                    <tr class="text-center d-flex" style="background: #d2d6d2;">
                                        <td class="col-fixed" style="background: #d2d6d2;border: none;outline: none;">
                                            <b>{{$dep}}</b></td>
                                    </tr>
                                    @foreach($consultantsByDep as $consultant)
                                        <tr class="text-center d-flex">
                                            <td class="col-fixed">{{$consultant->user_name}}</td>
                                            @foreach($monthCalendar as $day)
                                                @php($schedule_val = $consultant->getScheduleItem($day['day'],$day['month'],$day['year']))

                                                @if($schedule_val == 'выходной')
                                                    <td class="col col-day-off">
                                                @elseif($schedule_val == 'отпуск')
                                                    <td class="col col-holiday">
                                                @elseif($schedule_val == 'больничный')
                                                    <td class="col col-hospital">
                                                @elseif($schedule_val != null)
                                                    <td class="col col-schedule">
                                                @else
                                                    <td class="col">
                                                        @endif

                                                        <select
                                                            id="work-time-select-{{$consultant->id}}-{{$day['day']}}-{{$day['month']}}-{{$day['year']}}"
                                                            name="work_time_select"
                                                            class="form-control work-time-select
                                                                   @if($schedule_val == 'выходной')
                                                                col-day-off
                                                                    @elseif($schedule_val == 'отпуск')
                                                                col-holiday
                                                                    @elseif($schedule_val == 'больничный')
                                                                col-hospital
                                                                    @elseif($schedule_val != null)
                                                                col-schedule
                                                                    @endif
                                                                "
                                                            data-consultant-id="{{$consultant->id}}"
                                                            data-department-id="{{$consultant->department_id}}"
                                                            data-day="{{$day['day']}}"
                                                            data-month="{{$day['month']}}"
                                                            data-year="{{$day['year']}}"


                                                        >
                                                            <option value="">Выбрать</option>
                                                            <option
                                                                value="9:00-15:45" {{$schedule_val == "9:00-15:45" ? " selected":"" }}>
                                                                9:00-15:45
                                                            </option>
                                                            <option
                                                                value="9:00-16:45" {{$schedule_val == "9:00-16:45" ? " selected":"" }}>
                                                                9:00-16:45
                                                            </option>
                                                            <option
                                                                value="9:00-17:00" {{$schedule_val == "9:00-17:00" ? " selected":"" }}>
                                                                9:00-17:00
                                                            </option>
                                                            <option
                                                                value="9:00-18:00" {{$schedule_val == "9:00-18:00" ? " selected":"" }}>
                                                                9:00-18:00
                                                            </option>
                                                            <option
                                                                value="10:00-16:00" {{$schedule_val == "10:00-16:00" ? " selected":"" }}>
                                                                10:00-16:00
                                                            </option>
                                                            <option
                                                                value="10:00-19:00" {{$schedule_val == "10:00-19:00" ? " selected":"" }}>
                                                                10:00-19:00
                                                            </option>
                                                            <option
                                                                value="11:00-19:00" {{$schedule_val == "11:00-19:00" ? " selected":"" }}>
                                                                11:00-19:00
                                                            </option>
                                                            <option
                                                                value="выходной" {{$schedule_val == "выходной" ? " selected":"" }}>
                                                                выходной
                                                            </option>
                                                            <option
                                                                value="отпуск" {{$schedule_val == "отпуск" ? " selected":"" }}>
                                                                отпуск
                                                            </option>
                                                            <option
                                                                value="больничный" {{$schedule_val == "больничный" ? " selected":"" }}>
                                                                больничный
                                                            </option>
                                                            <option
                                                                value="empty" {{$schedule_val == "empty" ? " selected":"" }}>
                                                                пустое
                                                            </option>
                                                        </select>
                                                    </td>
                                                    @endforeach
                                                    <td class="col-tel">{{$consultant->phone}}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>

        $('.work-time-select').change(function () {

            var id = $(this).data('consultant-id');
            var day = $(this).data('day');
            var month = $(this).data('month');
            var year = $(this).data('year');
            var work_time = $(this).val();
            var department_id = $(this).data('department-id');

            $.ajax({
                url: "{{route('consultants_schedule.set.ajax')}}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "consultant_id": id,
                    "department_id": department_id,
                    "schedule_day": day,
                    "schedule_month": month,
                    "schedule_year": year,
                    "work_time_select": work_time

                },
                success: function (response) {

                    if (response.success == 'ok') {
                        $.toast({
                            heading: 'Ок!',
                            text: 'Записано',
                            position: 'top-right',
                            loaderBg: '#37a650',
                            icon: 'success',
                            hideAfter: 5000
                        });

                        var selectedDay = $('#work-time-select-' + response.id + '-' + response.day + '-' + response.month + '-' + response.year);
                        selectedDay.removeClass();
                        selectedDay.parent('td').removeClass();
                        selectedDay.addClass('form-control work-time-select ' + response.color_class);
                        selectedDay.parent('td').addClass('col ' + response.color_class);
                    }
                },
            });

        });


        $(document).ready(function () {
                @if(Illuminate\Support\Facades\Session::get('schedule_day'))
            var today = '{{Illuminate\Support\Facades\Session::get('schedule_day')}}';
                @else
            var today = '{{\Carbon\Carbon::now()->day.'_'.\Carbon\Carbon::now()->month.'_'.\Carbon\Carbon::now()->year}}';
            @endif
            //var offsetScroll = $('#fixed-td').width()+30;
            var scrollTo = $('#day_' + today).position().left - 210;

            $('.table-responsive').animate({'scrollLeft': scrollTo}, 50);


            $(window).scroll(function() {
                // if ($(this).scrollTop() > 150){
                //     $('#consultants_schedule_table').css('margin-top',$(this).scrollTop()-140 + 'px');
                // }
                // else{
                //     $('#consultants_schedule_table').removeClass('tableFixHead');
                // }
            });

        });


    </script>


@endsection
