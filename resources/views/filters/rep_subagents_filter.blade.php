<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('subagent_reporting.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">
                        {{--месяцы--}}
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="month">Месяц</label>

                                {{ Form::select('month',
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
                                 Session::get('month_selected'),

                                    [
                                    'id'=>'month',
                                    'placeholder'=>'Выбрать'
                                    ]
                                )}}
                            </div>

                        </div>
                        {{--субагенты--}}
                        <div class="col-lg-5">
                            <div class="form-group">
                                <label class="control-label" for="subagent">Субагенты</label>

                                {{ Form::select('subagent',
                                 $subagents ,
                                 Session::get('subagent'),

                                    [
                                    'id'=>'subagent',
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



        //сотрудники
        $('#subagent').multiselect({
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
                li: '<li><a class="dropdown-item itemLi-2" href="javascript:void(0);"><div class="checkboxItem radio radio-success"><label></label></div></a></li>',
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
            $('.itemLi-2 input[type=radio]').each(function(){

                var parentLbl = $(this).parent();

                if ($(this).val()>=1){
                    $(this).attr('id', 'subagent_'+$(this).val());
                    parentLbl.attr('for', 'subagent_'+$(this).val());
                }
                else{
                    $(this).parent().parent().parent().parent().hide();
                }



            });


        });

    });

</script>
