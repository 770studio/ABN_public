const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */
//
 mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css');

mix.styles([
    'resources/assets/monster-admin/assets/plugins/bootstrap/css/bootstrap.min.css',
    'resources/assets/monster-admin/assets/plugins/datatables/media/css/dataTables.bootstrap4.css',
    'resources/assets/monster-admin/assets/plugins/chartist-js/dist/chartist.min.css',
    'resources/assets/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.css',
    'resources/assets/monster-admin/assets/plugins/css-chart/css-chart.css',
    'resources/assets/monster-admin/assets/plugins/toast-master/css/jquery.toast.css',
    'resources/assets/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/css/asColorPicker.css',
    'resources/assets/monster-admin/assets/plugins/multiselect/css/multi-select.css',
    'resources/assets/monster-admin/main/scss/icons/font-awesome/css/fontawesome-all.css',
    'resources/assets/monster-admin/main/css/spinners.css',
    'resources/assets/monster-admin/main/css/animate.css',
    'resources/assets/monster-admin/main/scss/icons/flag-icon-css/flag-icon.min.css',
    'resources/assets/monster-admin/main/scss/icons/linea-icons/linea.css',
    'resources/assets/monster-admin/main/scss/icons/material-design-iconic-font/css/materialdesignicons.min.css',
    'resources/assets/monster-admin/main/scss/icons/simple-line-icons/css/simple-line-icons.css',
    'resources/assets/monster-admin/main/scss/icons/themify-icons/themify-icons.css',
    'resources/assets/monster-admin/main/scss/icons/weather-icons/css/weather-icons.min.css',
    'resources/assets/monster-admin/assets/plugins/daterangepicker/daterangepicker.css',
    'resources/assets/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.css',

    'resources/assets/monster-admin/assets/plugins/switchery/dist/switchery.min.css',
    'resources/assets/monster-admin/assets/plugins/morrisjs/morris.css',
    'resources/assets/monster-admin/main/css/style.css',
    'resources/assets/monster-admin/main/css/colors/blue.css',
    'resources/assets/monster-admin/assets/plugins/bootstrap-multiselect/bootstrap-multiselect.css',
    'resources/assets/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.css'

    // 'resources/assets/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.css',

    // 'resources/assets/monster-admin/assets/plugins/bootstrap-switch/bootstrap-switch.min.css',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-table/dist/bootstrap-table.min.css',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.css',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.min.css',
    // 'resources/assets/monster-admin/assets/plugins/calendar/dist/fullcalendar.css',
    //
    // 'resources/assets/monster-admin/assets/plugins/chartist-js/dist/chartist-init.css',
    //
    // 'resources/assets/monster-admin/assets/plugins/clockpicker/dist/jquery-clockpicker.min.css',
    // 'resources/assets/monster-admin/assets/plugins/cropper/cropper.min.css',
    //


    // 'resources/assets/monster-admin/assets/plugins/dropify/dist/css/dropify.min.css',
    // 'resources/assets/monster-admin/assets/plugins/dropzone-master/dist/dropzone.css',
    // 'resources/assets/monster-admin/assets/plugins/footable/css/footable.bootstrap.min.css',
    // 'resources/assets/monster-admin/assets/plugins/gridstack/gridstack.css',
    // 'resources/assets/monster-admin/assets/plugins/horizontal-timeline/css/horizontal-timeline.css',
    // 'resources/assets/monster-admin/assets/plugins/html5-editor/bootstrap-wysihtml5.css',
    // 'resources/assets/monster-admin/assets/plugins/html5-editor/lib/css/wysiwyg-color.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/flat/_all.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/futurico/futurico.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/line/_all.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/minimal/_all.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/polaris/polaris.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/square/_all.css',
    // 'resources/assets/monster-admin/assets/plugins/icheck/skins/all.css',
    // 'resources/assets/monster-admin/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css',
    // 'resources/assets/monster-admin/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css',

    // 'resources/assets/monster-admin/assets/plugins/jsgrid/jsgrid.min.css',
    // 'resources/assets/monster-admin/assets/plugins/jsgrid/jsgrid-theme.min.css',
    // 'resources/assets/monster-admin/assets/plugins/Magnific-Popup-master/dist/magnific-popup.css',

    // 'resources/assets/monster-admin/assets/plugins/multiselect/css/multi-select.css',
    // 'resources/assets/monster-admin/assets/plugins/nestable/nestable.css',
    // 'resources/assets/monster-admin/assets/plugins/select2/dist/css/select2.min.css',
    // 'resources/assets/monster-admin/assets/plugins/summernote/dist/summernote-bs4.css',
    // 'resources/assets/monster-admin/assets/plugins/sweetalert/sweetalert.css',

    // 'resources/assets/monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.css',
    // 'resources/assets/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.css',
    //
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-2.0.2.css',
    // 'resources/assets/monster-admin/assets/plugins/wizard/steps.css',
    // 'resources/assets/monster-admin/assets/plugins/x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css'


], 'public/css/all.css');
mix.scripts([
    'resources/assets/monster-admin/assets/plugins/jquery/jquery.min.js',
    'resources/assets/monster-admin/assets/plugins/moment/moment.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap/js/popper.min.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap/js/bootstrap.min.js',
    'resources/assets/monster-admin/main/js/jquery.slimscroll.js',
    'resources/assets/monster-admin/main/js/waves.js',
    'resources/assets/monster-admin/main/js/sidebarmenu.js',
    'resources/assets/monster-admin/assets/plugins/sticky-kit-master/dist/sticky-kit.min.js',
    'resources/assets/monster-admin/main/js/custom.min.js',
    'resources/assets/monster-admin/assets/plugins/toast-master/js/jquery.toast.js',
    'resources/assets/monster-admin/main/js/toastr.js',
    'resources/assets/monster-admin/assets/plugins/styleswitcher/jQuery.style.switcher.js',
    'resources/assets/monster-admin/assets/plugins/daterangepicker/daterangepicker.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap-select/bootstrap-select.min.js',
    'resources/assets/monster-admin/assets/plugins/switchery/dist/switchery.min.js',
    'resources/assets/monster-admin/assets/plugins/raphael/raphael-min.js',
    'resources/assets/monster-admin/assets/plugins/morrisjs/morris.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap-multiselect/bootstrap-multiselect.js',
    'resources/assets/monster-admin/assets/plugins/datatables/datatables.min.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
    'resources/assets/monster-admin/assets/plugins/jquery-asColor/dist/jquery-asColor.js',
    'resources/assets/monster-admin/assets/plugins/jquery-asGradient/dist/jquery-asGradient.js',
    'resources/assets/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js',
    'resources/assets/monster-admin/assets/plugins/multiselect/js/jquery.multi-select.js'

    // 'resources/assets/monster-admin/main/js/morris-data.js',


    // 'resources/assets/monster-admin/main/js/chat.js',
    // 'resources/assets/monster-admin/main/js/dashboard2.js',
    // 'resources/assets/monster-admin/main/js/dashboard3.js',
    // 'resources/assets/monster-admin/main/js/dashboard4.js',
    // 'resources/assets/monster-admin/main/js/dashboard5.js',
    // 'resources/assets/monster-admin/main/js/flot-data.js',
    // 'resources/assets/monster-admin/main/js/footable-init.js',
    // 'resources/assets/monster-admin/main/js/jasny-bootstrap.js',
    // 'resources/assets/monster-admin/main/js/jquery.PrintArea.js',
    // 'resources/assets/monster-admin/main/js/jsgrid-init.js',
    // 'resources/assets/monster-admin/main/js/mask.init.js',

    // 'resources/assets/monster-admin/main/js/validation.js',
    // 'resources/assets/monster-admin/main/js/widget-charts.js',
    // 'resources/assets/monster-admin/main/js/widget-data.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',

    // 'resources/assets/monster-admin/assets/plugins/bootstrap-switch/bootstrap-switch.min.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-table/dist/bootstrap-table.ints.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-table/dist/bootstrap-table.min.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-treeview-master/dist/bootstrap-treeview.min.js',
    // 'resources/assets/monster-admin/assets/plugins/bootstrap-treeview-master/dist/bootstrap-treeview-init.js',
    // 'resources/assets/monster-admin/assets/plugins/calendar/jquery-ui.min.js',
    // 'resources/assets/monster-admin/assets/plugins/calendar/dist/cal-init.js',
    // 'resources/assets/monster-admin/assets/plugins/calendar/dist/fullcalendar.min.js',
    // 'resources/assets/monster-admin/assets/plugins/calendar/dist/jquery.fullcalendar.js',
    // 'resources/assets/monster-admin/assets/plugins/Chart.js/Chart.min.js',
    // 'resources/assets/monster-admin/assets/plugins/Chart.js/chartjs.init.js',
    // 'resources/assets/monster-admin/assets/plugins/chartist-js/dist/chartist-init.js',
    // 'resources/assets/monster-admin/assets/plugins/clockpicker/dist/jquery-clockpicker.min.js',
    // 'resources/assets/monster-admin/assets/plugins/cropper/cropper.min.js',
    // 'resources/assets/monster-admin/assets/plugins/cropper/cropper-init.js',

    // 'resources/assets/monster-admin/assets/plugins/date-paginator/bootstrap-datepaginator.min.js',


    // 'resources/assets/monster-admin/assets/plugins/dff/dff.js',
    // 'resources/assets/monster-admin/assets/plugins/dropify/dist/js/dropify.min.js',
    // 'resources/assets/monster-admin/assets/plugins/dropzone-master/dist/dropzone.js',
    // 'resources/assets/monster-admin/assets/plugins/echarts/echarts-init.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/excanvas.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.crosshair.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.pie.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.stack.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.time.js',
    // 'resources/assets/monster-admin/assets/plugins/flot/jquery.flot.time.js',
    // 'resources/assets/monster-admin/assets/plugins/footable/js/footable.min.js',
    // 'resources/assets/monster-admin/assets/plugins/gauge/gauge.min.js',
    // 'resources/assets/monster-admin/assets/plugins/gmaps/gmaps.min.js',
    // 'resources/assets/monster-admin/assets/plugins/gmaps/jquery.gmaps.js',
    // 'resources/assets/monster-admin/assets/plugins/gridstack/gridstack.jQueryUI.js',
    // 'resources/assets/monster-admin/assets/plugins/gridstack/gridstack.js',
    // 'resources/assets/monster-admin/assets/plugins/gridstack/lodash.js',
    // 'resources/assets/monster-admin/assets/plugins/horizontal-timeline/js/horizontal-timeline.js',
    // 'resources/assets/monster-admin/assets/plugins/html5-editor/bootstrap-wysihtml5.js',
    // 'resources/assets/monster-admin/assets/plugins/html5-editor/wysihtml5-0.3.0.js',
    // 'resources/assets/monster-admin/assets/plugins/icheck/icheck.init.js',
    // 'resources/assets/monster-admin/assets/plugins/icheck/icheck.min.js',
    // 'resources/assets/monster-admin/assets/plugins/inputmask/dist/min/jquery.inputmask.bundle.min.js',
    // 'resources/assets/monster-admin/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js',
    // 'resources/assets/monster-admin/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider-init.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery-asColor/dist/jquery-asColor.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/jquery-asColorPicker.min.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery-asGradient/dist/jquery-asGradient.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery-datatables-editable/jquery.dataTables.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery.easy-pie-chart/easy-pie-chart.init.js',
    // 'resources/assets/monster-admin/assets/plugins/jquery.easy-pie-chart/dist/jquery.easypiechart.min.js',
    // 'resources/assets/monster-admin/assets/plugins/jqueryui/jquery-ui.min.js',
    // 'resources/assets/monster-admin/assets/plugins/jsgrid/db.js',
    // 'resources/assets/monster-admin/assets/plugins/jsgrid/jsgrid.min.js',
    // 'resources/assets/monster-admin/assets/plugins/knob/jquery.knob.js',
    // 'resources/assets/monster-admin/assets/plugins/Magnific-Popup-master/dist/jquery.magnific-popup.min.js',
    // 'resources/assets/monster-admin/assets/plugins/Magnific-Popup-master/dist/jquery.magnific-popup-init.js',
    // 'resources/assets/monster-admin/assets/plugins/moment/moment.js',

    // 'resources/assets/monster-admin/assets/plugins/multiselect/js/jquery.multi-select.js',
    // 'resources/assets/monster-admin/assets/plugins/nestable/jquery.nestable.js',
    // 'resources/assets/monster-admin/assets/plugins/peity/jquery.peity.init.js',
    // 'resources/assets/monster-admin/assets/plugins/peity/jquery.peity.min.js',

    // 'resources/assets/monster-admin/assets/plugins/select2/dist/js/select2.full.min.js',
    // 'resources/assets/monster-admin/assets/plugins/session-timeout/jquery.sessionTimeout.min.js',
    // 'resources/assets/monster-admin/assets/plugins/session-timeout/session-timeout-init.js',
    // 'resources/assets/monster-admin/assets/plugins/session-timeout/idle/jquery.idletimeout.js',
    // 'resources/assets/monster-admin/assets/plugins/session-timeout/idle/jquery.idletimer.js',
    // 'resources/assets/monster-admin/assets/plugins/session-timeout/idle/session-timeout-idle-init.js',
    // 'resources/assets/monster-admin/assets/plugins/sparkline/jquery.charts-sparkline.js',
    // 'resources/assets/monster-admin/assets/plugins/sparkline/jquery.sparkline.min.js',
    // 'resources/assets/monster-admin/assets/plugins/summernote/dist/summernote-bs4.min.js',
    // 'resources/assets/monster-admin/assets/plugins/sweetalert/jquery.sweet-alert.custom.js',
    // 'resources/assets/monster-admin/assets/plugins/sweetalert/sweetalert.min.js',

    // 'resources/assets/monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.jquery.js',
    // 'resources/assets/monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js',
    // 'resources/assets/monster-admin/assets/plugins/timepicker/bootstrap-timepicker.min.js',
    // 'resources/assets/monster-admin/assets/plugins/tiny-editable/mindmup-editabletable.js',
    // 'resources/assets/monster-admin/assets/plugins/tiny-editable/numeric-input-example.js',
    // 'resources/assets/monster-admin/assets/plugins/tinymce/tinymce.min.js',
    // 'resources/assets/monster-admin/assets/plugins/typeahead.js-master/typeahead.init.js',
    // 'resources/assets/monster-admin/assets/plugins/typeahead.js-master/dist/typeahead.bundle.min.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-au-mill.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-in-mill.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-uk-mill-en.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-us-aea-en.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js',
    // 'resources/assets/monster-admin/assets/plugins/vectormap/jvectormap.custom.js',
    // 'resources/assets/monster-admin/assets/plugins/wizard/jquery.steps.min.js',
    // 'resources/assets/monster-admin/assets/plugins/wizard/jquery.validate.min.js',
    // 'resources/assets/monster-admin/assets/plugins/wizard/steps.js',
    // 'resources/assets/monster-admin/assets/plugins/x-editable/dist/bootstrap3-editable/js/bootstrap-editable.js',


],'public/js/all.js');

mix.styles([
    'resources/assets/monster-admin/assets/plugins/bootstrap/css/bootstrap.min.css',
    'resources/assets/monster-admin/main/css/style.css',
    'resources/assets/monster-admin/main/css/colors/blue.css',
    'resources/assets/monster-admin/main/scss/icons/font-awesome/css/fontawesome-all.css',

], 'public/css/login.css');
mix.scripts([
    'resources/assets/monster-admin/assets/plugins/jquery/jquery.min.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap/js/popper.min.js',
    'resources/assets/monster-admin/assets/plugins/bootstrap/js/bootstrap.min.js',
    'resources/assets/monster-admin/main/js/jquery.slimscroll.js',
    'resources/assets/monster-admin/main/js/waves.js',
    'resources/assets/monster-admin/main/js/sidebarmenu.js',
    'resources/assets/monster-admin/assets/plugins/sticky-kit-master/dist/sticky-kit.min.js',
    'resources/assets/monster-admin/main/js/custom.min.js',
    'resources/assets/monster-admin/assets/plugins/styleswitcher/jQuery.style.switcher.js',


],'public/js/login.js');


mix.copy('resources/assets/monster-admin/main/scss/icons/font-awesome/webfonts', 'public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/themify-icons/fonts/themify.woff','public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/material-design-iconic-font/fonts/materialdesignicons-webfont.woff','public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/material-design-iconic-font/fonts/materialdesignicons-webfont.woff2','public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/weather-icons/fonts/weathericons-regular-webfont.woff','public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/weather-icons/fonts/weathericons-regular-webfont.woff2','public/fonts');
mix.copy('resources/assets/monster-admin/main/scss/icons/simple-line-icons/fonts/Simple-Line-Icons.ttf','public/fonts');



mix.copy('resources/assets/monster-admin/assets/images', 'public/img');
mix.copy('resources/assets/monster-admin/main/scss/icons/flag-icon-css/flags', 'public/img/flags');
mix.copy('resources/assets/monster-admin/assets/plugins/jquery-asColorPicker-master/dist/images','public/img');
