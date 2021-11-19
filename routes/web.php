<?php




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
})->name('welcome')->middleware('guest');

Auth::routes();

//Закрываем регистарцию
Route::get('/register', function () {
    return abort(404);
});


//Route::get('/home', 'HomeController@index')->name('home');

Route::group(['namespace' => 'Reports', 'middleware' => ['auth']], function () {

//1-й отчет
    Route::get('/reports/contracts', 'ContractsController@index')->name('contracts')->middleware('onlyForReports');
    Route::post('/reports/contracts', 'ContractsController@makeReport')->name('contracts.makeReport')->middleware('onlyForReports');

//2-й отчет
    Route::get('/reports/sales', 'SalesController@index')->name('sales')->middleware('onlyForReports');
    Route::post('/reports/sales', 'SalesController@makeReport')->name('sales.makeReport')->middleware('onlyForReports');
//3-й отчет
    Route::get('/reports/agents', 'AgentsController@index')->name('agents')->middleware('onlyForReports');
    Route::post('/reports/agents', 'AgentsController@makeReport')->name('agents.makeReport')->middleware('onlyForReports');

//4-й отчет
    Route::get('/reports/workout', 'WorkoutController@index')->name('workout')->middleware('onlyForReports');
    Route::post('/reports/workout', 'WorkoutController@makeReport')->name('workout.makeReport')->middleware('onlyForReports');

//5-й отчет
    Route::get('/reports/subagents', 'SubAgentsController@index')->name('subagents')->middleware('onlyForSubagents');
    Route::post('/reports/subagents', 'SubAgentsController@makeReport')->name('subagents.makeReport')->middleware('onlyForSubagents');

//6-й отчет
    Route::get('/reports/real_for_sale', 'RealForSaleController@index')->name('realForSale')->middleware('onlyForReports');
    Route::post('/reports/real_for_sale', 'RealForSaleController@makeReport')->name('realForSale.makeReport')->middleware('onlyForReports');

//7-й отчет
    Route::get('/reports/forecast', 'ForecastController@index')->name('forecast')->middleware('onlyForReports');
    Route::post('/reports/forecast', 'ForecastController@makeReport')->name('forecast.makeReport')->middleware('onlyForReports');

//8-й отчет
    Route::get('/reports/debtors', 'DebtorsController@index')->name('debtors')->middleware('onlyForReports');
    Route::post('/reports/debtors', 'DebtorsController@makeReport')->name('debtors.makeReport')->middleware('onlyForReports');

//9-й отчет собственники

    Route::get('/reports/owners', 'OwnersController@index')->name('owners')->middleware('onlyForReports');
    Route::post('/reports/owners', 'OwnersController@makeReport')->name('owners.makeReport')->middleware('onlyForReports');
    //берем адреса для связанных селектов
    Route::get('/reports/owners/address-list','OwnersController@getAddressList')->name('address-list');

//10-й отчетность субагента
    Route::get('/reports/subagent_reporting', 'SubagentReportingController@index')->name('subagent_reporting')->middleware('onlyForReports');
    Route::post('/reports/subagent_reporting', 'SubagentReportingController@makeReport')->name('subagent_reporting.makeReport')->middleware('onlyForReports');


});


//профиль
Route::get('/profile', 'ProfileController@index')->middleware('auth')->name('profile');
//редактирование профиля
//Route::put('/profile/update','ProfileController@profileUpdate')->name('profile.update');
//Смена пароля в профиле пользователя
Route::put('/profile/change_password','ProfileController@changePassword')->name('profile.changePassword');


////////////admin//////////
Route::group(['namespace' => 'Admin', 'middleware' => ['auth']], function () {
//////////////////////*******РОЛИ*************//////////////////////////
    //список ролей
    Route::get('/roles', 'AdminController@rolesIndex')->name('roles.index')->middleware('onlyAdmin');
    //показать форму добавления роли
    Route::get('/roles/create', 'AdminController@roleCreate')->name('role.create')->middleware('onlyAdmin');
    //обработчик формы добавления роли
    Route::post('/roles/create', 'AdminController@roleStore')->name('role.store')->middleware('onlyAdmin');
    //показать форму редактирования роли
    Route::get('/roles/{id}/edit', 'AdminController@roleEdit')->name('role.edit')->middleware('onlyAdmin');
    //обработчик формы редактирования роли
    Route::put('/roles/{id}/update', 'AdminController@roleUpdate')->name('role.update')->middleware('onlyAdmin');
    //удаление роли
    Route::delete('/roles/{id}/destroy', 'AdminController@roleDestroy')->name('role.destroy')->middleware('onlyAdmin');

    //////////////////////*******ПРАВА*************//////////////////////////
    //список прав
    Route::get('/permissions', 'AdminController@permissionsIndex')->name('permissions.index')->middleware('onlyAdmin');
    //показать форму добавления права
    Route::get('/permissions/create', 'AdminController@permissionCreate')->name('permission.create')->middleware('onlyAdmin');
    //обработчик формы добавления права
    Route::post('/permissions/create', 'AdminController@permissionStore')->name('permission.store')->middleware('onlyAdmin');
    //показать форму редактирования права
    Route::get('/permissions/{id}/edit', 'AdminController@permissionEdit')->name('permission.edit')->middleware('onlyAdmin');
    //обработчик формы редактирования права
    Route::put('/permissions/{id}/update', 'AdminController@permissionUpdate')->name('permission.update')->middleware('onlyAdmin');
    //удаление права
    Route::delete('/permissions/{id}/destroy', 'AdminController@permissionDestroy')->name('permission.destroy')->middleware('onlyAdmin');

    //////////////////////*******ПОЛЬЗОВАТЕЛИ*************//////////////////////////
    //список пользователей
    Route::get('/users', 'AdminController@usersIndex')->name('users.index')->middleware('onlyAdmin');
    //показать форму добавления пользователя
    Route::get('/users/create', 'AdminController@userCreate')->name('user.create')->middleware('onlyAdmin');
    //обработчик формы добавления пользователя
    Route::post('/users/create', 'AdminController@userStore')->name('user.store')->middleware('onlyAdmin');
    //показать форму редактирования пользователя
    Route::get('/users/{id}/edit', 'AdminController@userEdit')->name('user.edit')->middleware('onlyAdmin');
    //обработчик формы редактирования пользователя
    Route::put('/users/{id}/update', 'AdminController@userUpdate')->name('user.update')->middleware('onlyAdmin');
    //удаление пользователя
    Route::delete('/users/{id}/destroy', 'AdminController@userDestroy')->name('user.destroy')->middleware('onlyAdmin');



    //////////////////////*******СУБАГЕНТЫ*************//////////////////////////
    //список пользователей
    Route::get('/subagents', 'AdminController@subagentIndex')->name('subagent.index')->middleware('onlyForSubagents');
    //показать форму добавления пользователя
    Route::get('/subagents/create', 'AdminController@subagentCreate')->name('subagent.create')->middleware('onlyForSubagents');
    //обработчик формы добавления пользователя
    Route::post('/subagents/create', 'AdminController@subagentStore')->name('subagent.store')->middleware('onlyForSubagents');
    //показать форму редактирования пользователя
    Route::get('/subagents/{id}/edit', 'AdminController@subagentEdit')->name('subagent.edit')->middleware('onlyForSubagents');
    //обработчик формы редактирования пользователя
    Route::put('/subagents/{id}/update', 'AdminController@subagentUpdate')->name('subagent.update')->middleware('onlyForSubagents');
    //удаление пользователя
    Route::delete('/subagents/{id}/destroy', 'AdminController@subagentDestroy')->name('subagent.destroy')->middleware('onlyForSubagents');







});


//////////////////////*******правообладатели*************//////////////////////////
//список правообладателей
Route::get('/principals', 'Admin\AdminController@principalsIndex')->name('principals.index')->middleware('ForSortingComplexes');
//показать форму добавления правообладателя
Route::get('/principals/create', 'Admin\AdminController@principalsCreate')->name('principals.create')->middleware('ForSortingComplexes');
//обработчик формы добавления правообладателя
Route::post('/principals/create', 'Admin\AdminController@principalsStore')->name('principals.store')->middleware('ForSortingComplexes');
//показать форму редактирования правообладателя
Route::get('/principals/{id}/edit', 'Admin\AdminController@principalsEdit')->name('principals.edit')->middleware('ForSortingComplexes');
//обработчик формы редактирования правообладателя
Route::put('/principals/{id}/update', 'Admin\AdminController@principalsUpdate')->name('principals.update')->middleware('ForSortingComplexes');
//удаление правообладателя
Route::delete('/principals/{id}/destroy', 'Admin\AdminController@principalsDestroy')->name('principals.destroy')->middleware('ForSortingComplexes');


//////////////////////*******Ежегодные планы*************//////////////////////////
//Список планов
Route::get('/planseditor', 'Admin\AdminController@plansIndex')->name('plans.index')->middleware('ForSortingComplexes');
//Подтягивание существующих планов
Route::get('/planseditor/plansFetch', 'Admin\AdminController@plansFetch')->middleware('ForSortingComplexes');
//Добавление нового плана
Route::post('/planseditor/plansAdd', 'Admin\AdminController@plansAdd')->middleware('ForSortingComplexes');
//Обновление плана
Route::post('/planseditor/plansUpdate', 'Admin\AdminController@plansUpdate')->middleware('ForSortingComplexes');
//Удаление плана
Route::post('/planseditor/plansDelete', 'Admin\AdminController@plansDelete')->middleware('ForSortingComplexes');
//Подтягивание специалистов для автокомплита
Route::post('/planseditor/plansUploadUsers', 'Admin\AdminController@plansUploadUsers')->middleware('ForSortingComplexes');



//////////////////////*******комплексы*************//////////////////////////
//список комплексов
Route::get('/complexes', 'Admin\AdminController@complexesIndex')->name('complexes.index')->middleware('ForSortingComplexes');
//показать форму добавления комплекса
Route::get('/complexes/create', 'Admin\AdminController@complexesCreate')->name('complexes.create')->middleware('ForSortingComplexes');
//обработчик формы добавления комплекса
Route::post('/complexes/create', 'Admin\AdminController@complexesStore')->name('complexes.store')->middleware('ForSortingComplexes');
//показать форму редактирования комплекса
Route::get('/complexes/{id}/edit', 'Admin\AdminController@complexesEdit')->name('complexes.edit')->middleware('ForSortingComplexes');
//обработчик формы редактирования комплекса
Route::put('/complexes/{id}/update', 'Admin\AdminController@complexesUpdate')->name('complexes.update')->middleware('ForSortingComplexes');
//удаление комплекса
Route::delete('/complexes/{id}/destroy', 'Admin\AdminController@complexesDestroy')->name('complexes.destroy')->middleware('ForSortingComplexes');






//////////////////////*******коэффициенты*************//////////////////////////
//список коэффициентов
Route::get('/coefficients', 'Admin\AdminController@coefficientsIndex')->name('coefficients.index')->middleware('onlyForSubagents');
//показать форму добавления коэффициента
Route::get('/coefficients/create', 'Admin\AdminController@coefficientsCreate')->name('coefficients.create')->middleware('onlyForSubagents');
//обработчик формы добавления коэффициента
Route::post('/coefficients/create', 'Admin\AdminController@coefficientsStore')->name('coefficients.store')->middleware('onlyForSubagents');
//показать форму редактирования коэффициента
Route::get('/coefficients/{id}/edit', 'Admin\AdminController@coefficientsEdit')->name('coefficients.edit')->middleware('onlyForSubagents');
//обработчик формы редактирования коэффициента
Route::put('/coefficients/{id}/update', 'Admin\AdminController@coefficientsUpdate')->name('coefficients.update')->middleware('onlyForSubagents');
//удаление коэффициента
Route::delete('/coefficients/{id}/destroy', 'Admin\AdminController@coefficientsDestroy')->name('coefficients.destroy')->middleware('onlyForSubagents');

//Редактирование графика платежей для консультантов
Route::get('/edit_payments', 'Admin\AdminController@editPaymentsForConsultant')->name('payments.allow_edit.index')->middleware('ForSortingComplexes');
Route::post('/edit_payments', 'Admin\AdminController@editPaymentsForConsultantToggle')->name('payments.allow_edit.edit')->middleware('ForSortingComplexes');


///////////////////////////////СТОРОННИЕ ПЛАТЕЖИ/////////////////////////////////////////////

//сторонние платежи - главная
Route::get('/income_pays_payments', 'Admin\IncomePaysController@index')->name('income_pays_payments.index')->middleware('ForSortingComplexes');
//показать форму добавления платежа
Route::get('/income_pays_payments/create', 'Admin\IncomePaysController@create')->name('income_pays_payments.create')->middleware('ForSortingComplexes');
//обработчик формы добавления платежа
Route::post('/income_pays_payments/create', 'Admin\IncomePaysController@store')->name('income_pays_payments.store')->middleware('ForSortingComplexes');
//показать форму редактирования платежа
Route::get('/income_pays_payments/{id}/edit', 'Admin\IncomePaysController@edit')->name('income_pays_payments.edit')->middleware('ForSortingComplexes');
//обработчик формы редактирования платежа
Route::put('/income_pays_payments/{id}/update', 'Admin\IncomePaysController@update')->name('income_pays_payments.update')->middleware('ForSortingComplexes');
//удаление платежа
Route::delete('/income_pays_payments/{id}/destroy', 'Admin\IncomePaysController@destroy')->name('income_pays_payments.destroy')->middleware('ForSortingComplexes');


Route::get('/payments', 'PaymentsSchedule@index')->name('payments');
Route::post('/api/searchContract', 'PaymentsSchedule@api_searchContract');
Route::post('/api/createContract', 'PaymentsSchedule@api_createContract');
Route::post('/api/updateContract', 'PaymentsSchedule@api_updateContract');
Route::get('/api/searchContract', 'PaymentsSchedule@api_searchContract');
Route::post('/api/createPaymentSchedule', 'PaymentsSchedule@api_createPaymentSchedule');
Route::post('/api/getSettings', 'PaymentsSchedule@api_getSettings');
Route::post('/api/updateSettings', 'PaymentsSchedule@api_updateSettings');
Route::post('/api/updatePenalty', 'PaymentsSchedule@api_updatePenalty');
Route::post('/api/addPayment', 'PaymentsSchedule@api_addPayment');
Route::post('/api/editSchRow', 'PaymentsSchedule@api_editSchRow');
Route::post('/api/deleteSchRows', 'PaymentsSchedule@api_deleteSchRows');
Route::post('/api/deleteRefinRateRows', 'PaymentsSchedule@api_deleteRefinRateRows');
Route::post('/api/editRefinRateRow', 'PaymentsSchedule@api_editRefinRateRow');


//Route::get('/api/penalty_cron/{lead_id?}/{date?}', 'PenaltyJobsController@setJob'); // runJob
//Route::get('/api/penalty_process_contract/{lead_id}/{date}', 'PenaltyJobsController@ppc');
//Route::get('/api/process_payments', 'PaymentsJobsController@setJob');
//Route::get('/api/penalty_process_payment', 'PaymentsSchedule@api_PenaltyProcessPayment');
//Route::get('/api/penaltyrecalc', 'PaymentsSchedule@api_PenaltyRecalc');
Route::post('/api/getRefRateHistory', 'PaymentsSchedule@api_getRefRateHistory');


// parser
Route::post('/api/parser', 'ImportExcelController@parseFile')->name('parser');

//переуступка

Route::post('/api/getAssignment', 'PaymentsSchedule@api_getAssignment');
Route::post('/api/addAssignment', 'PaymentsSchedule@api_addAssignment');
Route::post('/api/searchCessionary', 'PaymentsSchedule@api_searchCessionary');

//отчет по договору
Route::post('/api/makeReport/{lead_id}', 'PaymentsSchedule@api_makeReport')->name('makeReport');
Route::get('/api/report_test', 'ScheduleController@sheet') ;




//Route::get('/api/test', 'Test4536@run') ;
//Route::get('/pn_cron', 'PenaltyNotificationController@run') ;

Route::get('/debug-sentry453576579687', function () {
    throw new Exception('My first Sentry error!');
});


//График работы консультантов
Route::get('/consultants_schedule/{month?}/{year?}', 'Admin\ConsultantController@consultantsScheduleIndex')->name('consultants_schedule.index')->middleware(['auth','onlyConsultantScheduleAdmin']);
Route::post('/consultants_schedule', 'Admin\ConsultantController@consultantsScheduleSet')->name('consultants_schedule.set')->middleware(['auth','onlyConsultantScheduleAdmin']);
Route::post('/consultants_schedule_ajax', 'Admin\ConsultantController@consultantsScheduleSetAjax')->name('consultants_schedule.set.ajax')->middleware(['auth','onlyConsultantScheduleAdmin']);

//API графика работы консультантов
Route::get('/api/consultants_schedule', 'Admin\ConsultantController@getConsultant')->name('consultants_schedule.get.consultant');
Route::get('/api/consultants_schedule_update_flag', 'Admin\ConsultantController@updateConsultantFlag')->name('consultants_schedule.update.consultant');


Route::get('api/employee_check_1PczTtit0aLrKJctHtmsj4UHXDYVw2Ad6tv_7n3pJPF4', function (Request $request) {

    $exitCode = Artisan::call('GoogleSheetCheckConnector:run', [
        'search' => $request->get('search_text') // $_GET['search_text']
    ]);

    // можно просто распечатать exit code ?
    switch($exitCode) {

        case 128: $msg = "Пустой запрос"; break;
        case 10: $msg = "Совпадение найдено!"; break;
        case 11: $msg = "Совпадение не найдено!"; break;
        default:  $msg = "Неизвестная ошибка"; break;
    }

        exit($msg);



}); //->middleware(['auth' ]);

Route::get('/kvitok', 'Admin\KvitokController@index')->name('kvitok.index')->middleware('checkIp');

//обновление цен
Route::get('/update_price', 'Admin\UpdatePriceController@index')->name('update_price.index')->middleware('onlyAdmin');

