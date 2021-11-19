<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('forecast.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        <div class="col-lg-2 col-md-2">
                            <div class="form-group">
                                <label class="control-label" for="year">Год</label>
                                <input id="year"  name="year"  type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">

                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group">
                                <label class="control-label" for="month">Месяц</label>

                                {{ Form::select('month',
                                [
                                    '01'=>'Январь',
                                    '02'=>'Февраль',
                                    '03'=>'Март',
                                    '04'=>'Апрель',
                                    '05'=>'Май',
                                    '06'=>'Июнь',
                                    '07'=>'Июль',
                                    '08'=>'Август',
                                    '09'=>'Сентябрь',
                                    '10'=>'Октябрь',
                                    '11'=>'Ноябрь',
                                    '12'=>'Декабрь'

                                ],
                                null,

                                    [
                                    'id'=>'month',
                                    'placeholder'=>'Выбрать'
                                    ]
                                )}}
                            </div>

                        </div>


                        {{--дата--}}
                        <div class="col-lg-4 col-md-4">
                            <div class="form-group{{ $errors->has('singleDate') ? ' has-danger' : '' }}">
                                <label class="control-label" for="singleDate">Произвольная дата в месяце</label>

                                <input id="singleDate"  name="singleDate" type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать" disabled>

                                @if ($errors->has('singleDate'))
                                    <div class="form-control-feedback">
                                        {{ $errors->first('singleDate') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-warning btn-lg" id="filterBtn" type="submit">Сформировать отчет</button>
                </form>



            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // инициализировали выбор года
        $('#year').datepicker({
            format: "yyyy",
            startView: 2,
            minViewMode: 2,
            language: "ru",
            orientation: "bottom auto",
            autoclose: true

        });
        $('#month').multiselect({
            enableClickableOptGroups: false,
            enableCollapsibleOptGroups: false,
            collapseOptGroupsByDefault: false,
            includeSelectAllOption: false,
            selectAllJustVisible: false,
            includeResetOption: false,
            nonSelectedText: 'Выбрать',
            nSelectedText:'Выбрать',
            numberDisplayed: 1,
            dropDown:true,
            buttonContainer: '<div class="btn-group-multi" />',
            templates: {

                button: '<button type="button" id="dateRangeBtn" class="dropdown-toggle btn-light" data-toggle="dropdown"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner"></div></div> </div></button>',
                ul: '<div class="dropdown-menu"></div>',
                li: '<li><a class="dropdown-item itemLi" href="javascript:void(0);"><div class="checkboxItem radio radio-success"><label></label></div></a></li>',
                //resetButton:'<li class="bs-actionsbox"><div class="btn-group btn-group-sm btn-block"><a href="javascript:void(0);" class="actions-btn  btn btn-light"><label class="checkbox"><input type="checkbox" value="multiselect-all"> Выбрать все</label></a><a class="btn reset-btn btn-light actions-btn"></a></div></li>'
            }
            ,
            onChange: function(element, checked) {

                var selectedOptions = $('#month option:selected');
                if (selectedOptions.length >=1){
                    $('#singleDate').removeAttr("disabled");

                }else {

                    $('#singleDate').attr('disabled', 'disabled');

                }
            },
            onSelectAll: function () {
                //$('#dateRange').attr('disabled', 'disabled');
                $('#singleDate').removeAttr("disabled");
            },
            onDeselectAll: function () {
                //$('#dateRange').removeAttr("disabled");
                $('#singleDate').attr('disabled', 'disabled');
            }


        });

        $(function() {
            $('.itemLi input[type=radio]').each(function(){

                var parentLbl = $(this).parent();

                if ($(this).val()>=1){
                    $(this).attr('id', 'month_'+$(this).val());
                    parentLbl.attr('for', 'month_'+$(this).val());
                }
                else{
                    $(this).parent().parent().parent().parent().hide();
                }



            });


        });

        $('#month').change(function () {

            //убираем старое значение
            $('#singleDate').val('');

            var monthSelected = $('#month').val();//выбранный месяц
            var currentYear = $('#year').val();//выбранный год
            var lastDayInMonth = new Date(currentYear, monthSelected, 0).getDate();//последний день месяца


            $('#singleDate').daterangepicker({
                singleDatePicker: true,
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
                startDate: "01."+monthSelected+"." + currentYear,
                minDate: "01."+monthSelected+"." + currentYear,
                endDate: lastDayInMonth+"."+monthSelected+"." + currentYear,
                maxDate: lastDayInMonth+"."+monthSelected+"." + currentYear,
            });

            $('#singleDate').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD.MM.YYYY'));
            });


        });

    });

</script>
