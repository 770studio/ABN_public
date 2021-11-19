<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentsSchedule;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

// .\vendor\bin\phpunit .\tests\Feature\
/*
 метод для формирования графика платежей через API


*/







class PaymentScheduleAutoBuildAPITest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
     return;
    }


    /**
     * А) 100% оплата
     *
     */
    public function testType1()
    {

        $lead_id = 13125023;

        $ps = new PaymentsSchedule();


        dd(4444444666666666);



    }

}
