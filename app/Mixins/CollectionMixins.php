<?php
namespace App\Mixins;

Class CollectionMixins {

    public function ScheduleTotalSum() {

        return function(  ) {
               return  round((float) $this->sum(function ($sched) {
                    return $sched->sum_total > 0 ? $sched->sum_total : $sched->sum_payment; // сумма долга с процентами либо без
                }), 2);

        };
    }
}
