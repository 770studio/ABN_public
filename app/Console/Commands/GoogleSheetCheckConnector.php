<?php

namespace App\Console\Commands;

use \Google_Client;
use \Google_Service_Sheets;
use Illuminate\Console\Command;

// не смотря на то, что это cli , нам не нужно много времени на запрос
set_time_limit(30);



class GoogleSheetCheckConnector extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GoogleSheetCheckConnector:run {search}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('GoogleSheetCheckConnector');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);

        $client->setAuthConfig(base_path() . '/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = base_path() . '/token.json';


        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {

                if (php_sapi_name() != 'cli') {
                    throw new \Exception('Необходимо обновить токен через консольный запуск');
                }


                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }


    /**
     * Execute the console command.
     *
     * @return exit code
     * 128 - пустой запрос
     *  10  Совпадение найдено
     *  11  Совпадение не найдено
     *
     */
    public function handle()
    {


       // if (php_sapi_name() != 'cli' &&  php_sapi_name() != "cli-server" ) {
      //      throw new \Exception('This application must be run on the command line.');
      //  }

        /**
         * Returns an authorized API client.
         * @return Google_Client the authorized client object
         */



// Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit

  // https://drive.google.com/file/d/1NCWT4zsNrMhdyWlfx9Vkns2kstU5smka/view?ths=true

        //$spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
        // $spreadsheetId = '1U4tM5DvPrdEYkTEOB1VO5oXgIDQMPQXT7sEH4WwDj6s'; //'1NCWT4zsNrMhdyWlfx9Vkns2kstU5smka';
        $spreadsheetId = '1PczTtit0aLrKJctHtmsj4UHXDYVw2Ad6tv_7n3pJPF4';

       // $response = $service->spreadsheets->get($spreadsheetId);
        // dd($response);
        //$range = 'Class Data!A2:E';
        // $range = 'Class Data';
        $range = 'B1:B1000000';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range, ['majorDimension'=> 'COLUMNS'] );
        $values = $response->getValues();

        if (empty($values)) {
            print "No data found.\n";
        } else {
            //dd(implode("\n", $values[0] ));

            $search = trim( $this->argument('search') );

            if(!$search) {
                $this->error("Пустой запрос!");
                return 128;
            }


            if( stripos( implode("\n", $values[0] ) , $this->argument('search') ) !== false) {
                $this->info("Совпадение найдено!");
                return 10 ; // "Совпадение найдено!";
            }
            $this->info("Совпадение не найдено!");
            return 11; // "Совпадение не найдено!";
        }
    }
}
