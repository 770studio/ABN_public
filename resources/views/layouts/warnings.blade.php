@if(session('status'))

    <script>
        $.toast({
            heading: 'Ошибка!',
            text:'{{session('status')}}',
            position: 'top-right',
            loaderBg:'#ff6849',
            icon: 'error',
            hideAfter: 5000

        });
    </script>
@endif

@if(session('ok'))

    <script>
        $.toast({
            heading: 'Ок!',
            text:'{{session('ok')}}',
            position: 'top-right',
            loaderBg:'#37a650',
            icon: 'success',
            hideAfter: 5000

        });
    </script>
@endif