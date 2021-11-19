<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('subagents.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        {{--дата--}}
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="dateRange">Период</label>

                                <input id="dateRange" value="{{ Session::get('date')}}" name="dateRange" type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">

                            </div>
                        </div>

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="managers">Субагенты</label>

                                {{ Form::select('subagents[]',
                                 $subagents ,
                                 Session::get('subagents'),

                                    [
                                    'id'=>'subagents',
                                    'multiple'=>true,
                                    ]
                                )}}
                            </div>
                        </div>
                    </div>

                    {{--<button class="btn btn-warning btn-lg" id="filterBtn">Сформировать отчет</button>--}}
                    <button class="btn btn-warning btn-lg" id="filterBtn" type="submit">Сформировать отчет</button>
                </form>



            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        //календарь
        $(function() {
            $('.daterange').daterangepicker({

                autoUpdateInput: false,
                locale: {
                    format: 'DD.MM.YYYY',
                    fromLabel: "От",
                    toLabel: "До",
                    applyLabel: "Выбрать",
                    cancelLabel: "Отмена",
                    customRangeLabel: "Произвольная дата",
                    daysOfWeek: [
                        "Вс",
                        "Пн",
                        "Вт",
                        "Ср",
                        "Чт",
                        "Пт",
                        "Сб"
                    ],
                    monthNames: [
                        "Январь",
                        "Февраль",
                        "Март",
                        "Апрель",
                        "Май",
                        "Июнь",
                        "Июль",
                        "Август",
                        "Сентябрь",
                        "Октябрь",
                        "Ноябрь",
                        "Декабрь"
                    ],
                    firstDay: 1
                },
                ranges: {

                    'Январь':[moment().startOf('year'), moment().endOf('year').subtract(11,'month')],
                    'Февраль':[moment().endOf('year').subtract(10,'month').startOf('month'), moment().endOf('year').subtract(10,'month')],
                    'Март':[moment().endOf('year').subtract(9,'month').startOf('month'), moment().endOf('year').subtract(9,'month')],
                    'Апрель':[moment().endOf('year').subtract(8,'month').startOf('month'), moment().endOf('year').subtract(8,'month')],
                    'Май':[moment().endOf('year').subtract(7,'month').startOf('month'), moment().endOf('year').subtract(7,'month')],
                    'Июнь':[moment().endOf('year').subtract(6,'month').startOf('month'), moment().endOf('year').subtract(6,'month')],
                    'Июль':[moment().endOf('year').subtract(5,'month').startOf('month'), moment().endOf('year').subtract(5,'month')],
                    'Август':[moment().endOf('year').subtract(4,'month').startOf('month'), moment().endOf('year').subtract(4,'month')],
                    'Сентябрь':[moment().endOf('year').subtract(3,'month').startOf('month'), moment().endOf('year').subtract(3,'month')],
                    'Октябрь':[moment().endOf('year').subtract(2,'month').startOf('month'), moment().endOf('year').subtract(2,'month')],
                    'Ноябрь':[moment().endOf('year').subtract(1,'month').startOf('month'), moment().endOf('year').subtract(1,'month')],
                    'Декабрь':[moment().endOf('year').startOf('month'), moment().endOf('year')],
                },
                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
                applyButtonClasses: 'btn-success',


            });

            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY'));
            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        });


        //субагенты
        $('#subagents').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering:true,
            filterPlaceholder: 'Поиск',
            enableClickableOptGroups: false,
            enableCollapsibleOptGroups: false,
            collapseOptGroupsByDefault: false,
            includeSelectAllOption: false,
            selectAllJustVisible: false,
            includeResetOption: true,
            nonSelectedText: 'Выбрать',
            allSelectedText: 'Выбраны все',
            resetText: 'Удалить все',
            selectAllText: 'Выбрать все',
            nSelectedText: 'выбраны',
            numberDisplayed: 1,
            buttonContainer: '<div class="btn-group-multi" />',
            templates: {

                button: '<button type="button" class="dropdown-toggle btn-light" data-toggle="dropdown"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner"></div></div> </div></button>',
                ul: '<div class="dropdown-menu"></div>',
                li: '<li><a class="dropdown-item itemLi" href="javascript:void(0);"><div class="checkboxItem"><label></label></div></a></li>',
                resetButton:'<li class="bs-actionsbox"><div class="btn-group btn-group-sm btn-block"><a href="javascript:void(0);" class="actions-btn  btn btn-light"><label class="checkbox"><input type="checkbox" value="multiselect-all"> Выбрать все</label></a><a class="btn reset-btn btn-light actions-btn"></a></div></li>'
            }
        });

    });

</script>

<script>



</script>