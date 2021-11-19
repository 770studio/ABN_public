<?php

namespace App\Http\Controllers\Admin;

use App\Coefficient;
use App\Complex;
use App\Principal;
use App\SubagentParams;
use Exception;
use Illuminate\Database\QueryException as QueryException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Role;
use App\Permission;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class AdminController extends Controller
{
    //////////////////////*******РОЛИ*************//////////////////////////
    //список ролей
    public function rolesIndex()
    {


        $roles = Role::all();
        return view('admin.roles.index', ['roles' => $roles]);
    }


    //показать форму добавления роли
    public function roleCreate()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', ['permissions' => $permissions]);
    }

    //обработчик формы добавления роли
    public function roleStore(Request $request)
    {

        $this->validate($request, [
            'title' => 'required|unique:permissions',
            'display_name' => 'required|unique:permissions'
        ]);

        $role = new Role();
        $role->title = $request->get('title');
        $role->display_name = $request->get('display_name');
        $role->description = $request->get('description');
        $role->save();
        $role->setPermissions($request->get('permissions'));
        return redirect()->route('roles.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования роли
    public function roleEdit($id)
    {
        $role = Role::findOrFail($id);



        $permissions = Permission::all();
        $selectedPerms = $role->permissions->pluck('id')->all();
        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'selectedPerms' => $selectedPerms
        ]);
    }

    //редактирование роли
    public function roleUpdate(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $this->validate($request, [
            'title' => ['required', Rule::unique('permissions')->ignore($role->id),],
            'display_name' => ['required', Rule::unique('permissions')->ignore($role->id),]
        ]);


        $role->update($request->all());

        $role->setPermissions($request->get('permissions'));
        return redirect()->route('roles.index')->with('ok', 'Запись изменена');
    }

    //удаление роли
    public function roleDestroy($id)
    {
        $role = Role::findOrFail($id);
        //Удаляем связь с разрешениями
        $role->permissions()->sync([]);
        //Удаляем роль
        $role->forceDelete();

        return redirect()->route('roles.index')->with('ok', 'Запись удалена');
    }


    //////////////////////*******ПРАВА*************//////////////////////////

    //список прав
    public function permissionsIndex()
    {
        $permissions = Permission::all();
        return view('admin.permissions.index', ['permissions' => $permissions]);
    }

    //показать форму добавления права
    public function permissionCreate()
    {

        return view('admin.permissions.create');
    }

    //обработчик формы добавления права
    public function permissionStore(Request $request)
    {

        $this->validate($request, [
            'title' => 'required|unique:permissions',
            'display_name' => 'required|unique:permissions'
        ]);

        $permission = new Permission();
        $permission->title = $request->get('title');
        $permission->display_name = $request->get('display_name');
        $permission->description = $request->get('description');
        $permission->save();

        return redirect()->route('permissions.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования права
    public function permissionEdit($id)
    {
        $permission = Permission::findOrFail($id);

        return view('admin.permissions.edit', ['permission' => $permission]);
    }

    //редактирование права
    public function permissionUpdate(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $this->validate($request, [
            'title' => ['required', Rule::unique('permissions')->ignore($permission->id),],
            'display_name' => ['required', Rule::unique('permissions')->ignore($permission->id),]
        ]);


        $permission->update($request->all());

        return redirect()->route('permissions.index')->with('ok', 'Запись изменена');
    }

    //удаление права
    public function permissionDestroy($id)
    {
        $permission = Permission::findOrFail($id);


        $permission->forceDelete();

        return redirect()->route('permissions.index')->with('ok', 'Запись удалена');
    }

    //////////////////////*******ПОЛЬЗОВАТЕЛИ*************//////////////////////////

    //список пользователей
    public function usersIndex()
    {

        //dd(Auth::user()->roles->first()->id);


        $users = User::all();
        return view('admin.users.index', ['users' => $users]);
    }

    //показать форму добавления пользователя
    public function userCreate()
    {
        $roles = Role::pluck('display_name', 'id')->all();

        return view('admin.users.create', ['roles' => $roles]);
    }

    //обработчик формы добавления пользователя
    public function userStore(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users'),
            ],
            'role_id' => 'required',
            'password' => [
                'required',
                'min:8',
                'confirmed'
            ]
        ]);

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        $user->setRole($request->get('role_id'));

        if ($request->get('group_id') != null) {
            $user->group_id = $request->get('group_id');
            $user->save();
        }

        return redirect()->route('users.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования пользователя
    public function userEdit($id)
    {
        $user = User::findOrFail($id);


        $roles = Role::pluck('display_name', 'id')->all();
        $selectedRole = $user->roles->pluck('id')->all();
        if ($selectedRole != null) {
            $user_role = $selectedRole[0];
        } else {
            $user_role = null;
        }


        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'selectedRole' => $user_role,

        ]);
    }

    //редактирование пользователя
    public function userUpdate(Request $request, $id)
    {

        $user = User::find($id);

        $this->validate($request, [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'role_id' => 'required',
            'password' => [
                'nullable',
                'min:8',
                'confirmed'
            ]
        ]);

        $user->update($request->all());
        $user->setRole($request->get('role_id'));


        if ($request->get('password') != null) {
            $user->password = Hash::make($request->get('password'));
            $user->save();
        }

        return redirect()->route('users.index')->with('ok', 'Запись изменена');
    }

    //удаление пользователя
    public function userDestroy($id)
    {
        $user = User::findOrFail($id);
        //удаляем связь с ролью
        $user->roles()->sync([]);

        //удаляем пользователя
        $user->forceDelete();

        return redirect()->route('users.index')->with('ok', 'Запись удалена');
    }



    //////////////////////*******коэффициенты*************//////////////////////////
    //список коэффициентов
    public function coefficientsIndex()
    {
        $coefficients = Coefficient::all();
        return view('admin.coefficients.index', ['coefficients' => $coefficients]);
    }

    //показать форму добавления коэффициента
    public function coefficientsCreate()
    {

        return view('admin.coefficients.create');
    }

    //обработчик формы добавления коэффициента
    public function coefficientsStore(Request $request)
    {

        $this->validate($request, [
            'coefficient' => 'required',
            'contracts_count' => 'required'
        ]);

        $coefficient = new Coefficient();
        $coefficient->coefficient = $request->get('coefficient');
        $coefficient->contracts_count = $request->get('contracts_count');

        $coefficient->save();

        return redirect()->route('coefficients.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования коэффициента
    public function coefficientsEdit($id)
    {
        $coefficient = Coefficient::findOrFail($id);

        return view('admin.coefficients.edit', ['coefficient' => $coefficient]);
    }

    //редактирование коэффициента
    public function coefficientsUpdate(Request $request, $id)
    {
        $coefficient = Coefficient::findOrFail($id);

        $this->validate($request, [
            'coefficient' => ['required'],
            'contracts_count' => ['required']
        ]);


        $coefficient->update($request->all());
        return redirect()->route('coefficients.index')->with('ok', 'Запись изменена');
    }

    //удаление коэффициента
    public function coefficientsDestroy($id)
    {
        $coefficient = Coefficient::findOrFail($id);

        //Удаляем коэффициент
        $coefficient->forceDelete();

        return redirect()->route('coefficients.index')->with('ok', 'Запись удалена');
    }


    //////////////////////*******правообладатели*************//////////////////////////
    //список правообладателей
    public function principalsIndex()
    {
        $principals = Principal::all();
        return view('admin.principals.index', ['principals' => $principals]);
    }

    //показать форму добавления правообладателя
    public function principalsCreate()
    {

        return view('admin.principals.create');
    }

    //обработчик формы добавления правообладателя
    public function principalsStore(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'agentcontract_number' => 'required',
            'agentcontract_date' => 'required',
            'head_name' => 'required',
            'head_name_2' => 'required',
            'adress' => 'required',
            'requisites' => 'required'
        ]);

        $principal = new Principal();
        $principal->name = $request->get('name');
        $principal->agentcontract_number = $request->get('agentcontract_number');
        $principal->agentcontract_date = $request->get('agentcontract_date');
        $principal->head_name = $request->get('head_name');
        $principal->head_name_2 = $request->get('head_name_2');
        $principal->adress = $request->get('adress');
        $principal->requisites = $request->get('requisites');

        $principal->save();

        return redirect()->route('principals.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования правообладателя
    public function principalsEdit($id)
    {
        $principal = Principal::findOrFail($id);

        return view('admin.principals.edit', ['principal' => $principal]);
    }

    //редактирование правообладателя
    public function principalsUpdate(Request $request, $id)
    {
        $principal = Principal::findOrFail($id);

        $this->validate($request, [
            'name' => 'required',
            'agentcontract_number' => 'required',
            'agentcontract_date' => 'required',
            'head_name' => 'required',
            'head_name_2' => 'required',
            'adress' => 'required',
            'requisites' => 'required'
        ]);


        $principal->update($request->all());
        return redirect()->route('principals.index')->with('ok', 'Запись изменена');
    }

    //удаление правообладателя
    public function principalsDestroy($id)
    {
        $principal = Principal::findOrFail($id);

        //Удаляем коэффициент
        $principal->forceDelete();

        return redirect()->route('principals.index')->with('ok', 'Запись удалена');
    }

    //////////////////////*******Ежегодные планы*************//////////////////////////

    //Список планов
    public function plansIndex()
    {
        return view('admin.plans.index');
    }

    //Подтягивание существующих планов
    function plansFetch(Request $request)
    {


        if ($request->ajax()) {
            try {
                $data = DB::table('plans_employees')
                    ->leftJoin('abned_users', 'plans_employees.employee_id', '=', 'abned_users.id')
                    ->select('plans_employees.employee_id', 'abned_users.user_name', 'plans_employees.plans', 'plans_employees.year')
                    ->orderBy('plans_employees.year', 'desc')
                    ->orderBy('abned_users.user_name')
                    ->get();

                echo json_encode($data);
            } catch (QueryException|Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    //Добавление нового плана
    public function plansAdd(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = array(
                    'employee_id' => $request->employee_id,
                    'plans' => str_replace(' ', '', $request->plans),
                    'year' => $request->year
                );

                DB::table('plans_employees')->insert($data);

                return response()->json(['message' => 'План успешно добавлен.'], 200);
            } catch (QueryException|Exception $exception) {
                Log::error($exception->getMessage());
                return response()->json(['message' => 'План не добавлен. Возможно такой план уже существует.'], 400);
            }
        }
    }

    //Обновление плана
    public function plansUpdate(Request $request)
    {
        if ($request->ajax()) {
            try {
                DB::table('plans_employees')
                    ->where('employee_id', $request->id)
                    ->where('year', $request->year)
                    ->update(['plans' => str_replace(' ', '', $request->plans)]);

                return response()->json(['message' => 'План успешно обновлен.'], 200);
            } catch (QueryException|Exception $exception) {
                Log::error($exception->getMessage());
                return response()->json(['message' => 'Не удалось обновить план.'], 400);
            }
        }
    }

    //Удаление плана
    public function plansDelete(Request $request)
    {
        if ($request->ajax()) {
            try {
                DB::table('plans_employees')
                    ->where('employee_id', $request->id)
                    ->where('year', $request->year)
                    ->delete();

                return response()->json(['message' => 'План успешно удален.'], 200);
            } catch (QueryException|Exception $exception) {
                Log::error($exception->getMessage());
                return response()->json(['message' => 'Удаляемый план не найден.'], 400);
            }
        }
    }

    //Подтягивание специалистов для автокомплита
    public function plansUploadUsers(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = DB::table('abned_users')
                    ->select('abned_users.id', 'abned_users.user_name')
                    ->orderBy('abned_users.user_name')
                    ->get();

                echo json_encode($data);
            } catch (QueryException|Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    //////////////////////********************//////////////////////////


    //////////////////////*******комплексы*************//////////////////////////
    //список комплексов
    public function complexesIndex()
    {
        $complexes = Complex::all();
        return view('admin.complexes.index', ['complexes' => $complexes]);
    }

    //показать форму добавления комплекса
    public function complexesCreate()
    {

        return view('admin.complexes.create');
    }

    //обработчик формы добавления комплекса
    public function complexesStore(Request $request)
    {

        $this->validate($request, [
            'complex' => 'required',
            'sort' => 'required|numeric'
        ]);

        $complex = new Complex();
        $complex->complex = $request->get('complex');
        $complex->sort = $request->get('sort');

        $complex->save();

        return redirect()->route('complexes.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования комплекса
    public function complexesEdit($id)
    {
        $complex = Complex::findOrFail($id);

        return view('admin.complexes.edit', ['complex' => $complex]);
    }

    //редактирование комплекса
    public function complexesUpdate(Request $request, $id)
    {
        $complex = Complex::findOrFail($id);

        $this->validate($request, [
            'complex' => ['required'],
            'sort' => ['required','numeric']
        ]);


        $complex->update($request->all());
        return redirect()->route('complexes.index')->with('ok', 'Запись изменена');
    }

    //удаление комплекса
    public function complexesDestroy($id)
    {
        $complex = Complex::findOrFail($id);

        //Удаляем коэффициент
        $complex->forceDelete();

        return redirect()->route('complexes.index')->with('ok', 'Запись удалена');
    }

    //показать форму разрешить/запретить редактирование графика платежей для консультантов
    public function editPaymentsForConsultant(){

        $role = Role::where('id',4)->first();


        return view('admin.consultants.index',['role'=>$role]);
    }

    public function editPaymentsForConsultantToggle(Request $request){

        if($request){
            if($request->get('allow') == 'yes'){

                $role = Role::where('id',4)->first();

                $permissionsArr = $role->permissions->pluck('id')->all();

                array_push($permissionsArr,10);



                $role->setPermissions($permissionsArr);

            }
            else{

                $role = Role::where('id',4)->first();

                $permissionsArr = $role->permissions->pluck('id')->all();

                if (($key = array_search('10', $permissionsArr)) !== false) {
                    unset($permissionsArr[$key]);
                }


                $role->setPermissions($permissionsArr);

            }
        }


        return redirect()->route('payments.allow_edit.index');

    }




    //////////////////////*******СУБАГЕНТЫ*************//////////////////////////

    //список субагентов
    public function subagentIndex()
    {


        $subagents = SubagentParams::all();
        return view('admin.subagents.index', ['subagents' => $subagents]);
    }

    //показать форму добавления субагента
    public function subagentCreate()
    {

        return view('admin.subagents.create');
    }

    //обработчик формы добавления субагента
    public function subagentStore(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'sub_contract_number' => 'required',
            'sub_contract_date' => 'required',
            'head_name' => 'required',
            'head_name_2' => 'required',
            'base_of_rules' => 'required',
            'adress' => 'required',
            'inn' => 'required',
            'bank_name' => 'required',
            'bik' => 'required',
            'rs' => 'required',
            'ks' => 'required',

        ]);


            $subagent = new SubagentParams();
            $subagent->name = $request->get('name');
            $subagent->sub_contract_number = $request->get('sub_contract_number');
            $subagent->sub_contract_date = $request->get('sub_contract_date');
            $subagent->head_name = $request->get('head_name');
            $subagent->head_name_2 = $request->get('head_name_2');
            $subagent->base_of_rules = $request->get('base_of_rules');
            $subagent->adress = $request->get('adress');
            $subagent->inn = $request->get('inn');
            $subagent->bank_name = $request->get('bank_name');
            $subagent->bik = $request->get('bik');
            $subagent->rs = $request->get('rs');
            $subagent->ks = $request->get('ks');
            $subagent->ogrn = $request->get('ogrn');
            $subagent->kpp = $request->get('kpp');

            $subagent->save();

        return redirect()->route('subagent.index')->with('ok', 'Новая запись добавлена');

    }

    //показать форму редактирования субагента
    public function subagentEdit($id)
    {
        $subagent = SubagentParams::findOrFail($id);




        return view('admin.subagents.edit', ['subagent' => $subagent]);
    }

    //редактирование субагента
    public function subagentUpdate(Request $request, $id)
    {

        $subagent = SubagentParams::find($id);

        $this->validate($request, [
            'name' => 'required',
            'sub_contract_number' => 'required',
            'sub_contract_date' => 'required',
            'head_name' => 'required',
            'head_name_2' => 'required',
            'base_of_rules' => 'required',
            'adress' => 'required',
            'inn' => 'required',
            'bank_name' => 'required',
            'bik' => 'required',
            'rs' => 'required',
            'ks' => 'required',

        ]);
        $subagent->update($request->all());



        return redirect()->route('subagent.index')->with('ok', 'Запись изменена');
    }

    //удаление субагента
    public function subagentDestroy($id)
    {
        $subagent = SubagentParams::findOrFail($id);


        //удаляем пользователя
        $subagent->forceDelete();

        return redirect()->route('subagent.index')->with('ok', 'Запись удалена');
    }

}
