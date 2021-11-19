<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class CriticalException extends Exception
{

    public function __construct($attr)
    {
        parent::__construct($attr);
    }


/*    public function report(Exception $exception)
    {

        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }


        parent::report($exception);
    }
*/
}
