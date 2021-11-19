<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('workout.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-lg-1">
                            <div class="form-group">
                                <label class="control-label" for="year">Год</label>
                                <input id="year"  name="year"  type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">

                            </div>
                        </div>
                        <div class="col-lg-3">
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
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="managers">Сотрудники</label>

                                {{ Form::select('managers[]',
                                 $managers ,
                                 Session::get('managers'),

                                    [
                                    'id'=>'managers',
                                    'multiple'=>true,
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


        // инициализировали выбор года
        $('#year').datepicker({
            format: "yyyy",
            startView: 2,
            minViewMode: 2,
            language: "ru",
            orientation: "bottom auto",
            autoclose: true

        });

        //инициализируем селект месяцы

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






        //сотрудники
        $('#managers').multiselect({
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