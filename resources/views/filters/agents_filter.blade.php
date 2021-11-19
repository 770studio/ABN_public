<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('agents.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        {{--дата--}}
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
                                <label class="control-label" for="principal">Собственник</label>

                                {{ Form::select('principal',
                                  $principals ,
                                 null,

                                    [
                                    'id'=>'principal',
                                    'placeholder'=>'Выбрать'
                                    ]
                                )}}
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

            });

            $('.daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');

            });
        });

        //сотрудники
        $('#principal').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering:true,
            filterPlaceholder: 'Поиск',
            enableClickableOptGroups: false,
            enableCollapsibleOptGroups: false,
            collapseOptGroupsByDefault: false,
            includeSelectAllOption: false,
            selectAllJustVisible: false,
            includeResetOption: false,
            nonSelectedText: 'Выбрать',
            nSelectedText:'Выбрать',
            numberDisplayed: 1,
            buttonContainer: '<div class="btn-group-multi" />',
            templates: {


                button: '<button type="button" id="dateRangeBtn" class="dropdown-toggle btn-light" data-toggle="dropdown"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner"></div></div> </div></button>',
                ul: '<div class="dropdown-menu"></div>',
                li: '<li><a class="dropdown-item itemLi" href="javascript:void(0);"><div class="checkboxItem radio radio-success"><label></label></div></a></li>',
                //resetButton:'<li class="bs-actionsbox"><div class="btn-group btn-group-sm btn-block"><a href="javascript:void(0);" class="actions-btn  btn btn-light"><label class="checkbox"><input type="checkbox" value="multiselect-all"> Выбрать все</label></a><a class="btn reset-btn btn-light actions-btn"></a></div></li>'
            }
        });

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

</script>