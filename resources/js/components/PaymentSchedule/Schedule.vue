
<template>
    <div >



        <b-modal ref="sch_history" scrollable size="xl"  id="sch_history" hide-footer hide-header title="">

            <schedule-history v-bind:cdata= "this.cdata"   ></schedule-history>
        </b-modal>


        <b-modal ref="import_sch" id="import_sch" hide-footer hide-header title="">

            <single-file v-bind:cdata= "this.cdata" v-bind:bailout= "form.bailout" ></single-file>
        </b-modal>


        <b-modal ref="edit_sch_row" id="edit_sch_row" hide-footer title="">

            <schedule-edit-row v-bind:rid= "selectedItem" v-bind:fields="fields" v-bind:cdata= "this.cdata"     ref_id= "edit_sch_row"  ></schedule-edit-row>
        </b-modal>

        <b-modal ref="add_payment" id="add_payment" hide-footer title="">

            <schedule-edit-row v-bind:rid= "added_cost" v-bind:cdata= "this.cdata"  ref_id= "add_payment"  ></schedule-edit-row>
        </b-modal>

        <b-modal ref="delete_rows" id="delete_rows" hide-footer title="">

            <schedule-edit-row v-bind:rid= "selected" v-bind:cdata= "this.cdata"   v-bind:items= "items"   @updateItems="updateItems"  @schDeleted="schDeleted"    ref_id= "delete_rows"  ></schedule-edit-row>
        </b-modal>

        <b-form @submit="onSubmit" @reset="onReset" >


            <b-card  title="Параметры графика платежей" bg-variant="light">

                <b-row>
                    <b-col md="3" > Периодичность оплаты:
                        <b-form-select size="sm"  v-model="form.period"    :state="form.period>0"  :options="period_options" ></b-form-select>
                    </b-col>
                    <b-col md="3" >  Процент первоначального взноса : <b-form-input v-model="form.ipp" v-on:keyup="onIPPChange" autocomplete="off"  v-prs-upto100    type="text"  placeholder="00.00" size="sm"    ></b-form-input></b-col>

                    <b-col md="auto" > </b-col>
                    <b-col md="auto" > </b-col>

                </b-row>

                <b-row>
                    <b-col md="3" > Кол-во платежей :
                        <b-form-select size="sm"  v-model="form.payments_count"   :state="form.payments_count>0" :options="payments_count_opts"  ></b-form-select>
                    </b-col>
                    <b-col md="3" > Сумма первоначального взноса: <b-form-input v-inputmask-rub v-model="form.initial_payment_sum"  v-on:keyup="onIPSChange"  autocomplete="off"     size="sm"  ></b-form-input> </b-col>

                    <b-col md="auto" >    </b-col>
                    <b-col md="auto" > </b-col>

                </b-row>

                <b-row>
                    <b-col md="2" > Процент рассрочки: <b-form-input v-model="form.inst_prs"  autocomplete="off"   type="text" v-mask="'##.#'"  placeholder="00.0" size="sm"  ></b-form-input>
                    </b-col>
                    <b-col md="2" > Первоначальный платеж от: <b-form-input  v-model="form.initial_payment_date"   :state="state.initial_payment_date" type="date"   size="sm" ></b-form-input> </b-col>

                    <b-col md="2" >  Дата второго платежа: <b-form-input  v-model="form.first_payment_date"   :state="state.first_payment_date" type="date"   size="sm"  ></b-form-input>
                    </b-col>

                    <b-col md="auto" >  </b-col>

                </b-row>
            </b-card>


            <b-card   title="График платежей" bg-variant="light">
                <b-button-toolbar key-nav aria-label="Toolbar with button groups">

                    <b-button-group class="m-md-2">
                        <!--  <b-button @click="calc">Заполнить</b-button> &nbsp;-->
                        <b-button type="reset" variant="danger" :disabled="cdata.schedule_created">Очистить</b-button> &nbsp;
                        <b-button  @click="_import"  >Импортировать</b-button> &nbsp;
                        <b-dropdown id="dropdown-1" text="Изменить на основании" >&nbsp;
                            <b-dropdown-form>


                                <b-form-radio-group
                                    v-model="form.bailout"
                                    :options="this.cdata.bailouts"
                                    name="some-radios"
                                ></b-form-radio-group>

                                <!--      <b-form-radio v-model="form.bailout" name="some-radios" value="1">Заявление о досрочном погашени</b-form-radio>
                                      <b-form-radio v-model="form.bailout" name="some-radios" value="2">Доплата по замерам БТИ</b-form-radio>-->
                            </b-dropdown-form>
                        </b-dropdown> &nbsp;
                        <b-button variant="warning"  type="submit"   >Сгенерировать график</b-button> &nbsp;
                    </b-button-group>




                    <b-button-group class="m-md-2  float-right" >
                        <b-button  v-b-modal.sch_history  >История</b-button> &nbsp;
                    </b-button-group>







                </b-button-toolbar>
            </b-card>


            <b-card    bg-variant="light">
                <b-table striped   hover :items="items" :fields="fields"
                         selectable
                         :select-mode='rowSelectMode'
                         @row-selected="onRowSelected"
                         @row-dblclicked="selectRow"
                         responsive="sm"
                         :busy="cdata.loading"
                         :tbody-tr-class="rowClass"
                         ref="SchTable"


                >
                    <!--
                                <template v-slot:cell(selected)="{ rowSelected }">
                                    <template v-if="rowSelected">
                                        <span aria-hidden="true">&check;</span>
                                        <span class="sr-only">Selected</span>
                                    </template>
                                    <template v-else>
                                        <span aria-hidden="true">&nbsp;</span>
                                        <span class="sr-only">Not selected</span>
                                    </template>
                                </template>

                    -->


                    <div slot="table-busy" class="text-center text-danger my-2">
                        <b-spinner class="align-middle"></b-spinner>
                        <strong>Работаем...</strong>
                    </div>
                    <template slot="n" slot-scope="data">
                        {{ data.index + 1 }}
                    </template>
                    <!-- A custom formatted column -->
                    <template #cell(sum_payment)="data">
                        {{ data.value }}
                        <template v-if="data.item._is_last_row && data.item._remainder_kop > 0">
                            <b class="text-info">( {{data.item._remainder_kop}}   )</b>

                        </template>
                    </template>
                    <template #cell(sum_total)="data">
                        {{ data.value }}
                        <template v-if="data.item._is_last_row && data.item._total_payment_kop > 0">
                            <b class="text-info">( {{ data.item._total_payment_kop }} )</b>
                        </template>
                    </template>


                </b-table>
                <!--
                          <b-button variant="danger" @click="add_payment"  v-show="!added_payment_exists() &&  !this.cdata.schedule_created  ">Добавить платеж</b-button>
                         доп платеж во время создания графика-->
                <b-button variant="danger" @click="add_payment2"  v-show="this.cdata.schedule_created">Добавить платеж</b-button>
                <!-- доп платеж когда график уже создан-->
                <b-button variant="danger" @click="delete_selected"  v-show="this.selected.length">Удалить выделенные</b-button>

            </b-card>
        </b-form>
    </div>
</template>

<script>



export default {

    props:{
        cdata: {
            type: Object,
            default: function () {
                return {}
            }
        },
    },

    data() {

        return {

            selected: [],
            selectedItem: [],
            added_cost: {},
            state: {},
            rowSelectMode: 'multi',
            // initial_payment_prs:'00.0',
            form:{},
            form_default: { initial_payment_sum: '0.00',   initial_payment_date: '', first_payment_date:'',  inst_prs: '00.0', ipp: '00.0', period: 1, payments_count:5 , added_cost:  {} , bailout: 0 },
            period_options: [
                { value: 1, text: 'Месяц' },
                { value: 3, text: 'Квартал' },
                { value: 6, text: 'Полугодие' },
            ],
            payments_count_opts: _.range(1,61),
            items: [],


            fields: [


                {
                    key: 'n',
                    sortable: false,
                    label: '№ п/п',
                },
                {
                    key: 'payment_date',
                    sortable: false,
                    label: 'Дата платежа'
                },
                {
                    key: 'total_payings',
                    sortable: false,
                    label: 'Остаток выплат',
                    formatter: (value, key, item) => {
                        return format_currency(value )
                    }
                },
                {
                    key: 'sum_payment',
                    sortable: false,
                    label: 'Сумма без процентов',
                    formatter: (value, key, item) => {

                        return format_currency(value )
                    }
                },
                {
                    key: 'sum_prs',
                    sortable: false,
                    label: 'Сумма процентов',
                    formatter: (value, key, item) => {
                        return format_currency(value )
                    }
                },

                {
                    key: 'sum_total',
                    sortable: false,
                    label: 'Сумма с процентами',
                    formatter: (value, key, item) => {
                        //if(!parseFloat(value)) return format_currency(item.sum_payment)
                        return format_currency(value )
                    }
                },

            ],
        }
    },

    mounted()   {

        this.formReset()
        this.form.initial_payment_date = this.format_date()

        this.$root.$on('bv::modal::show', (bvEvent, modalId) => {
            // console.log( 'mshow', bvEvent, modalId   , this.selectedItem, this.selectedItem.n );
            if(modalId == 'edit_sch_row' &&  this.selectedItem.n == 1 ) {
                // bvEvent.preventDefault()
                //  app.showAlert (  'Нельзя редактировать первоначальный взнос.', 'warning' );


            }

            // передотвращение переоткрывания диалога
            this.$refs.SchTable.clearSelected()

        })


    },
    created: function() {


    },
    computed: {
        onFormChange () {
            return  this.form
        },

        onScheduleUpdatedFromOutside() {

            return this.cdata.sch_form ;

        },

        loading() {

            return this.cdata.loading ;

        },



    },
    watch: {



        onFormChange: {
            handler(   ){
                //console.log('onFormChange',    this.form   );
                var dates_ok = (this.form.initial_payment_date <= this.form.first_payment_date ) || this.form.payments_count == 1 ; // если кол-во платежей равно 1 , дата второго платежа не нужна, т.е платеж всего один
                this.state.initial_payment_date = Date.parse(this.form.initial_payment_date) > 0 && dates_ok
                this.state.first_payment_date = ( Date.parse(this.form.first_payment_date) > 0 && dates_ok ) || this.form.payments_count == 1


                //this.$emit('updateips', this.form.initial_payment_sum );
                this.calc() // заполнить график
            },
            deep: true
        },
        onScheduleUpdatedFromOutside: {
            handler( ){
                // console.log('onScheduleUpdatedFromOutside',    this.form );
                // this.calc() // заполнить график
                //.add_cost = this.cdata.add_cost

                if( this.cdata.schedule_created ) {
                    this.form = this.cdata.sch_form;
                    this.items = this.cdata.sch
                    // console.log('onCdataChange:',  this.items)
                } else {
                    //console.log('this.cdata.sch', this.cdata.sch );
                    // this.items = []
                }

                if(!parseFloat(this.form.ipp)) this.onIPSChange(  'auto' )
            },
            deep: false
        },
        loading( val )  {
            // console.log('loading:' , val)
            if(val === true) {
                this.formReset();
            }
        },

    },

    methods: {

        onIPPChange( IPP ) {
            // IPP.preventDefault()
            //IPP.stopPropagation()
            // console.log('onIPPChange',     IPP ,  this.form.ipp,  this.cdata.contract_sum );



            /*       if( parseFloat(  this.form.ipp ) > 100) {
                       this.form.ipp = 100;
                   }*/
            this.form.initial_payment_sum =  ( this.cdata.contract_sum * this.form.ipp  / 100 ) .toFixed(2)
            this.calc() // заполнить график
        },
        onIPSChange( IPS  ) {

            //IPS.preventDefault()
            //IPS.stopPropagation()
            // console.log('onIPSChange',     IPS ,   this.form.initial_payment_sum );

            this.form.ipp = (  this.form.initial_payment_sum * 100 / this.cdata.contract_sum ).toFixed(2)
            this.calc() // заполнить график
        },
        updateItems(items) {
            this.items = items
        },
        rowClass(item, type) {
            if (!item || type !== 'row') return
            if( item.sum_payment < 0 )  return 'table-danger'
            if (item.added == 1) return 'table-warning'
        },
        _import() {

            if(  !this.form.bailout) {
                app.showAlert (  'Пожалуйста укажите основание внесения изменений', 'warning' );
                return;
            }

            // импорт возможен
            app.$bvModal.show('import_sch')

        },

        added_payment_exists () {

            return typeof this.items[this.items.length-1] !== 'undefined' &&  this.items[this.items.length-1].added == 1
        },
        add_payment_validate() {
            /*   if( this.added_payment_exists() ) {
                   app.showAlert('Платеж уже добавлен!')
                   return false;
               }*/
            if( !this.form.bailout ) {
                app.showAlert (  'Пожалуйста укажите основание внесения изменений', 'warning' );
                return false;
            }
            if(  this.form.bailout!=2 ) {
                app.showAlert (  'Основание внесения изменений должно быть: доплата по замерам БТИ', 'warning' );
                return false;
            }

            return true;
        },
        add_payment() {

            if(!this.add_payment_validate()) return;

            this.items.push(
                {   n: this.items.length + 1,
                    payment_date: ''   ,
                    sum_payment:'',
                    _rowVariant: 'warning',
                    added: true
                });



        },

        add_payment2() {
            if(!this.add_payment_validate()) return;
            this.added_cost =  {
                n: this.items.length + 1,
                payment_date: ''   ,
                sum_payment:'',
                _rowVariant: 'warning',
                added: 1,
                lead_id: this.cdata.lead_id,
                bailout: this.form.bailout,
            }
            app.$bvModal.show('add_payment')
        },

        delete_selected() {

            app.$bvModal.show('delete_rows')
        },
        onRowSelected(items) {

            if( items.length ) this.selected = items

        },
        selectRow(item) {




            //if( item.length )
            this.selectedItem = item
            app.$bvModal.show('edit_sch_row')
            return;


            if(!item || !item[0] || item[0].added == 0) return;
            // доп платеж редактируем
            if( this.added_payment_exists() ) return;

            this.form.added_cost = item[0]
            app.$bvModal.show('edit_sch_row')


        },

        format_date: function(d = null) {

            if(!d) d = new Date()
            var month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            // console.log([year, month, day].join('-'));
            return  [year, month, day].join('-');

        },
        calc: function () {

            //console.log('CALC');

            if(this.cdata.schedule_created) {


                return; // дизаблед

            }

            // провверим, если проц. перв. платежа не соотв. сумме перв. платежа, то пересчитать процент (сумма введена вручную)
            /*

                            if( this.form.initial_payment_sum !=    (this.cdata.contract_sum *  this.form.initial_payment_prs )  / 100)  {
                                //this.form.initial_payment_prs = this.format_currency(  (this.form.initial_payment_sum * 100) / this.cdata.contract_sum  )
                                this.form.initial_payment_prs = '';
                            }
            */


//("CALC")

            var data = [];


            var total_payment = 0;
            var paid = 0;
            //var inst_sum = parseFloat(this.cdata.instalment_sum);
            //if(!inst_sum)
            var inst_sum = this.cdata.contract_sum
            var initial_sum = total_payment = parseFloat( this.form.initial_payment_sum )


            //console.log(initial_sum)
            data.push(
                {   n: 1,
                    payment_date: this.form.initial_payment_date ,
                    sum_prs: 0,  // сумма процентов
                    sum_payment: initial_sum,  // сумма перв. взноса
                    sum_total: initial_sum,  //  база + проценты
                    total_payings:  this.cdata.contract_sum // остаток выплат
                }
            );

            inst_sum-= initial_sum ;
            //console.log('inst', inst_sum, initial_sum, paid )
            // paid = initial_sum;
            if (! this.form.first_payment_date && this.form.payments_count != 1 ) {
                // this.formReset()

                return; // дата не задана , отдыхаем
            }



            if(this.form.payments_count > 1 ) {

                var current_date = new Date( this.form.first_payment_date  );  // this.cdata.contract_date  this.form.initial_payment_date
                // current_date = new Date(new Date(current_date).setMonth(current_date.getMonth()+ this.form.period

                // на какое количество платежей ты делишь сумму рссрочки. К примеру. если мы выбраем 12 платежей, то нужно делить на 11. Т.к. 12-ый платеж занимает первоначальный взнос
                var pc = this.form.payments_count - 1  ;
                // средний платеж
                let avg_payment_with_kop =  ( inst_sum  /  pc )
                var avg_payment = parseInt(avg_payment_with_kop)
                let kop = avg_payment_with_kop - avg_payment

                // остаток
                var remainder =   kop*pc
                // console.log(remainder, inst_sum % pc, kop*pc, kop, pc)

                var added_payment_kop = 0;

                for (var i = 1; i <= pc ; i++) {

                    current_date = (i===1) ? current_date : new
                    Date(new Date(current_date).setMonth(current_date.getMonth()+ this.form.period   )); // добавить  1,3 или 6 мес

                    var  sum_payment = avg_payment

                    var total_payings_remained = inst_sum - paid // остаток выплат без процентов
                    //console.log('total_payings_remained',total_payings_remained );
                    var sum_prs =  parseFloat( (total_payings_remained * this.form.inst_prs) / 100)



                    let added_payment_with_kop =  sum_payment  +  sum_prs
                    let added_payment = parseInt(added_payment_with_kop)
                    added_payment_kop+= (added_payment_with_kop - added_payment)

                    if(pc === i) {
                        // последний платеж, добавить остаток (копейки) в сумму с проц. и без
                        sum_payment = avg_payment + remainder
                        added_payment+= added_payment_kop
                    }

                    total_payment+= added_payment_with_kop
                    // console.log('Остаток выплат', i + 1, total_payings_remained );
                    //  console.log(avg_payment,sum_payment, sum_prs, remainder, avg_payment + remainder,  this.form.period, this.form.payments_count );
                    data.push(
                        {   n: i + 1,
                            payment_date: this.format_date( current_date  )   ,
                            sum_prs: sum_prs,
                            sum_payment: sum_payment,
                            sum_total: added_payment,
                            total_payings:  total_payings_remained, // остаток выплат
                            added: 0,
                            _is_last_row: pc === i,
                            _remainder_kop: remainder.toFixed(2),
                            _total_payment_kop: added_payment_kop.toFixed(2)
                        });

                    paid+= sum_payment // должно быть выплачено

                }

            }


            // console.log('tts0:', total_payment, parseFloat(this.form.add_cost))
            //total_payment+= parseFloat(this.this.rowItemData) // доп платежи , БТИ и др.
            if(this.added_payment_exists()) {
                data.push(this.form.added_cost);

                total_payment+= parseFloat(this.form.added_cost.sum_payment)

            }


            this.items = data


            this.$emit('updatetts', total_payment  );
            //console.log(initial_sum, this.cdata.instalment_sum, this.cdata.contract_sum ,   initial_sum,  this.cdata.contract_sum -   initial_sum)
            this.cdata.instalment_sum = this.cdata.contract_sum -   initial_sum
            //this.$emit('updateips', initial_sum   );






        },

        onSubmit(evt) {
            evt.preventDefault()

            // validation:

            if(this.added_payment_exists() ) {
                if( !Date.parse( this.form.added_cost.payment_date )   ) {
                    app.showAlert( 'Не указана дата доп. платежа' ,  'warning' )
                    return
                }

            }

            if(this.form.initial_payment_date > this.form.first_payment_date && this.form.payments_count != 1 ) {
                app.showAlert( 'Дата первого платежа не может быть больше второго' ,  'warning' )
                this.state.initial_payment_date = false
                return
            }
            if(!this.state.first_payment_date || !this.state.initial_payment_date ) {
                app.showAlert( 'Не указана дата  платежа' ,  'warning' )
                return
            }

            // выбран тип начисления пеней и если тип фикс или произв. процент (1,4), то должно быть указано фикс. значение
            // TODO! не правильно?
            if( !this.cdata.penalty_type
                ||  !(this.cdata.penalty_value>0 || ( this.cdata.penalty_type!=1 && this.cdata.penalty_type!=4 ) )
            ) {
                this.cdata.tabIndex = 2
                app.showAlert( 'Параметры начисления пени не установлены' ,  'warning' )
                return
            }

            app.start_spin();

            axios.post('api/createPaymentSchedule',  { ...this.form,  ...this.cdata } )   //
                .then(response => {
                    app.stop_spin();
                    if(response.data.error) {
                        app.showAlert ( response.data.error , 'danger' );

                    } else {
                        app.loadData(response.data);
                        app.showAlert( 'График платежей записан!' ,  'success' );



                    }



                }) .catch(function(error){
                app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

            });
        },
        onReset(evt) {
            if(evt) evt.preventDefault()
            // Reset our form values
            this.formReset()

        },
        formReset() {
            this.items = []
            this.form = _.clone(this.form_default);
            this.cdata.reset = !this.cdata.reset
            this.form.bailout = 0
        },

        schDeleted() {
            //console.log('schDeleted')
        },

    }
}
</script>
