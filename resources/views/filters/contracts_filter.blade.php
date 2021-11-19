<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">


                <form   action="{{route('contracts.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        {{--дата--}}
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="months">Месяцы</label>

                                {{ Form::select('months[]',
                                [
                                    '1'=>'Январь',
                                    '2'=>'Февраль',
                                    '3'=>'Март',
                                    '4'=>'Апрель',
                                    '5'=>'Май',
                                    '6'=>'Июнь',
                                    '7'=>'Июль',
                                    '8'=>'Август',
                                    '9'=>'Сентябрь',
                                    '10'=>'Октябрь',
                                    '11'=>'Ноябрь',
                                    '12'=>'Декабрь'

                                ],
                                null,

                                    [
                                    'id'=>'months',
                                    'multiple'=>true,
                                    ]
                                )}}
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group{{ $errors->has('dateRange') ? ' has-danger' : '' }}">
                                <label class="control-label" for="dateRange">Произвольная дата</label>

                                <input id="dateRange"  name="dateRange" type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">
                                @if ($errors->has('dateRange'))
                                    <div class="form-control-feedback">
                                        {{ $errors->first('dateRange') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label">Расторжение/Не вступил в силу</label>
                                <div class="radio radio-success">
                                    <input type="radio" name="has_entered" id="has_entered_yes" value="1">
                                    <label for="has_entered_yes"> Да </label>
                                </div>
                                <div class="radio radio-danger">
                                    <input type="radio" name="has_entered" id="has_entered_no" value="0">
                                    <label for="has_entered_no"> Нет </label>
                                </div>
                            </div>
                        </div>
                        {{--<div class="col-lg-3">--}}
                            {{--<div class="form-group">--}}
                                {{--<label class="control-label">Расторжение</label>--}}
                                {{--<div class="radio radio-success">--}}
                                    {{--<input type="radio" name="dissolution" id="dissolution_yes" value="1">--}}
                                    {{--<label for="dissolution_yes"> Да </label>--}}
                                {{--</div>--}}
                                {{--<div class="radio radio-danger">--}}
                                    {{--<input type="radio" name="dissolution" id="dissolution_no" value="0">--}}
                                    {{--<label for="dissolution_no"> Нет </label>--}}
                                {{--</div>--}}
                            {{--</div>--}}
                        {{--</div>--}}


                    </div>

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

                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
                applyButtonClasses: 'btn-success',


            });

            $('.daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY') + ' - ' + picker.endDate.format('DD.MM.YYYY'));
                $('.btn-group-multi button').attr('disabled', 'disabled');
            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('.btn-group-multi button').removeAttr("disabled");
            });
        });

        //инициализируем селект месяцы

        $('#months').multiselect({
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

                button: '<button type="button" id="dateRangeBtn" class="dropdown-toggle btn-light" data-toggle="dropdown"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner"></div></div> </div></button>',
                ul: '<div class="dropdown-menu"></div>',
                li: '<li><a class="dropdown-item itemLi" href="javascript:void(0);"><div class="checkboxItem"><label></label></div></a></li>',
                resetButton:'<li class="bs-actionsbox"><div class="btn-group btn-group-sm btn-block"><a href="javascript:void(0);" class="actions-btn  btn btn-light"><label class="checkbox"><input type="checkbox" value="multiselect-all"> Выбрать все</label></a><a class="btn reset-btn btn-light actions-btn"></a></div></li>'
            },
            onChange: function(element, checked) {

                var selectedOptions = $('#months option:selected');
                if (selectedOptions.length >=1){

                    $('#dateRange').attr('disabled', 'disabled');
                }else {

                    $('#dateRange').removeAttr("disabled");

                }
            },
            onSelectAll: function () {
                $('#dateRange').attr('disabled', 'disabled');
            },
            onDeselectAll: function () {
                $('#dateRange').removeAttr("disabled");
            }


        });


        $( ".reset-btn" ).click(function() {
            $('#dateRange').removeAttr("disabled");
        });
    });

</script>