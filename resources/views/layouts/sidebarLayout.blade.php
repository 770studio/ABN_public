<!-- Left Sidebar - style you can find in sidebar.scss  -->
<!-- ============================================================== -->
<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- User profile -->
        <div class="user-profile">
            <!-- User profile image -->
            <div class="profile-img"><img src="{{config('app.url')}}/img/users/user.svg" alt="user"/></div>
            <!-- User profile text-->
            <div class="profile-text">

                <p>{{ Auth::user()->name }}</p>

                <p class="label label-rounded label-success">{{ Auth::user()->getRoleDisplayName() }}</p>

            </div>
        </div>
        <!-- End User profile text-->
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                @if(Auth::user()->getRoleId() == 1 ||Auth::user()->getRoleId() == 2||Auth::user()->getRoleId() == 3)
                    <li class="nav-devider"></li>
                    <li>
                        <a class="has-arrow" href="#" aria-expanded="false"><i class="mdi mdi-view-list"></i><span
                                class="hide-menu">Отчеты</span></a>
                        <ul aria-expanded="true" class="collapse" id="reportsMenu" style="">
                            @if(Auth::user()->getRoleId() != 3)
                                <li>
                                    <a href="{{ route('contracts')}}"><span
                                            class="hide-menu1">По реестру договоров</span></a>
                                </li>
                                <li>
                                    <a href="{{ route('sales')}}"><span class="hide-menu1">По продажам</span></a>
                                </li>
                                <li>
                                    <a href="{{ route('agents')}}"><span class="hide-menu1">Агента</span></a>
                                </li>
                                <li>
                                    <a href="{{ route('workout')}}"><span
                                            class="hide-menu1">По выработке менеджеров</span></a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ route('subagents')}}"><span class="hide-menu1">По субагентам</span></a>
                            </li>
                            @if(Auth::user()->getRoleId() != 3)
                                <li>
                                    <a href="{{ route('realForSale')}}"><span class="hide-menu1">О передаче недвижимости на реализацию </span></a>
                                </li>
                                <li>
                                    <a href="{{ route('forecast')}}"><span class="hide-menu1">Прогноз поступлений</span></a>
                                </li>
                                <li>
                                    <a href="{{ route('debtors')}}"><span class="hide-menu1">По должникам</span></a>
                                </li>
                                    <li>
                                        <a href="{{ route('subagent_reporting')}}"><span class="hide-menu1">Отчетность  субагента</span></a>
                                    </li>
                                {{--<li>--}}
                                    {{--<a href="{{ route('owners')}}"><span class="hide-menu1">По собственникам</span></a>--}}
                                {{--</li>--}}
                            @endif
                        </ul>
                    </li>

                    @if(Auth::user()->getRoleId() == 1)


                    <li class="nav-devider"></li>
                    <li>
                        <a href="{{route('payments')}}" target="_blank"><i class="fa fa-ruble-sign"></i>График платежей</a>
                    </li>
                    <li class="nav-devider"></li>

                    <li>
                        <a class="has-arrow" href="#" aria-expanded="false"><i class="ti-settings"></i><span
                                class="hide-menu">Управление системой</span></a>

                        <ul aria-expanded="true" class="collapse" style="">
                            <li><a href="{{route('users.index')}}">Пользователи</a></li>
                            <li><a href="{{route('roles.index')}}">Роли пользователей</a></li>
                            <li><a href="{{route('permissions.index')}}">Права пользователей</a></li>
                            <li><a href="{{route('subagent.index')}}">Субагенты</a></li>
                            <li><a href="{{route('coefficients.index')}}">Коэффициенты субагентов</a></li>
                            <li><a href="{{route('principals.index')}}">Правообладатели</a></li>
                            <li><a href="{{route('plans.index')}}">Планы пользователей</a></li>
                            <li><a href="{{route('complexes.index')}}">Сортировка комплексов</a></li>
                            <li><a href="{{route('income_pays_payments.index')}}">Сторонние платежи</a></li>
                            <li><a href="{{route('update_price.index')}}">Обновление цен</a></li>

                        </ul>
                    </li>

                    @elseif(Auth::user()->getRoleId() == 2)
                            <li class="nav-devider"></li>
                            <li>
                                <a class="has-arrow" href="#" aria-expanded="false"><i class="ti-settings"></i><span
                                            class="hide-menu">Управление системой</span></a>

                                <ul aria-expanded="true" class="collapse" style="">

                                    <li><a href="{{route('complexes.index')}}">Сортировка комплексов</a></li>
                                    <li><a href="{{route('payments.allow_edit.index')}}">Редактирование графика платежей</a></li>
                                    <li><a href="{{route('principals.index')}}">Правообладатели</a></li>
                                    <li><a href="{{route('plans.index')}}">Планы пользователей</a></li>
                                    <li><a href="{{route('income_pays_payments.index')}}">Сторонние платежи</a></li>


                                </ul>
                            </li>
                    @elseif(Auth::user()->getRoleId() == 3)
                        <li class="nav-devider"></li>
                        <li>
                            <a href="{{route('payments')}}" target="_blank"><i class="fa fa-ruble-sign"></i>График платежей</a>
                        </li>
                            <li class="nav-devider"></li>
                            <li>
                                <a class="has-arrow" href="#" aria-expanded="false"><i class="ti-settings"></i><span
                                            class="hide-menu">Управление системой</span></a>

                                <ul aria-expanded="true" class="collapse" style="">

                                    <li><a href="{{route('coefficients.index')}}">Коэффициенты субагентов</a></li>
                                    <li><a href="{{route('subagent.index')}}">Субагенты</a></li>

                                </ul>
                            </li>
                      @endif
                @endif
                @if(Auth::user()->getRoleId() == 2 ||Auth::user()->getRoleId() == 4)
                    <li class="nav-devider"></li>
                    <li><a href="{{route('payments')}}" target="_blank"><i class="fa fa-ruble-sign"></i>График платежей</a>
                    </li>
                @endif
                @if(Auth::user()->getRoleId() == 1 || Auth::user()->getRoleId() == 5 )
                        <li class="nav-devider"></li>
                        <li>
                            <a  href="{{route('consultants_schedule.index')}}" aria-expanded="false"><i class="mdi mdi-calendar-multiple-check"></i><span
                                    class="hide-menu">График работы консультантов</span>
                            </a>
                        </li>
                @endif

</ul>
</nav>
<!-- End Sidebar navigation -->
</div>
<!-- End Sidebar scroll-->
<!-- Bottom points-->
<div class="sidebar-footer">
<!-- item-->
<a href="{{route('logout')}}" onclick="event.preventDefault();document.getElementById('logout-form').submit();"
class="link" data-toggle="tooltip" title="Выход"><i class="mdi mdi-power"></i>

</a>
</div>
<!-- End Bottom points-->
</aside>
<!-- ============================================================== -->
