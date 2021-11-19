<template>
    <div class="container">
        <div class="large-12 medium-12 small-12 cell">
            <label>Файл
                <input type="file" id="file" ref="file" v-on:change="handleFileUpload()"/>
            </label>
            <b-button variant="dark" v-on:click="submitFile()">Импортировать</b-button>
        </div>
    </div>
</template>

<script>
    export default {
        /*
          Defines the data used by the component
        */
        data(){
            return {
                file: ''
            }
        },
        props:{
            cdata: null, bailout: null
        },
        methods: {
            /*
              Submits the file to the server
            */
            submitFile(){
                this.cdata.loading  = true
                /*
                        Initialize the form data
                    */
                let formData = new FormData();
                formData.append('lead_id', this.cdata.lead_id );
                formData.append('bailout', this.bailout );

                /*
                    Add the form data we need to submit
                */
                formData.append('file', this.file);

                /*
                  Make the request to the POST /single-file URL
                */



                axios.post( 'api/parser',
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }
                ).then(response => {
                    this.cdata.loading  = false

                    app.$bvModal.hide('import_sch')
                    if(response.data.error) {
                        app.showAlert ( response.data.error , 'danger' );
                        return;
                    }
                    if(  response.data.lead ) {
                            app.loadData(response.data);
                            app.showAlert ( 'График успешно импортирован' ,   'success' )


                    } else {
                        app.showAlert ( 'Ошибка сервера' ,   'warning' )
                    }


                })
                    .catch(function(error){
                        app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

                    });
            },

            /*
              Handles a change on the file upload
            */
            handleFileUpload(){
                this.file = this.$refs.file.files[0];
            }
        }
    }
</script>