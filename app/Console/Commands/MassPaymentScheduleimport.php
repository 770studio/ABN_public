<?php

namespace App\Console\Commands;

use App\Events\ScheduleModifiedEvent;
use App\Instalment;
use App\Lead;
use App\PaymentsSchedule;
use App\Schedule;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jenssegers\Date\Date;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use ZanySoft\Zip\Zip;


class MassPaymentScheduleimport extends Command
{

        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masspsimport:run'; // импорт из 1 с
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
private $bailout = 4;
    /**
     * Create a new command instance.
     *
     * @return void
     */


    private $penalty_type = 0;
    private $penalty_value = 0.0;


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

        set_time_limit(10000);
        ini_set('max_execution_time', '10000');

        Log::channel('ps1simport')->info("max_execution_time:" . ini_get('max_execution_time'));


        $path = storage_path('app/1s_import/archive_import_paymentschedules_from_1S.zip');
        $unzipped_path = storage_path('app/1s_import/unzipped/');
        Storage::delete(Storage::files('1s_import/unzipped'));

        if (!file_exists($path)) \Exception('Архива нет');
        if (!Zip::check($path)) \Exception('Архив не распаковывается либо битый архив');
        $zip = Zip::open($path);
        $zip->extract($unzipped_path);

        $files = Storage::files('1s_import/unzipped');
        foreach ($files as $file) {

            $this->parseFile('app/' . $file);


        }

    }

    public function parseFile($filePath)
    {
        Log::channel('ps1simport')->info("старт обработки:" . $filePath);


        try {

            //запускаем ридер
            $reader = new Xlsx();
            $spreadsheet = $reader->load(storage_path($filePath));

            //получаем данные
            $dataAll = $spreadsheet->getActiveSheet()->toArray();

            if (!$dataAll) throw new \Exception('Не парсится excel');
            $contract_number = null;
            if ($dataAll[0][9] == "номер договора:") {
                $contract_number = trim($dataAll[0][10]);

            }

            if (!$contract_number) {
                throw new \Exception('Нет номера договора');
            }
            Log::channel('ps1simport')->info("номер договора:" . $contract_number);


            if (false === $this->parsePenaltyType(@$dataAll[16][2])) {
                throw new \Exception('Не задан тип расчета пени');
            }

            Log::channel('ps1simport')->info("тип расчета пени:", $this->penalty);


            $lead = Lead::where('contract_number', $contract_number)->join('leads', function ($join) {
                $join->on('leads.lead_id', '=', 'lead_params.lead_id');
            })->orderBy('leads.date_create', 'desc')
                ->get();


            if (!$lead || !$lead->count()) throw new \Exception('Нет сделки');
            //if($lead->count() != 1 ) throw new \Exception('Дупли по сделке');
            // берем последний

            $lead_id = $lead->first()->lead_id;


            Log::channel('ps1simport')->info("lead_id:" . $lead_id);
            $contract = Instalment::firstOrCreate(['lead_id' => $lead_id],
                ['lead_id' => $lead_id, 'schedule_created' => 0]

            );
            // $contract =  Instalment::find( $lead_id );
            // if(!$contract) throw new Exception('договора нет, договор рассрочки должен существовать'  );


            //убираем лишнее до 21 строки
            $data = array_slice($dataAll, 20);


            $Schedule = [];
            $sum_inst = 0;
            $total = 0;

            foreach ($data as $n => $row) {


                if ($row[1] == "ИТОГО:") {

                    //dd($row[1], $total, self::_toDouble( $row[5] ));
                    if (self::_toDouble($total) != self::_toDouble($row[5])) throw new Exception('суммы не бьются');
                    break;
                } else {


                    if ($n == 0) {
                        $sum_inst = self::_toDouble($row[2]);
                    }

                    $total += self::_toDouble($row[5]);

                    array_push($Schedule, [
                        'n' => $n + 1,
                        'lead_id' => $lead_id,
                        'payment_date' => self::_toMysqlDate($row[1]),
                        'sum_prs' => self::_toDouble($row[4]),
                        'sum_payment' => self::_toDouble($row[3]),
                        'sum_total' => self::_toDouble($row[5]),
                        'total_payings' => self::_toDouble($row[2]),

                    ]);


                }
            }
            $bailout = $this->bailout;

            DB::transaction(function () use ($contract, $total, $sum_inst, $Schedule, $bailout) {
                // обновляем сумуу рассрочки и общую
                $contract->update([
                    'instalment_sum' => $sum_inst,
                    'total_sum' => $total,
                    'bailout' => $bailout,
                    'penalty_type' => $this->penalty_type,
                    'penalty_value' => $this->penalty_value
                ]);
                $contract->increment('schedule_updated');  // 'schedule_updated' => (int)$contract->schedule_updated + 1
                // обновить
                $contract = Instalment::find($contract->lead_id);

                // удаляем старый график

                if (@$contract->Schedule) {
                    // сохранить для истории
                    \App\ScheduleHistory::create(['event' => 'import1s', 'dump' => serialize($contract->Schedule->toArray()), 'lead_id' => $contract->lead_id, 'bailout' => $bailout, 'employee_id' => Auth::user() ? Auth::user()->id : 0]);
                    $contract->Schedule()->delete();
                }

                // Schedule::where('lead_id', $contract->lead_id)->delete();
                //$contract->Schedule()->insert($Schedule);
                Schedule::insert($Schedule);
                ScheduleModifiedEvent::dispatch($contract, __CLASS__ . ':' . __LINE__);


            });


            Log::channel('ps1simport')->info($contract_number . ' график успешно импортирован!');


        } catch (\Exception $e) {


            Log::channel('ps1simport')->error($e->getLine() . ' | ' . $e->getMessage());
            // Log::channel('ps1simport')->error( $e->getTrace() );
        }

        if (isset($spreadsheet)) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

        }


    }

    private function parsePenaltyType($str)
    {
        /**
         * 1 => 'Величина пени в сумме',
         * 2 => '1/360 ставки рефинансирования',
         * 3 => '1/300 ставки рефинансирования',
         * 4 => 'Величина пени в процентах',
         *
         *   #####################################
         * Величина пени в сумме:100.00
         * 1/360
         * 1/300
         * Величина пени в процентах:10.5
         */


        if ($str == '0' || $str == '0.0' || $str == '0.0%') {
            // обнуление
            $this->setPenalty(0);
            return;
        }
        if ($str == '1/360') {
            $this->setPenalty(2);
            return;
        }
        if ($str == '1/300') {
            $this->setPenalty(3);
            return;
        }

        $type = 0;
        $other = explode(':', $str);
        if (@$other[0] == 'Величина пени в сумме') $type = 1;
        elseif (@$other[0] == 'Величина пени в процентах') $type = 4;

        if ($value = $this->_toDouble(@$other[1])) {
            $this->setPenalty($type, $value);
            return;
        }


        return false;


    }

    private function setPenalty($type, $value = 0.0)
    {

        $this->penalty_type = $type;
        $this->penalty_value = $value;
    }

    public static function _toDouble($str)
    {
        return round((float)preg_replace(['/,/', '/[^\d\.]/'], ['.', ''], $str), 2);
        // return round( (float) str_replace([','], [''], $str)   , 2  );
        // preg_replace (   [ '/[\,^\d\.]/'   ], [''   ], $str)


    }

    public static function _toMysqlDate($str)
    {
        // $ts = strtotime($str );
        //  return  $ts ? date("Y-m-d", $ts  ) : false;

        $formatter = new \IntlDateFormatter("ru_RU", \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
        $ts = $formatter->parse($str);
        return $ts ? date("Y-m-d", $ts) : false;

    }


}

