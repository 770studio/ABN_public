<template>
    <div>
        <b-form @submit="onSubmit" @reset="onReset" v-if="show">
            <b-form-group
                    id="input-group-1"
                    label="Кому:"
                    label-for="input-1"

            >
                <!--<b-form-input-->
                        <!--id="input-1"-->
                        <!--v-model="form.cessionary"-->
                        <!--required-->
                        <!--placeholder="Имя клиента"-->
                <!--&gt;</b-form-input>-->

                <b-form-input
                        id="input-11212"
                        v-model="cessionary"
                        required
                        placeholder="Имя клиента"
                ></b-form-input>
                <b-form-select size="sm"   v-model="pick_one_contact"  v-show="found_contacts.length"    :options="found_contacts"  >

                    <template slot="first">
                        <option value="0" disabled>-- Выбрать контакт --</option>
                    </template>

                </b-form-select>

            </b-form-group>

            <b-form-group id="input-group-2" label="На основании" label-for="input-2">
                <b-form-input
                        id="input-2"
                        v-model="form.base"
                        required
                        placeholder="Основание"
                ></b-form-input>
            </b-form-group>

            <b-form-group id="input-group-3" label="Комментарий" label-for="textarea">
                <b-form-textarea
                        id="textarea"
                        v-model="form.comments"
                        placeholder="Комментарий"
                        rows="3"
                        max-rows="6"
                ></b-form-textarea>
            </b-form-group>



            <b-button type="submit" variant="warning">Добавить</b-button>
            <b-button type="reset" variant="danger">Очистить форму</b-button>
        </b-form>
        <!--<b-card class="mt-3" header="Form Data Result">-->
            <!--<pre class="m-0">{{ form }}</pre>-->
        <!--</b-card>-->
    </div>
</template>

<script>
    export default {
        data() {
            return {

                form: {

                    base: '',
                    comments: '',

                },
                cessionary: '',
                found_contacts: {},
                pick_one_contact: null,

                show: true
            }
        },
        watch:{
            cessionary: function (newText) {


                this.search(newText);

            },
            pick_one_contact: function(newText) {

                if(newText) {
                    this.cessionary = newText;
                    this.found_contacts = {}
                }
            },
        },
        props:{
            cdata: null, bailout: null
        },
        methods: {

            onSubmit(evt) {
                 evt.preventDefault();
                //alert(JSON.stringify(this.form))
                this.cdata.loading  = true;
                if( !this.cdata.lead_id  ) {
                    app.showAlert( 'Загрузите действующий договор' ,  'warning' );
                    app.$bvModal.hide('addAssignmentModal');
                    return;
                }

                let formData = new FormData();
                formData.append('lead_id', this.cdata.lead_id );



                formData.append('assignor', this.cdata.client_name );
                formData.append('cessionary', this.cessionary );
                formData.append('base', this.form.base );
                formData.append('comments', this.form.comments );

                axios.post('api/addAssignment',formData )   //
                    .then(response => {

                        this.cdata.loading  = false;

                        if(response.data.error) {
                            console.log(response.data.error);
                            app.showAlert ( response.data.error , 'danger' );

                        } else {

                            app.$bvModal.hide('addAssignmentModal');
                            app.showAlert( 'Новая запись добавлена' ,  'success' );
                            this.cdata.assignment.push(response.data)

                        }



                    }) .catch(function(error){
                    app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

                });

            },
            onReset(evt) {
                evt.preventDefault()
                // Reset our form values
                this.cessionary = ''
                this.form.base = ''
                this.form.comments = ''
                this.found_contacts = {}
                // Trick to reset/clear native browser form validation state
                this.show = false
                this.$nextTick(() => {
                    this.show = true
                })
            },
            search: _.debounce(function(newText)  {

                // console.log(newText);

                axios.post('api/searchCessionary', { query: newText  })
                    .then(response => {

                        // console.log(response.data.name);

                        if(response.data.name ) {

                            //если нашлт больше 1 контакта
                            if (response.data.name.length > 1){
                                // выбор
                                this.pick_one_contact = 0;
                                this.found_contacts = response.data.name;
                            }
                            else {
                                this.found_contacts = {}
                            }


                        }

                    });



            }, 500) ,

        }
    }
</script>
