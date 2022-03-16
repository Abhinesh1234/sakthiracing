@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">Package Features</h4>
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
        <a href="#">Packages Management</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">Package Features</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <div class="card-title d-inline-block">Package Features</div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-8 offset-lg-2">
              <form id="permissionsForm" class="" action="{{route('admin.package.features')}}" method="post">
                {{csrf_field()}}
                <div class="alert alert-warning">
                  Only these selected features will be visible in frontend Pricing Section
                </div>
                <div class="form-group">
                    <label class="form-label">Package Features</label>
                    <div class="selectgroup selectgroup-pills">
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Custom Domain" class="selectgroup-input" @if(is_array($features) && in_array('Custom Domain', $features)) checked @endif>
                            <span class="selectgroup-button">Custom Domain</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Subdomain" class="selectgroup-input" @if(is_array($features) && in_array('Subdomain', $features)) checked @endif>
                            <span class="selectgroup-button">Subdomain</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="QR Builder" class="selectgroup-input" @if(is_array($features) && in_array('QR Builder', $features)) checked @endif>
                            <span class="selectgroup-button">QR Builder</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="vCard" class="selectgroup-input" @if(is_array($features) && in_array('vCard', $features)) checked @endif>
                            <span class="selectgroup-button">vCard</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Online CV & Export" class="selectgroup-input" @if(is_array($features) && in_array('Online CV & Export', $features)) checked @endif>
                            <span class="selectgroup-button">Online CV & Export</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Follow/Unfollow" class="selectgroup-input" @if(is_array($features) && in_array('Follow/Unfollow', $features)) checked @endif>
                            <span class="selectgroup-button">Follow/Unfollow</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Blog" class="selectgroup-input" @if(is_array($features) && in_array('Blog', $features)) checked @endif>
                            <span class="selectgroup-button">Blog</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Portfolio" class="selectgroup-input" @if(is_array($features) && in_array('Portfolio', $features)) checked @endif>
                            <span class="selectgroup-button">Portfolio</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Achievements" class="selectgroup-input" @if(is_array($features) && in_array('Achievements', $features)) checked @endif>
                            <span class="selectgroup-button">Achievements</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Skill" class="selectgroup-input" @if(is_array($features) && in_array('Skill', $features)) checked @endif>
                            <span class="selectgroup-button">Skill</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Service" class="selectgroup-input" @if(is_array($features) && in_array('Service', $features)) checked @endif>
                            <span class="selectgroup-button">Service</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Experience" class="selectgroup-input" @if(is_array($features) && in_array('Experience', $features)) checked @endif>
                            <span class="selectgroup-button">Experience</span>
                        </label>
                        <label class="selectgroup-item">
                            <input type="checkbox" name="features[]" value="Testimonial" class="selectgroup-input" @if(is_array($features) && in_array('Testimonial', $features)) checked @endif>
                            <span class="selectgroup-button">Testimonial</span>
                        </label>
                    </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="card-footer">
          <div class="form">
            <div class="form-group from-show-notify row">
              <div class="col-12 text-center">
                <button type="submit" id="permissionBtn" class="btn btn-success">Update</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
