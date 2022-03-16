@extends('admin.layout')
@section('content')
<div class="page-header">
   <h4 class="page-title">Customer Details</h4>
   <ul class="breadcrumbs">
      <li class="nav-home">
         <a href="{{route('admin.dashboard')}}">
         <i class="flaticon-home"></i>
         </a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">Customers</a>
      </li>
      <li class="separator">
         <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
         <a href="#">Customer Details</a>
      </li>
   </ul>

   <a href="{{route('admin.register.user')}}" class="btn-md btn btn-primary ml-auto">Back</a>
</div>
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center p-4">
                <img src="{{!empty($user->photo) ? asset('assets/front/img/user/'.$user->photo) : asset('assets/front/img/user/profile.jpg')}}" alt="" width="100%">
            </div>
        </div>
    </div>
   <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{__('Customer Details')}}</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Username:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->username ?? '-'}}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Path Based URL:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        <a href="//{{env('WEBSITE_HOST') . '/' . $user->username}}" target="_blank">{{env('WEBSITE_HOST') . '/' . $user->username}}</a>
                    </div>
                </div>

                @php
                    $features = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
                    $features = json_decode($features, true);
                @endphp

                @if (!empty($features) && is_array($features) && in_array('Subdomain', $features))  
                    @php
                        $subdomain = strtolower($user->username) . '.' . env('WEBSITE_HOST');
                    @endphp
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <strong>{{__('Subdomain:')}}</strong>
                        </div>
                        <div class="col-lg-6">
                            <a href="//{{$subdomain}}" target="_blank">{{$subdomain}}</a>
                        </div>
                    </div>  
                @endif

                @if (!empty($features) && is_array($features) && in_array('Custom Domain', $features))                    
                    @php
                        $cdomains = $user->user_custom_domains()->where('status', 1);
                    @endphp
                    @if ($cdomains->count() > 0)
                        @php
                            $cdomain = $cdomains->orderBy('id', 'DESC')->first()->requested_domain;
                        @endphp
                        <div class="row mb-3">
                            <div class="col-lg-6">
                                <strong>{{__('Custom Domain:')}}</strong>
                            </div>
                            <div class="col-lg-6">
                                <a href="//{{$cdomain}}" target="_blank">{{$cdomain}}</a>
                            </div>
                        </div>
                    @endif
                @endif

                @php
                    $currPackage = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
                @endphp
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Current Package:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($currPackage)
                            <a target="_blank" href="{{route('admin.package.edit', $currPackage->id)}}">{{$currPackage->title}}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>

                @php
                    $nextPackage = \App\Http\Helpers\UserPermissionHelper::nextPackage($user->id);
                @endphp
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Next Package:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($nextPackage)
                            <a target="_blank" href="{{route('admin.package.edit', $nextPackage->id)}}">{{$nextPackage->title}}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('First Name:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->first_name ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Last Name:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->last_name ?? '-'}}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Email:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->email ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Number:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->phone ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('City:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->city ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('State:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->state ?? '-'}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Country:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->country}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Address:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        {{$user->address}}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Email Status:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($user->email_verified == 1)
                            <span class="badge badge-success">Verified</span>
                        @elseif ($user->email_verified == 0)
                            <span class="badge badge-danger">Not Verified</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6">
                        <strong>{{__('Account Status:')}}</strong>
                    </div>
                    <div class="col-lg-6">
                        @if ($user->status == 1)
                            <span class="badge badge-success">Active</span>
                        @elseif ($user->status == 0)
                            <span class="badge badge-danger">Banned</span>
                        @endif
                    </div>
                </div>

            </div>
        </div>

   </div>
</div>
@endsection
