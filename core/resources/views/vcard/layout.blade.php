<!DOCTYPE html>
<html lang="en" @yield('rtl')>
    <head>
        <!--====== Required meta tags ======-->
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        {{-- og meta tags --}}
        <meta property="og:image" itemprop="image" content="@yield('og-image')">
        <meta property="og:image:type" content="image/jpg">
        <meta property="og:image:width" content="1024">
        <meta property="og:image:height" content="1024">
        <meta property="og:type" content="website" />
        <meta property="og:title" content="@yield('og-title')" />
        <meta property="og:description" content="@yield('og-description')" />

        <!--====== Title ======-->
        <title>{{$vcard->name}}</title>
        <link rel="shortcut icon" href="{{asset('assets/front/img/user/vcard/' . $vcard->profile_image)}}" type="image/x-icon">
        <!--====== Bootstrap css ======-->
        <link rel="stylesheet" href="{{asset('assets/front/css/bootstrap.min.css')}}">
        <!--====== FontAwesoem css ======-->
        <link rel="stylesheet" href="{{asset('assets/front/css/all.min.css')}}">
        <!--====== Magnific Popup css ======-->
        <link rel="stylesheet" href="{{asset('assets/front/css/magnific-popup.css')}}">
        <!--====== Slick css ======-->
        <link rel="stylesheet" href="{{asset('assets/front/css/slick.css')}}">
        <!--====== Style css ======-->
        <link rel="stylesheet" href="{{asset('assets/front/css/profile/vcard.css')}}">
        <!--====== RTL css ======-->
        @yield('rtl-css')
        <!--====== Base color ======-->
        @yield('base-color')
    </head>
    <body class="@yield('body')">

        @yield('content')

        <div id="snackbar"></div>

        <!--====== Jquery js ======-->
        <script src="{{asset('assets/front/js/vendor/jquery-3.4.1.min.js')}}"></script>
        <!--====== Popper js ======-->
        <script src="{{asset('assets/front/js/popper.min.js')}}"></script>
        <!--====== Bootstrap js ======-->
        <script src="{{asset('assets/front/js/bootstrap.min.js')}}"></script>
        <!--====== magnific popup js ======-->
        <script src="{{asset('assets/front/js/jquery.magnific-popup.min.js')}}"></script>
        <!--====== slick js ======-->
        <script src="{{asset('assets/front/js/slick.min.js')}}"></script>
        <!--====== lazyload js ======-->
        <script src="{{asset('assets/front/js/lazyload.min.js')}}"></script>
        <script>
            var dir = {{$vcard->direction}};
        </script>
        <!--====== vcard js ======-->
        <script src="{{asset('assets/front/js/profile/vcard.js')}}"></script>
        @if (session()->has('success'))
        <script>
            showSnackbar("Mail sent successfully!");
        </script>
        @endif

        <script>
            $('#projectDetails').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget)
                var details = button.data('details')
                var modal = $(this)
                modal.find('.modal-body').text(details)
            })
        </script>
    </body>
</html>