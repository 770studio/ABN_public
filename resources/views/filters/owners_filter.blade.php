<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('owners.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">


                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="complex">Жилой комплекс</label>

                                {{ Form::select('complex',
                                  $complexes ,
                                   null,

                                    [
                                    'id'=>'complex',
                                    'placeholder'=>'Выбрать'
                                    ]
                                )}}
                            </div>
                        </div>

                        <div class="col-lg-3" id="house_num_container">
                            <div class="form-group">
                                <label class="control-label" for="complex_address">Номер дома</label>

                                {{ Form::select('complex_address[]',
                                   [] ,
                                   null,

                                    [
                                    'id'=>'complex_address',
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

        //жилые комплексы
        $('#complex').multiselect({
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

            },

        });

        $('#house_num_container  button').attr('disabled', 'disabled');
    });

    $(function() {
        $('.itemLi input[type=radio]').each(function(){

            var parentLbl = $(this).parent();

            if (parentLbl.attr('title') == 'Выбрать'){
                $(this).parent().parent().parent().parent().hide();
            }

            if ($(this).val()!=null){
                $(this).attr('id', $(this).val());
                parentLbl.attr('for', $(this).val());
            }



        });


    });
    $('#complex_address').multiselect({
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
    $('#complex').change(function(){

        var complex = $(this).val();
        if(complex){
            var complexEncode = encodeURIComponent(complex);
            $.ajax({
                type:"GET",
                url:"{{route('address-list')}}?complex="+complexEncode,
                success:function(res){
                    if(res){
                        $("#complex_address").empty();

                        $.each(res,function(key,value){
                            $("#complex_address").append('<option value="'+value+'">'+value+'</option>');
                        });

                       $('#complex_address').multiselect(
                           'rebuild'
                       );


                        $('#house_num_container  button').removeAttr("disabled");

                    }else{
                        $("#complex_address").empty();
                    }
                }
            });
        }else{
            $("#complex_address").empty();
        }
    });


</script>