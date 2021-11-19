<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('sales.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">

                        {{--дата--}}
                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label" for="singleDate">Дата</label>

                                <input id="singleDate"  name="singleDate" type="text" autocomplete="off" class="form-control  daterange" placeholder="Выбрать">

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
                    firstDay: 1,

                },



            });

            $('.daterange').on('apply.daterangepicker', function(ev, picker) {


                $(this).val(picker.startDate.format('DD.MM.YYYY'));



            });


        });



    });

</script>