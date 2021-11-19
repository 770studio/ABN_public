
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{config('app.url')}}/css/app.css">
<link rel="stylesheet" href="{{config('app.url')}}/css/payment.css">
<script src="{{config('app.url')}}/js/app.js?ver=1.2.5"></script>

<style>



    [v-cloak] {
        display: none;
    }

</style>




<div class="container-fluid" id="app"  v-cloak     >

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <h3 class="text-themecolor" id="pTitle">Модуль графиков платежей и пени</h3>


            </div>
            <div class="col-lg-6 text-right mt-4">
                <b-modal ref="rate_history" id="rate_history" size="lg" hide-footer title="История изменения ставки">

                    <rate_history></rate_history>

                </b-modal>


                <b-modal hide-footer ref="edit_rate" id="edit_rate" title="">

                    <template v-slot:modal-title>
                        <span>Добавить ставку &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        <!--
                                                <b-button variant="warning" id="showRateHistory" v-on:click="$bvModal.show('rate_history')" >История</b-button>
                        -->

                    </template>


                    <b-row class="justify-content-md-center">
                        <b-col md="12"> Ставка рефинансирования:
                            <b-form-input v-model="settings.rate"
                                          autocomplete="off" placeholder="00.00" v-mask="'##.##'" type="text"
                                          size="sm"></b-form-input>
                        </b-col>
                    </b-row>
                    <b-row class="justify-content-md-center">
                        <b-col md="12"> Дата начала действия ставки:
                            <b-form-input v-model="settings.start_date" :state="state.start_date"
                                          type="date"></b-form-input>
                        </b-col>
                    </b-row>
                    <b-row class="justify-content-md-center">
                        <b-col md="12">
                            <b-button variant="warning" id="pSaveSettings" v-on:click="submitSettings()">Сохранить
                            </b-button>
                        </b-col>
                    </b-row>

                </b-modal>


                <a class="btn btn-secondary" href="{{route('profile')}}" target="_blank">Профиль</a>
                <a class="btn btn-danger" href="{{route('logout')}}" onclick="event.preventDefault();
                            document.getElementById('logout-form').submit();">
                     Выход</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>

                <br><br>
                <b-button variant="warning" id="editInterestRate" v-on:click="$bvModal.show('edit_rate_history')"
                >Ставка Банка России</b-button>

            </div>
        </div>

    </div>

    <search-contract  v-bind:cdata= "cData" ></search-contract>  {{--@update:cdata="updateCData"--}}
    <!--сделать отчет-->
    {{--<make-report  v-bind:cdata= "cData" ></make-report>--}}

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 text-right">


                <form method="post"   v-bind:action="'api/makeReport/' + cData.lead_id" >
                    @csrf

                    <button class="btn btn-success"  type="submit" >Отчет по договору</button>
                </form>
            </div>
        </div>

    </div>

    <transition name="fade">
        <div   class="container-fluid"  id="panel" >
            <b-card no-body>
                <b-tabs pills card id="pTabs" v-model="cData.tabIndex">


                    <b-tab title="Общее" >
                        <b-card-text>
                            <common-data v-bind:cdata= "cData" ></common-data>
                        </b-card-text>
                    </b-tab>
                    <b-tab title="График платежей">
                        <b-card-text>
                            <schedule-data v-bind:cdata= "cData"     @updateIPS="updateIPS"   @updateTTS="updateTTS"   ></schedule-data>
                        </b-card-text>
                    </b-tab>
                    <b-tab title="Пени"><b-card-text> <penalty-data v-bind:cdata= "cData"    ></penalty-data></b-card-text></b-tab>
                    <b-tab title="Переуступка прав">
                        <b-card-text>
                            <assignment-data v-bind:cdata= "cData"  ></assignment-data>
                        </b-card-text>

                    </b-tab> {{--
                    <b-tab title="Основные настройки"><b-card-text>
                               Ставка рефинансирования: <b-form-input  v-model="settings.rate"  type="text"   size="sm" ></b-form-input>

                                <b-button variant="warning" id="pSaveSettings" v-on:click="submitSettings()" >Сохранить</b-button>

                        </b-card-text>
                    </b-tab>
                                <b-tab title="Расторжение"><b-card-text>Расторжение Contents 1</b-card-text></b-tab>--}}


                </b-tabs>
            </b-card>
        </div>
    </transition>

    <br> <br>
    <contract-data v-bind:cdata="cData"></contract-data>


    <b-modal size="xl" hide-footer ref="edit_rate_history" id="edit_rate_history" title="">
        <refin-rates></refin-rates>
    </b-modal>


</div>
















<script>

    function format_currency(value ) {
        if(!value) return '0.00';
        //console.log(value, key, item)
        return  parseFloat(value).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1 ')
    }



    Vue.directive('inputmask-rub', {
        bind: function(el) {
            new Inputmask({
                alias             : 'currency',
                removeMaskOnSubmit: false,
                groupSeparator    : ' ',
                autoUnmask        : true,
                unmaskAsNumber    : true,
                digitsOptional    : false,
                autoGroup         : true,
                allowMinus        : true,
                allowPlus         : false,
                showMaskOnHover   : false,
                showMaskOnFocus   : false,
                integerDigits     : 10,
                digits            : 2,
                radixPoint        :  '.',
                prefix: "₱ ",
            }).mask(el);
        },
    });



    Vue.directive('prs-upto100', {
        bind: function(el) {


            new Inputmask({
                alias             : 'decimal',
                removeMaskOnSubmit: false,
                autoUnmask        : true,
                autoGroup         : true,
                allowMinus        : false,
                allowPlus         : false,
                integerDigits     : 3,
                digits            : 2,
                radixPoint        :  '.',




            }).mask(el);
        },
    });




    Vue.mixin({
        methods: {
            getManager( id ) {

            }


        }
    })

    const app = new Vue({
        el: '#app',

        data: {
            settings: {rate: 0, start_date: new Date()},
            state: {start_date: false, rate: false},
            cData: {loading: false, schedule_created: false, reset: false},


        },
        components: {
            // SearchContract
        },
        mounted() {

            axios.post( 'api/getSettings'
            ).then(response => {
                if(  response.data ) {
                    app.settings = response.data
                } else {
                    app.showAlert ( 'Ошибка сервера. Невозможно получить настройки' ,   'warning' )
                }
            })
                .catch(function(error){
                    app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )
                });
        },

        methods: {


            updateIPS( ips ) {
                // initial_payment_sum
                //this.cData.ips = ips;
                this.cData.instalment_sum = this.cData.contract_sum - ips
                //this.cData.total_sum =  ???
                //console.log('ips updated:', this.cData )

            },
            updateTTS( tts ) {
               // console.log('tts:',tts, this.cData, tts.toFixed(2) )
                this.cData.total_sum = tts.toFixed(2);


            },
            loadData: function(r) {

                if(!r)  { // сброс
                    this.cData = {}
                    return;

                }
                this.cData = {...this.cData, ...r.lead } ;
                this.cData.sch_form = r.inst;
                this.cData.bailouts = r.bailouts;

                this.cData.sch = r.sch ;
                this.cData.sch_history = r.sch_history ;

                if(r.sch && r.sch.length) {
                   // console.log('schedule_created',  this.cData.schedule_created)
                    // есть график
                    this.cData.schedule_created = true;

                } else {
                   // console.log('schedule NOT created',  this.cData.schedule_created )
                     this.cData.schedule_created = false ;
                     // this.cData.ips = 0; //this.cData.schedule_created = false ;
                   //  this.cData.reset = true;
                }
                if(r.penalty ) {
                    this.cData.penalty = r.penalty;
                    this.cData.penalty_statuses = r.penalty_statuses
                }

                //переуступка
                if(r.assignment ) {
                    this.cData.assignment = r.assignment;

                }


            },


            showAlert: function( alerttext , variant = 'default' , sec = 0 ) {

               var type = {
                   default : 'Сообщение:',
                   info: 'Информация:',
                   warning: 'Предупреждение!',
                   success: 'Выполнено:',
                   danger: 'Ошибка!',
               }

                this.$bvToast.toast(alerttext, {
                    title: type[variant],
                    variant: variant,
                    solid: true
                })


            },
            start_spin: function () {
                this.cData.loading = true ;
            } ,
            stop_spin: function () {
                this.cData.loading = false;
            },
            editInterestRate () {

            },
            submitSettings (){

                //TODO!! EMPLOYEE_ID
                axios.post( 'api/updateSettings', {'rate': this.settings.rate, 'start_date' : this.settings.start_date},
                ).then(response => {
                    if(response.data.error) {
                        app.showAlert ( response.data.error , 'danger' );
                        return;
                    }
                    if(  response.data ) {
                        app.showAlert ( 'Настройки записаны' ,   'success' )

                    } else {
                        app.showAlert ( 'Ошибка сервера. Попробуйте еще раз.' ,   'warning' )
                    }


                })
                    .catch(function(error){
                        app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

                    });









            },

            updateContractData() {
                app.start_spin();
                axios.post('api/updateContract',  _.pick(this.cData, ['comments' , 'ttl_area', 'nds', 'lead_id']) )   //
                    .then(response => {
                        app.stop_spin();
                        if(response.data.error) {
                            app.showAlert ( response.data.error , 'danger' );

                        } else {

                            app.showAlert( 'Успешно обновлено' ,  'success' );

                            //this.stext =
                            //app.loadData(response.data);
                        }


                    }).catch(function (error) {
                    app.showAlert('Ошибка сервера. Обратитесь к администратору.' + error, 'danger')

                });


            }
        },
        watch: {

            settings: {
                deep: true,
                handler() {
                    this.state.start_date = Date.parse(this.settings.start_date) > 0
                }
            }
        },
        computed: {}

    });


</script>




