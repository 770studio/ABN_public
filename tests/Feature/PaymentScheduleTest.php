<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PSext extends \App\Http\Controllers\PaymentsSchedule
{
}

class PaymentScheduleTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSearchByLeadId()
    {

        return;

        $r = new Request(['stext'=> 'СГ/9/О-1400/1430/1431/3/18/АБН', 'search_by' => ['lead_id' => 15141469 ] ]);
        $r = new Request( ['stext'=>'СГ/9/О-1400/1430/1431/3/18/АБН', 'search_by'=>['lead_id' => 15141469 ] ]);

        //api_searchContract(Request $request)

        $ps = new PSext;
        $s = $ps->api_searchContract($r);


        TestCase::assertTrue($s['lead']->lead_id == 15141469);
    }
}
