@extends('user.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">Custom Domain</h4>
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
            <a href="#">Custom Domain</a>
        </li>
    </ul>
</div>
<div class="row">
    <div class="col-md-12">
        <!-- Custom Domain Request Modal -->
        <div class="modal fade" id="customDomainModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLongTitle">Request Custom Domain</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if (cPackageHasCdomain(Auth::user()))
                            @if (Auth::user()->custom_domains()->where('status', 1)->count() > 0)
                                <div class="alert alert-warning">
                                    You already have an custom domain (<a target="_blank" href="//{{getCdomain(Auth::user())}}">{{getCdomain(Auth::user())}}</a>) connected with your portfolio webiste. <br>
                                    if you request another domain now & if it gets connected with our server, then your current domain (<a target="_blank" href="//{{getCdomain(Auth::user())}}">{{getCdomain(Auth::user())}}</a>) will be removed.
                                </div>
                            @endif
                        @endif
                        <form action="{{route('user-domain-request')}}" id="customDomainRequestForm" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="">Custom Domain</label>
                                <input type="text" class="form-control" name="custom_domain" placeholder="example.com" required>

                                <p class="text-secondary mb-0"><i class="fas fa-exclamation-circle"></i> Do not use <strong class="text-danger">http://</strong> or <strong class="text-danger">https://</strong> or <strong class="text-danger">www.</strong></p>

                                <p class="text-secondary mb-0"><i class="fas fa-exclamation-circle"></i> The valid format will be exactly like this one - <strong class="text-danger">domain.com</strong> or <strong class="text-danger">subdomain.domain.com</strong></p>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" form="customDomainRequestForm">Send Request</button>
                    </div>
                </div>
            </div>
        </div>

        
        @if (session()->has('domain-success'))
        <div class="alert alert-success bg-success text-white">
            <p class="mb-0">{!! nl2br(session()->get('domain-success')) !!}</p>
        </div>
        @endif
        @if ($errors->has('custom_domain'))
        <div class="alert alert-danger bg-danger text-white">
            <p class="mb-0">{!! $errors->first('custom_domain') !!}    </p>   
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card-title d-inline-block">Custom Domain</div>
                    </div>
                    <div class="offset-lg-4 col-lg-4 text-right">
                        @if (empty($rcDomain) || $rcDomain->status != 0)
                        <button class="btn btn-primary" data-toggle="modal" data-target="#customDomainModal">Request Custom Domain</button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        @if (empty($rcDomain))
                            <h3 class="text-center">REQUESTED / CONNECTED CUSTOM DOMAIN NOT AVAILABLE</h3>
                        @else
                        <div class="table-responsive">
                            <table class="table table-striped mt-3">
                                <thead>
                                    <tr>
                                        <th scope="col">Requested Domain</th>
                                        <th scope="col">Current Domain</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            @if ($rcDomain->status == 0)
                                            <a href="//{{$rcDomain->requested_domain}}" target="_blank">{{$rcDomain->requested_domain}}</a>
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td>
                                            @if (getCdomain(Auth::user()))
                                                @php
                                                    $cdomain = getCdomain(Auth::user());
                                                @endphp
                                                <a target="_blank" href="//{{$cdomain}}">{{$cdomain ?? '-'}}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>


        </div>

        

        <div class="card">
            <div class="card-header"><h4 class="mb-0"><strong>{{ $be->cname_record_section_title }}</strong></h4></div>
            <div class="card-body">
                {!! $be->cname_record_section_text !!}
            </div>
            <div class="px-4">
                <strong>Add CNAME record (take data from below table) in your custom domain from your domain registrar panel:</strong>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Host</th>
                                <th>Value</th>
                                <th>TTL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>CNAME</td>
                                <td>@</td>
                                @php
                                    $parsedUrl = parse_url(url('/'));
                                    $host =  $parsedUrl['host'];
                                @endphp
                                <td>{{$host}}.</td>
                                <td>leave it to its default value</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
