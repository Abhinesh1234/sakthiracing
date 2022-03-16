@extends('user.layout')

@section('content')
@if (!empty($features) && is_array($features) && in_array('Subdomain', $features))
<div class="page-header">
    <h4 class="page-title">Subdomain & Path URL</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{route('user-dashboard')}}">
            <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">Domains & URLs</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">Subdomain & Path URL</a>
        </li>
    </ul>
</div>
@else
<div class="page-header">
    <h4 class="page-title">Path Based URL</h4>
    <ul class="breadcrumbs">
        <li class="nav-home">
            <a href="{{route('user-dashboard')}}">
            <i class="flaticon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">Path Based URL</a>
        </li>
    </ul>
</div>
@endif

<div class="row">
    @if (cPackageHasSubdomain(Auth::user()))
        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">Subdomain</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">Subdomain</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                @php
                                                    $subdomain = strtolower(Auth::user()->username) . "." . env('WEBSITE_HOST');
                                                @endphp
                                                <a href="//{{$subdomain}}" target="_blank">{{$subdomain}}</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            
        </div>
    @endif

    <div class="{{cPackageHasSubdomain(Auth::user()) ? 'col-md-6' : 'col-md-12'}}">
        <div class="card">
            <div class="card-header card-title">
                Path Based URL
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-striped table">
                        <thead>
                            <tr>
                                <th>URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    @php
                                        $url = env('WEBSITE_HOST') . '/' . Auth::user()->username;
                                    @endphp
                                    <a href="//{{$url}}" target="_blank">{{$url}}</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
    <script>
        $(document).ready(function() {

            $('.requestBtn').on('click', function () {

                swal({
                title: 'Are you sure?',
                text: "You want to send subdomain request?",
                type: 'warning',
                buttons: {
                    confirm: {
                    text: 'Send Request',
                    className: 'btn btn-success'
                    },
                    cancel: {
                    visible: true,
                    className: 'btn btn-danger'
                    }
                }
                }).then((Request) => {
                if (Request) {
                    $(".requestBtn").attr('type', 'submit');
                    $("#requestForm").submit();
                } else {
                    swal.close();
                }
                });
            });
        });
    </script>
@endsection