<!DOCTYPE html>
<html lang="en" @if($userCurrentLang->rtl == 1) dir="rtl" @endif>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description')">
    <meta name="keywords" content="@yield('meta-keywords')">

    @yield('og-meta')
    <!--====== Title ======-->
    <title>{{$user->username}} - @yield('tab-title')</title>

    <!--====== Favicon Icon ======-->
    <link rel="shortcut icon" href="{{!empty($userBs->favicon) ? asset('assets/front/img/user/'.$userBs->favicon) : ''}}" type="image/png">
    <link rel="stylesheet" href="{{asset('assets/front/css/plugin.min.css')}}">
    <!--====== Common css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/common.css')}}">
    <!--====== Style css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/style1.css')}}">
    <!--====== Responsive css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/responsive1.css')}}">
    @if ($userCurrentLang->rtl == 1)
    <!--====== Common RTL Style css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/common-rtl.css')}}">
    <!--====== RTL Style css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/rtl1.css')}}">
    <!--====== RTL Responsive css ======-->
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/rtl-responsive1.css')}}">
    @endif
    @php
        if(!empty($userBs->base_color)) {
            $baseColor = $userBs->base_color;
        } else {
            $baseColor = '';
        }
    @endphp
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/common-base-color.php?color=' . $baseColor)}}">
    @if ($userBs->theme == 1)
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/base-color1.php?color=' . $baseColor)}}">
    @endif
    @if ($userBs->theme == 2)
    <link rel="stylesheet" href="{{asset('assets/front/css/profile/base-color2.php?color=' . $baseColor)}}">
    @endif

    @yield('styles')
</head>

<body>
    @if (!empty($userBs->preloader))
    <!--======= Start Preloader =======-->
    <div id="preloader">
        <img src="{{asset('assets/front/img/user/' . $userBs->preloader)}}" alt="">
    </div>
    <!--======= End Preloader =======-->
    @endif
    
    @if ($userBs->theme == 1)
        @includeif('user.profile1.partials.header1')
    @elseif ($userBs->theme == 2)
        @includeif('user.profile1.partials.header2')
    @endif

    @if (!request()->routeIs('front.user.detail.view') && $userBs->theme == 1)
    <!--====== Hero Area Start ======-->
    <section class="hero-section breadcrumbs-section breadcrumbs-section-one">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-10">
                    <div class="page-title">
                        <h1>@yield('br-title')</h1>
                        <ul class="breadcrumbs-link">
                            <li><a href="{{route('front.user.detail.view', getParam())}}">{{$keywords["Home"] ?? "Home"}}</a></li>
                            <li class="">@yield('br-link')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== Hero Area End ======-->
    @endif

    @if (!request()->routeIs('front.user.detail.view') && $userBs->theme == 2)
    <!--====== Hero Area Start ======-->
    <section class="hero-section breadcrumbs-section breadcrumbs-section-two">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-10">
                    <div class="page-title">
                        <h1>@yield('br-title')</h1>
                        <ul class="breadcrumbs-link">
                            <li><a href="{{route('front.user.detail.view', getParam())}}">{{$keywords["Home"] ?? "Home"}}</a></li>
                            <li class="">@yield('br-link')</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== Hero Area End ======-->
    @endif

    @yield('content')

    <!--====== Start Footer ======-->
    <footer class="template-footer">
        <img src="{{asset('assets/front/img/profile1/footer_left_shape.png')}}" class="left-shape" alt="">
        <img src="{{asset('assets/front/img/profile1/footer_right_shape.png')}}" class="right-shape" alt="">
        <div class="container">
            <div class="footer-content">
                <p class="mail-title">{{$keywords["Stay_Connected"] ?? "Stay Connected"}}</p>
                <a class="mail" href="mailto:{{$user->email}}">{{$user->email}}</a>
                <ul class="social-links">
                    @if(isset($social_medias))
                        @foreach($social_medias as $social_media)
                        <li><a href="{{$social_media->url}}" target="_blank"><i class="{{$social_media->icon}}"></i></a>
                        </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </footer>
    <!--====== Start End ======-->

    <!--====== Jquery ======-->
    <script src="{{asset('assets/front/js/vendor/jquery-3.4.1.min.js')}}"></script>
    <!--====== plugin js ======-->
    <script src="{{asset('assets/front/js/plugin.min.js')}}"></script>
    <script>
        "use strict";
        var rtl = {{$userCurrentLang->rtl}};
    </script>
    <!--====== Common js ======-->
    <script src="{{asset('assets/front/js/profile/common.js')}}"></script>
    <!--====== Main js ======-->
    <script src="{{asset('assets/front/js/profile/main1.js')}}"></script>
    @if (session()->has('success'))
    <script>
        "use strict";
        toastr['success']("{{ __(session('success')) }}");
    </script>
    @endif
    @yield('scripts')
</body>

</html>