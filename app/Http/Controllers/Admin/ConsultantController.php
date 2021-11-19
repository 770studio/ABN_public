<?php

namespace App\Http\Controllers\Admin;

use App\AbnUser;
use App\ConsultantsSchedule;
use App\ConsultantsScheduleFlag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;


class ConsultantController extends Controller
{
    public function consultantsScheduleIndex($month = null,$year = null){

        $consultants = AbnUser::where('is_active','=',1)
            ->whereIn('department',['Отдел продаж','Отдел оформления сделок','Ипотечные брокеры'])
            ->orderBy('user_name','asc')
            ->get();



        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $lastDayOfCurrentMonth = $now->endOfMonth()->format('d');

        if ($currentMonth != 12){
            $nextMonth = (string)($currentMonth+1);
            $nextYear = $currentYear;
        }
        else{
            $nextMonth = (string)($currentMonth+1);
            $nextYear = (string)($currentYear+1);
        }

        $next = $nextMonth.','.$nextYear;//Carbon::now()->addMonth()->format('m,Y');
        $nextArr = explode(',',$next);
        //dd($next);
        if ($month != null && $year != null){

//            Session::forget('schedule_day');

            $lastDayOfMonth = Carbon::parse('1.'.$month.'.'.$year)->endOfMonth()->format('d');

            for ($i = 1; $i <= $lastDayOfMonth; $i++)
            {
                $dayOfWeek = Carbon::parse($i.'.'.$month.'.'.$year)->format('l');

                switch ($dayOfWeek){
                    case 'Monday':
                        $dayOfWeekRus = 'Понедельник';
                        break;

                    case 'Tuesday':
                        $dayOfWeekRus = 'Вторник';
                        break;
                    case 'Wednesday':
                        $dayOfWeekRus = 'Среда';
                        break;
                    case 'Thursday':
                        $dayOfWeekRus = 'Четверг';
                        break;
                    case 'Friday':
                        $dayOfWeekRus = 'Пятница';
                        break;
                    case 'Saturday':
                        $dayOfWeekRus = 'Суббота';
                        break;
                    case 'Sunday':
                        $dayOfWeekRus = 'Воскресение';
                        break;
                }


                $monthCalendar[] = array(
                    'year'=>  $year,
                    'month'=> $month,
                    'day' => $i,
                    'day_of_week' => $dayOfWeekRus
                );


            }

        }
        else{
            for ($i = 1; $i <= $lastDayOfCurrentMonth; $i++)
            {
                $dayOfWeek = Carbon::parse($i.'.'.$currentMonth.'.'.$currentYear)->format('l');

                switch ($dayOfWeek){
                    case 'Monday':
                        $dayOfWeekRus = 'Понедельник';
                        break;

                    case 'Tuesday':
                        $dayOfWeekRus = 'Вторник';
                        break;
                    case 'Wednesday':
                        $dayOfWeekRus = 'Среда';
                        break;
                    case 'Thursday':
                        $dayOfWeekRus = 'Четверг';
                        break;
                    case 'Friday':
                        $dayOfWeekRus = 'Пятница';
                        break;
                    case 'Saturday':
                        $dayOfWeekRus = 'Суббота';
                        break;
                    case 'Sunday':
                        $dayOfWeekRus = 'Воскресение';
                        break;
                }


                $monthCalendar[] = array(
                    'year'=>  $currentYear,
                    'month'=> $currentMonth,
                    'day' => $i,
                    'day_of_week' => $dayOfWeekRus
                );


            }
        }
        switch ($currentMonth){
            case 1:
                $monthRus = 'Январь';
                break;

            case 2:
                $monthRus = 'Февраль';
                break;
            case 3:
                $monthRus = 'Март';
                break;
            case 4:
                $monthRus = 'Апрель';
                break;
            case 5:
                $monthRus = 'Май';
                break;
            case 6:
                $monthRus = 'Июнь';
                break;
            case 7:
                $monthRus = 'Июль';
                break;
            case 8:
                $monthRus = 'Август';
                break;
            case 9:
                $monthRus = 'Сентябрь';
                break;
            case 10:
                $monthRus = 'Октябрь';
                break;
            case 11:
                $monthRus = 'Ноябрь';
                break;
            case 12:
                $monthRus = 'Декабрь';
                break;
        }
        switch ($nextArr[0]){
            case 1:
                $nextMonthRus = 'Январь';
                break;

            case 2:
                $nextMonthRus = 'Февраль';
                break;
            case 3:
                $nextMonthRus = 'Март';
                break;
            case 4:
                $nextMonthRus = 'Апрель';
                break;
            case 5:
                $nextMonthRus = 'Май';
                break;
            case 6:
                $nextMonthRus = 'Июнь';
                break;
            case 7:
                $nextMonthRus = 'Июль';
                break;
            case 8:
                $nextMonthRus = 'Август';
                break;
            case 9:
                $nextMonthRus = 'Сентябрь';
                break;
            case 10:
                $nextMonthRus = 'Октябрь';
                break;
            case 11:
                $nextMonthRus = 'Ноябрь';
                break;
            case 12:
                $nextMonthRus = 'Декабрь';
                break;
        }



        return view('admin.consultants_schedule.index',[
            'consultants'=>$consultants,
            'monthCalendar'=>$monthCalendar,
            'monthRus'=>$monthRus,
            'currentYear'=>$currentYear,
            'nextMonthRus'=>$nextMonthRus,
            'nextMonthYear'=>$nextArr[1],
            'currentMonth'=>$currentMonth,
            'nextMonth'=>$nextArr[0]
        ]);
    }

    public function consultantsScheduleSet(Request $request){



        $rules =  [
            'schedule_year' => 'required',
            'schedule_month' => 'required',
            'schedule_day' => 'required',
            'consultant_id' => 'required',
            'work_time_select'=>'required',
            'department_id'=>'required',
        ];
        $this->validate($request, $rules);

        try {

            $schedule = ConsultantsSchedule::where('abn_user_id',$request->get('consultant_id'))
                ->where('year',$request->get('schedule_year'))
                ->where('month',$request->get('schedule_month'))
                ->where('day',$request->get('schedule_day'))
                ->first();

            if (!$schedule){
                $schedule = new ConsultantsSchedule();
            }

            if ($request->get('work_time_select') == 'empty'){
                $schedule->delete();
            }
            else{
                $schedule->year = $request->get('schedule_year');
                $schedule->month = $request->get('schedule_month');
                $schedule->day = $request->get('schedule_day');
                $schedule->abn_user_id = $request->get('consultant_id');
                $schedule->schedule = $request->get('work_time_select');
                $schedule->department_id = $request->get('department_id');

                $schedule->save();
            }


            Session::put('schedule_day',$request->get('schedule_day').'_'.$request->get('schedule_month').'_'.$request->get('schedule_year'));


            return redirect()->back()->with('ok','Записано!');
        }
        catch (\Exception $e){

            return redirect()->back()->with('status',$e->getMessage());
        }



    }

    public function consultantsScheduleSetAjax(Request $request){



        $rules =  [
            'schedule_year' => 'required',
            'schedule_month' => 'required',
            'schedule_day' => 'required',
            'consultant_id' => 'required',
            'work_time_select'=>'required',
            'department_id'=>'required',
        ];
        $this->validate($request, $rules);

        try {

            $schedule = ConsultantsSchedule::where('abn_user_id',$request->get('consultant_id'))
                ->where('year',$request->get('schedule_year'))
                ->where('month',$request->get('schedule_month'))
                ->where('day',$request->get('schedule_day'))
                ->first();

            if (!$schedule){
                $schedule = new ConsultantsSchedule();
            }

            if ($request->get('work_time_select') == 'empty'){
                $schedule->delete();
                $colorClass = "col";
            }

            else{
                $schedule->year = $request->get('schedule_year');
                $schedule->month = $request->get('schedule_month');
                $schedule->day = $request->get('schedule_day');
                $schedule->abn_user_id = $request->get('consultant_id');
                $schedule->schedule = $request->get('work_time_select');
                $schedule->department_id = $request->get('department_id');
                $schedule->save();

                if ($request->get('work_time_select')=='выходной'){
                    $colorClass = "col-day-off";
                }
                elseif  ($request->get('work_time_select')=='отпуск'){
                    $colorClass = "col-holiday";
                }
                elseif  ($request->get('work_time_select')=='больничный'){
                    $colorClass = "col-hospital";
                }
                else{
                    $colorClass = "col-schedule";
                }
            }


            Session::put('schedule_day',$request->get('schedule_day').'_'.$request->get('schedule_month').'_'.$request->get('schedule_year'));



            return response()->json([
                'success'=>'ok',
                'id'=>$request->get('consultant_id'),
                'day' => $request->get('schedule_day'),
                'month' => $request->get('schedule_month'),
                'year' => $request->get('schedule_year'),
                'color_class'=>$colorClass]);

        }
        catch (\Exception $e){
            return response()->json($e->getMessage(),400);

        }


    }


    public function getConsultant(Request $request){
        Log::channel('consultant_schedule')->info('Start query');

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'department_id' => 'required',
            'date_time'=>'required',
            // 'type'=>'required'
        ]);


        if ($validator->fails()) {
            Log::channel('consultant_schedule')->error(print_r($validator->errors(),true));
            return response()->json(['error' => $validator->errors()], 401);
        }


        $token = $request->get('token');
        $department_id = $request->get('department_id');
        $type = $request->get('type');


        if ($token == 'ENU2GruRZ9c2Ir918l5sU5ruk4TYLfD8Env0xV1l'){


            $date = Carbon::parse($request->get('date_time'))->addHours(3);

            //$now = Carbon::now();

            $year = $date->year;
            $month = $date->month;
            $day = $date->day;



            $schedule = ConsultantsSchedule::where('year',$year)
                ->where('month',$month)
                ->where('day',$day)
                ->where('schedule','!=','выходной')
                ->where('schedule','!=','отпуск')
                ->where('schedule','!=','больничный')
                ->where('department_id',$department_id)
                ->get();


            if ($schedule->count()){

                foreach ($schedule as $row){

                    //$flag = $row->flag();

                    $time = $row->schedule;

                    $timeArr = explode('-',$time);

                    $timeWorkStart = Carbon::parse($year.'-'.$month.'-'.$day.' '.$timeArr[0])->addHours(3);
                    $timeWorkEnd = Carbon::parse($year.'-'.$month.'-'.$day.' '. $timeArr[1])->addHours(3);


                    if($date >= $timeWorkStart && $date <= $timeWorkEnd){
                        $rowsArr[] = $row;
                    }

                }

                if (isset($rowsArr)){
                    Log::channel('consultant_schedule')->info('Found rows!');
                    $rowsCollect = collect($rowsArr);

                    $filtered = $rowsCollect->filter(function ($value) {
                        //dump(!$value->flag(), $value->flag()->flag );
                        return !$value->flag() || !$value->flag()->flag;
                    });

                    if((string)$type==='users'){
                        $response['users'] = $rowsCollect->pluck('abn_user_id');
                        return response()->json($response, 200);
                    }



                    if(!$filtered->count()){
                        //обнулить все
                        ConsultantsScheduleFlag::where('flag',1)->update(array('flag' => 0));

                        $filtered = $rowsCollect;
                    }


                    $user = $filtered->first();
                    $flag = $user->flag();

                    Log::channel('consultant_schedule')->info('User: ' . print_r($user,true));
                    Log::channel('consultant_schedule')->info('Flag: ' . $flag);

                    if(!$flag){
                        $flag = new ConsultantsScheduleFlag();
                        $flag->abn_user_id = $user->abn_user_id;
                        $flag->save();
                    }

                    $flag->flag = 1;
                    $flag->save();
                    Log::channel('consultant_schedule')->info('Set flag: ' . $flag);

                    $response['id'] = $user->abn_user_id;
                    $response['users'] = $rowsCollect->pluck('abn_user_id');

                    Log::channel('consultant_schedule')->info('Give id: ' . $user->abn_user_id);
                    Log::channel('consultant_schedule')->info('Give users: ' . $rowsCollect->pluck('abn_user_id'));



//                        foreach ($rowsCollect as $row){
//                            $flag = $row->flag();
//                            dd($flag);
//                        }


//                        $rowFirst = $rowsCollect->where('flag','!=',1)->first();
//
//                        if ($rowFirst){
//                            $rowFirst->flag = 1;
//                            $rowFirst->save();
//                            $response['id'] = $rowFirst->abn_user_id;
//                            $response['users'] = $rowsCollect->pluck('abn_user_id');
//                        }
//                        elseif($rowsCollect->count()){
//                            $ids = $rowsCollect->pluck('abn_user_id');
//                            ConsultantsSchedule::whereIn('abn_user_id',$ids)->update(array('flag' => 0));
//
//                            $rowFirst = $rowsCollect->first();
//                            $rowFirst->refresh()->flag = 1;
//                            $rowFirst->save();
//
//                            $response['id'] = $rowFirst->abn_user_id;
//                            $response['users'] = $rowsCollect->pluck('abn_user_id');
//                        }
//                        else{
//                            $response['message'] = "Not found!";
//                        }
                }
                else{
                    $response['message'] = "Not found!";
                    Log::channel('consultant_schedule')->info('Not found!');
                }


            }
            else{
                $response['message'] = "Not found!";
                Log::channel('consultant_schedule')->info('Not found!');
            }


            $status = 200;
        }
        else{

            $response['error'] = "wrong token!";
            $status = 403;
            Log::channel('consultant_schedule')->error('wrong token!');
        }



        Log::channel('consultant_schedule')->info('End query');
        return response()->json($response, $status);
    }

    public function updateConsultantFlag(Request $request)
    {
        Log::channel('consultant_schedule')->info('Start updateConsultantFlag query');

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'user_id' => 'required',
        ]);


        if ($validator->fails()) {
            Log::channel('consultant_schedule')->error(print_r($validator->errors(), true));
            return response()->json(['error' => $validator->errors()], 401);
        }

        $token = $request->get('token');
        $user_id = $request->get('user_id');

        if ($token == 'ENU2GruRZ9c2Ir918l5sU5ruk4TYLfD8Env0xV1l') {
            $user=ConsultantsScheduleFlag::firstOrNew(['abn_user_id' => $user_id]);
            $user->flag = 1;
            $user->save();

            $response['result']='success';
            $status=200;
        }

        else{

            $response['error'] = "wrong token!";
            $status = 403;
            Log::channel('consultant_schedule')->error('wrong token!');
        }
        return response()->json($response, $status);
    }
}

