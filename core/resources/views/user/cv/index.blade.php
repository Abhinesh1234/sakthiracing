@extends('user.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">CVs</h4>
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
        <a href="#">CV Management</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">

      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-6">
              <div class="card-title d-inline-block">CVs</div>
            </div>
            <div class="col-lg-6">
              <button class="btn btn-danger float-right ml-2 d-none bulk-delete" data-href="{{route('user.cv.bulk.delete')}}"><i class="flaticon-interface-5"></i> Delete</button>
              <a href="#" class="btn btn-primary float-right"
                 data-toggle="modal" data-target="#createModal">
                  <i class="fas fa-plus"></i>
                  Add CV
              </a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($cvs) == 0)
                <h3 class="text-center">NO CV FOUND</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">
                            <input type="checkbox" class="bulk-check" data-val="all">
                        </th>
                        <th scope="col">CV Name</th>
                        <th scope="col">Direction</th>
                        <th scope="col">Preview</th>
                        <th scope="col">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($cvs as $key => $cv)
                        <tr>
                          <td>
                              <input type="checkbox" class="bulk-check" data-val="{{$cv->id}}">
                          </td>
                          <td>{{$cv->cv_name}}</td>
                          <td>{{$cv->direction == 1 ? 'Left to Right' : 'Right to Left'}}</td>
                          <td>
                              <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#urlsModal{{$cv->id}}"><i class="fas fa-link"></i> URLs</button>
                          </td>
                          <td>
                            <a class="btn btn-warning btn-sm editbtn" href="{{route('user.cv.edit', $cv->id)}}">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                              Edit
                            </a>
                            <a class="btn btn-secondary btn-sm" href="{{route('user.cv.info', $cv->id)}}">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                              Information
                            </a>
                            <a class="btn btn-secondary btn-sm" href="{{route('user.cv.section.index', $cv->id)}}">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                              Sections
                            </a>
                            <form class="deleteform d-inline-block" action="{{route('user.cv.delete')}}" method="post">
                              @csrf
                              <input type="hidden" name="cv_id" value="{{$cv->id}}">
                              <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                  <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </td>
                        </tr>
  
                        <!-- Modal -->
                        <div class="modal fade" id="urlsModal{{$cv->id}}" tabindex="-1" role="dialog" aria-labelledby="urlsModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="urlsModalLabel">CV URLs</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <ul>
                                            <li>
                                                @php
                                                    $pathUrl = env('WEBSITE_HOST') . '/' . Auth::user()->username . '/cv/' . $cv->id;
                                                @endphp
                                                <strong class="mr-2">Path Based URL:</strong>
                                                <a target="_blank" href="//{{$pathUrl}}">{{$pathUrl}}</a>
                                            </li>
                                            @if (cPackageHasSubdomain(Auth::user()))
                                                <li>
                                                    @php
                                                        $subUrl = Auth::user()->username . '.' . env('WEBSITE_HOST') . '/cv/' . $cv->id;
                                                    @endphp
                                                    <strong class="mr-2">Subdomain Based URL:</strong>
                                                    <a target="_blank" href="//{{$subUrl}}">{{$subUrl}}</a>
                                                </li>
                                            @endif
                                            @if (cPackageHasCdomain(Auth::user()))
                                                @php
                                                    $domUrl = Auth::user()->custom_domains()->where('status', 1)->orderBy('id', 'DESC')->first();
                                                @endphp
                                                @if (!empty($domUrl))
                                                <li>
                                                    <strong class="mr-2">Domain Based URL:</strong>
                                                    <a target="_blank" href="//{{$domUrl->requested_domain}}/cv/{{$cv->id}}">{{$domUrl->requested_domain}}/cv/{{$cv->id}}</a>
                                                </li>
                                                @endif
                                            @endif
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Create CV Modal -->
  @includeif('user.cv.create')
@endsection

@section('scripts')
    <script>
      $(document).ready(function() {
        $("select[name='direction']").on('change', function() {
          val = $(this).val();
          let $formControls = $(".form-control:not(.ltr)");

          // if RTL is selected
          if (val == 2) {
            $formControls.each(function() {
              $(this).addClass('rtl');
            });
            $("#ltrAlert").show();
          } else {
            $formControls.each(function() {
              $(this).removeClass('rtl');
            });
            $("#ltrAlert").hide();
          }
        });
      });
    </script>
@endsection
