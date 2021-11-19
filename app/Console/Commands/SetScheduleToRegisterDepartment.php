<?php

namespace App\Console\Commands;

use App\AbnUser;
use App\ConsultantsSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SetScheduleToRegisterDepartment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register_dep_schedule:set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавляет график работ сотрудникам отдела оформления сделок на след. месяц';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('start command set schedule to register department');

        $consultants = AbnUser::where('is_active', '=', 1)
            ->where('department', 'Отдел оформления сделок')
            ->get();

        //берем дату + месяц от текущего
        $scheduleDate = Carbon::now()->addMonth();
        $month = $scheduleDate->month;
        $year = $scheduleDate->year;
        //последний день в месяце
        $lastDayOfMonth = (int)$scheduleDate->endOfMonth()->format('d');

        foreach ($consultants as $consultant) {

            for ($i = 1; $i <= $lastDayOfMonth; $i++) {

                //определяем день недели
                $dayOfWeek = Carbon::parse($i . '.' . $month . '.' . $year)->format('l');

                if ($dayOfWeek === 'Friday') {
                    $workTime = '9:00-16:45';
                }
                elseif ($dayOfWeek === 'Saturday' || $dayOfWeek === 'Sunday') {
                    $workTime = 'выходной';
                }
                else {
                    $workTime = '9:00-18:00';
                }

                //проверяем , есть в расписании в базе
                $schedule = ConsultantsSchedule::where('abn_user_id', $consultant->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('day', $i)
                    ->first();



                if (!$schedule) {
                    $schedule = new ConsultantsSchedule();
                }

                $schedule->year = $year;
                $schedule->month = $month;
                $schedule->day = $i;
                $schedule->abn_user_id = $consultant->id;
                $schedule->schedule = $workTime;
                $schedule->department_id = $consultant->department_id;
                $schedule->save();

                $this->line('Записали сотруднику '.$consultant->user_name .' на ' . $i.'.'.$month.'.'.$year .' время работы: ' .$workTime);

            }
        }

        $this->line('########################################');
        $this->line('Завершение работы команды');
    }
}
