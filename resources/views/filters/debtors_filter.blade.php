<div class="row" id="filter">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="card-body">
                <form   action="{{route('debtors.makeReport')}}" id="filterForm" method="post">
                    @csrf
                    <div class="row">


                        <div class="col-lg-3">
                            <div class="form-group">
                                <label class="control-label">Просрочка оплаты</label>
                                <div class="radio radio-success">
                                    <input type="radio" name="late_pay" id="late_pay_1" value="1">
                                    <label for="late_pay_1"> более 1 дня </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="late_pay" id="late_pay_2" value="30">
                                    <label for="late_pay_2"> более 30 дней </label>
                                </div>
                                <div class="radio radio-success">
                                    <input type="radio" name="late_pay" id="late_pay_3" value="60">
                                    <label for="late_pay_3">более 60 дней </label>
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

