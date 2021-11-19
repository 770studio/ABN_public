
<div id="periodLbl">
    <div class="row">
        <div class="col-lg-5">
            <hr>
            <h6>Период сравнения</h6>
            <span>{{\Carbon\Carbon::parse(App\Report::$back_df)->format('d.m.Y')}} - {{\Carbon\Carbon::parse(App\Report::$back_dt)->format('d.m.Y')}}</span>
            <hr>
        </div>
    </div>
</div>

