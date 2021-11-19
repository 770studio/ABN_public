<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('realForSale.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        {{--дата--}}
                        {{--<div class="col-lg-3">--}}
                            {{--<div class="form-group">--}}
                                {{--<label class="control-label" for="dateRange">Период</label>--}}

                                {{--<input id="dateRange"  name="dateRange" type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">--}}

                            {{--</div>--}}
                        {{--</div>--}}
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label">Тип недвижимости</label>
                                <div class="radio radio-success">
                                    <input type="radio" name="real_type" id="real_type_1" value="2" >
                                    <label for="real_type_1"> Жилая </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="real_type" id="real_type_2" value="1,3,4">
                                    <label for="real_type_2"> Нежилая </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="real_type" id="real_type_all" value='' checked>
                                    <label for="real_type_all"> Все </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label">Акт приема-передачи</label>
                                <div class="radio radio-success">
                                    <input type="radio" name="akt" id="akt_1" value="1" >
                                    <label for="akt_1"> Подписан </label>
                                </div>
                                <div class="radio radio-danger">
                                    <input type="radio" name="akt" id="akt_2" value="0">
                                    <label for="akt_2"> Не подписан </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="akt" id="akt_2" value="2" checked>
                                    <label for="akt_2"> Все </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label">Статус</label>
                                <div class="radio radio-success">
                                    <input type="radio" name="status" id="status_1" value="4">
                                    <label for="status_1"> Продано </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="status" id="status_2" value="1">
                                    <label for="status_2"> На реализации </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="status" id="status_3" value="0" checked>
                                    <label for="status_3"> Все </label>
                                </div>
                            </div>
                        </div>


                    </div>

                    <button class="btn btn-warning btn-lg" id="filterBtn" type="submit">Сформировать отчет</button>
                </form>



            </div>
        </div>
    </div>
</div>

{{--<script type="text/javascript">--}}
    {{--$(document).ready(function() {--}}

        {{--//календарь--}}

        {{--$(function() {--}}
            {{--$('.daterange').daterangepicker({--}}

                {{--autoUpdateInput: false,--}}
                {{--locale: {--}}
                    {{--format: 'DD.MM.YYYY',--}}
                    {{--fromLabel: "От",--}}
                    {{--toLabel: "До",--}}
                    {{--applyLabel: "Выбрать",--}}
                    {{--cancelLabel: "Отмена",--}}
                    {{--customRangeLabel: "Произвольная дата",--}}
                    {{--daysOfWeek: [--}}
                        {{--"Вс",--}}
                        {{--"Пн",--}}
                        {{--"Вт",--}}
                        {{--"Ср",--}}
                        {{--"Чт",--}}
                        {{--"Пт",--}}
                        {{--"Сб"--}}
                    {{--],--}}
                    {{--monthNames: [--}}
                        {{--"Январь",--}}
                        {{--"Февраль",--}}
                        {{--"Март",--}}
                        {{--"Апрель",--}}
                        {{--"Май",--}}
                        {{--"Июнь",--}}
                        {{--"Июль",--}}
                        {{--"Август",--}}
                        {{--"Сентябрь",--}}
                        {{--"Октябрь",--}}
                        {{--"Ноябрь",--}}
                        {{--"Декабрь"--}}
                    {{--],--}}
                    {{--firstDay: 1--}}
                {{--},--}}
                {{--ranges: {--}}

                    {{--'Январь':[moment().startOf('year'), moment().endOf('year').subtract(11,'month')],--}}
                    {{--'Февраль':[moment().endOf('year').subtract(10,'month').startOf('month'), moment().endOf('year').subtract(10,'month')],--}}
                    {{--'Март':[moment().endOf('year').subtract(9,'month').startOf('month'), moment().endOf('year').subtract(9,'month')],--}}
                    {{--'Апрель':[moment().endOf('year').subtract(8,'month').startOf('month'), moment().endOf('year').subtract(8,'month')],--}}
                    {{--'Май':[moment().endOf('year').subtract(7,'month').startOf('month'), moment().endOf('year').subtract(7,'month')],--}}
                    {{--'Июнь':[moment().endOf('year').subtract(6,'month').startOf('month'), moment().endOf('year').subtract(6,'month')],--}}
                    {{--'Июль':[moment().endOf('year').subtract(5,'month').startOf('month'), moment().endOf('year').subtract(5,'month')],--}}
                    {{--'Август':[moment().endOf('year').subtract(4,'month').startOf('month'), moment().endOf('year').subtract(4,'month')],--}}
                    {{--'Сентябрь':[moment().endOf('year').subtract(3,'month').startOf('month'), moment().endOf('year').subtract(3,'month')],--}}
                    {{--'Октябрь':[moment().endOf('year').subtract(2,'month').startOf('month'), moment().endOf('year').subtract(2,'month')],--}}
                    {{--'Ноябрь':[moment().endOf('year').subtract(1,'month').startOf('month'), moment().endOf('year').subtract(1,'month')],--}}
                    {{--'Декабрь':[moment().endOf('year').startOf('month'), moment().endOf('year')],--}}
                {{--},--}}
                {{--startDate: moment().startOf('month'),--}}
                {{--endDate: moment().endOf('month'),--}}
                {{--applyButtonClasses: 'btn-success',--}}


            {{--});--}}

            {{--$('.daterange').on('apply.daterangepicker', function(ev, picker) {--}}
                {{--$(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY'));--}}
            {{--});--}}

            {{--$('.daterange').on('cancel.daterangepicker', function(ev, picker) {--}}
                {{--$(this).val('');--}}
            {{--});--}}
        {{--});--}}



    {{--});--}}

{{--</script>--}}