<?php

namespace App\Providers;


use App\Schedule;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use App\Penalty;
use App\PenaltyCorrection;
use App\PenaltyNoCorrection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
 //  лог всех mysql запросов
/*
             DB::listen(function($query) {
                File::append(
                    storage_path('/logs/query.log'),
                    date("r") . ":" . $query->time . ":" . $query->sql . ' [' . implode(', ', $query->bindings) . ']' . PHP_EOL
                );
            });
*/

        //dd(Config::get('app.lc_all'));
        setlocale(LC_ALL, Config::get('app.lc_all'));
        Carbon::setLocale(Config::get('app.locale'));

/*        Schedule::updated(function ( $data ) {



        });*/

/*        $this->app->singleton('PenaltyChargeFacade', function($app) {
                return new PenaltyChargeService();
        });*/

/*        https://laravel.com/docs/5.8/eloquent#events
        When a new model is saved for the first time, the creating and created events will fire.
        If a model already existed in the database and the save method is called, the updating / updated events will fire.
        However, in both cases, the saving / saved events will fire.*/
        Penalty::created  (function ( $data ) {
           //dd($data, 99999999999999 , Auth::check() ?  Auth::user()->id : null  );
           // PenaltyNoCorrection::create( $data->toArray() );


        });
        Penalty::updating (function ( $data ) {

          //  if(!Auth::user()->id)  throw new Exception('Не авторизован?? '  );  а крон ?

            if(Auth::check()) {
                $data->employee_id =  Auth::user()->id;
            } else $data->employee_id = 0; // крон


            if($data->employee_id) { // ручная коррекция
                $c = new PenaltyCorrection;
                $c->employee_id = $data->employee_id ;
                $c->penalty_id =  $data->id;
                $c->correction = serialize($data->getOriginal()) ;

                return $c->save();
            } else {
                // авто коррекция, приход платежа, пересчет при изменении ставки и т.д.
                //  так  делать нельзя так как в PenaltyNoCorrection попадут ручные коррекции сделанные ранее
                // приход платежей учитывется параллельно (двойной учет )
                // пересчет при изменении ставки то же параллельно, через двойной учет
              //   PenaltyNoCorrection::find( $data->id )->update( $data->toArray() );

            }

            return true;
        });





        Collection::mixin( new \App\Mixins\CollectionMixins() );


    }
}
