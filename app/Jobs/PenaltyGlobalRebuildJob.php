<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class PenaltyGlobalRebuildJob__DEPR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     *
     */

    public $cacheKey = 'PenaltyGlobalRebuildJob_queued';

    public function __construct()
    {


    }

    /*    public function middleware()
        {         Log::debug('job middleware');

            return [new PenaltyGlobalRebuildRateLimited];
        }*/
    /**
     *
     *
     * @return void
     *
     *   6 не поддерживает ShouldBeUnique, также не поддерживает
     *  cache lock для драйвера file, database (только для memcached, dynamodb, or redis ) Call to undefined method Illuminate\Cache\FileStore::lock()
     *  поэтому схема:
     *  1. schedule->job (PenaltyGlobalRebuild) ->daily-> between ночью с 00:00 по 2:00
     *  2. внутри проверка на ключ в кэше
     *  3. устанавливаемем ключ на 24ч, т.о раньше чем через сутки не запустимся
     * (на воркере app/Console/runqueueworker sleep 2 сек - исключен одновременный запуск )
     *
     */
    public function handle()
    {

        if (Cache::has($this->cacheKey)) {
            $this->delete();
        } else {
            Cache::put($this->cacheKey, true, 24*3600);
        }

    }
}
