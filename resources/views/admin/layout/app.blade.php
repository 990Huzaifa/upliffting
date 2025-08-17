<!doctype html>
<html lang="en" dir="ltr">

<!-- soccer/project/  07 Jan 2020 03:36:49 GMT -->
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">

<link rel="icon" href="favicon.ico" type="image/x-icon"/>

<title>@yield('title') - {{ config('app.name') }}</title>

<!-- Bootstrap Core and vandor -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}" />

<!-- Plugins css -->
<link rel="stylesheet" href="{{ asset('assets/plugins/charts-c3/c3.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/plugins/dropify/css/dropify.min.css')}}">

<!-- Core css -->
<link rel="stylesheet" href="{{ asset('assets/css/main.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/css/theme1.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}"/>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Select2 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
    rel="stylesheet"
  />
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <style></style>

@stack('style')
</head>

<body class="font-montserrat">
<!-- Page Loader -->
<div class="page-loader-wrapper">
    <div class="loader">
    </div>
</div>

<div id="main_content">

    <div id="header_top" class="header_top">
        <div class="container">
            <div class="hleft">
                <a class="header-brand" href="{{ route('admin.dashboard') }}"><i class="fa fa-soccer-ball-o brand-logo"></i></a>
                <div class="dropdown">
                    <a href="javascript:void(0)" class="nav-link user_btn"><img class="avatar" src="{{ asset('assets/images/user.png') }}" alt="User Menu" data-toggle="tooltip" data-placement="right" title="User Menu"/></a>
                </div>
            </div>
            <div class="hright">
                <div class="dropdown">
                    <a href="javascript:void(0)" class="nav-link icon settingbar"><i class="fa fa-gear fa-spin" data-toggle="tooltip" data-placement="right" title="Settings"></i></a>
                    <a href="{{ route('admin.logout') }}" class="nav-link icon logoutbar"><i class="fa-solid fa-arrow-right-from-bracket flip-horizontal" data-toggle="tooltip" data-placement="right" title="Logout"></i></a>
                    <a href="javascript:void(0)" class="nav-link icon menu_toggle"><i class="fa  fa-align-left"></i></a>
                </div>            
            </div>
        </div>
    </div>

    <div id="rightsidebar" class="right_sidebar">
        <a href="javascript:void(0)" class="p-3 settingbar float-right"><i class="fa fa-close"></i></a>
        <div class="p-4">
            <div class="mb-4">
                <h6 class="font-14 font-weight-bold text-muted">Font Style</h6>
                <div class="custom-controls-stacked font_setting">
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="font" value="font-opensans">
                        <span class="custom-control-label">Open Sans Font</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="font" value="font-montserrat" checked="">
                        <span class="custom-control-label">Montserrat Google Font</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="font" value="font-roboto">
                        <span class="custom-control-label">Robot Google Font</span>
                    </label>
                </div>
            </div>
            <hr>
            <div class="mb-4">
                <h6 class="font-14 font-weight-bold text-muted">Dropdown Menu Icon</h6>
                <div class="custom-controls-stacked arrow_option">
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="marrow" value="arrow-a">
                        <span class="custom-control-label">A</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="marrow" value="arrow-b">
                        <span class="custom-control-label">B</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="marrow" value="arrow-c" checked="">
                        <span class="custom-control-label">C</span>
                    </label>
                </div>
                <h6 class="font-14 font-weight-bold mt-4 text-muted">SubMenu List Icon</h6>
                <div class="custom-controls-stacked list_option">
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="listicon" value="list-a" checked="">
                        <span class="custom-control-label">A</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="listicon" value="list-b">
                        <span class="custom-control-label">B</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="listicon" value="list-c">
                        <span class="custom-control-label">C</span>
                    </label>
                </div>
            </div>
            <hr>
            <div>
                <h6 class="font-14 font-weight-bold mt-4 text-muted">General Settings</h6>
                <ul class="setting-list list-unstyled mt-1 setting_switch">
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Night Mode</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-darkmode">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Fix Navbar top</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-fixnavbar">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Header Dark</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-pageheader" checked="">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Min Sidebar Dark</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-min_sidebar">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Sidebar Dark</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-sidebar">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Icon Color</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-iconcolor">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Gradient Color</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-gradient">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Box Shadow</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-boxshadow">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">RTL Support</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-rtl">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                    <li>
                        <label class="custom-switch">
                            <span class="custom-switch-description">Box Layout</span>
                            <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input btn-boxlayout">
                            <span class="custom-switch-indicator"></span>
                        </label>
                    </li>
                </ul>
            </div>
            <hr>
            <div class="form-group">
                <label class="d-block">Storage <span class="float-right">77%</span></label>
                <div class="progress progress-sm">
                    <div class="progress-bar" role="progressbar" aria-valuenow="77" aria-valuemin="0" aria-valuemax="100" style="width: 77%;"></div>
                </div>
                <button type="button" class="btn btn-primary btn-block mt-3">Upgrade Storage</button>
            </div>
        </div>
    </div>

    <div id="left-sidebar" class="sidebar ">
        <h5 class="brand-name">Admin Panel <a href="javascript:void(0)" class="menu_option float-right"><i class="icon-grid font-16" data-toggle="tooltip" data-placement="left" title="Grid & List Toggle"></i></a></h5>
        <nav id="left-sidebar-nav" class="sidebar-nav">
            <ul class="metismenu">
                <li class="g_heading">Main</li>
                <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i><span>Dashboard</span></a></li>
                <li class="{{ request()->is('admin/riders') ? 'active' : '' }}"><a href="{{ url('admin/riders') }}"><i class="fa fa-list-ol"></i><span>Riders</span></a></li>
                <li class="{{ request()->is('admin/customers') ? 'active' : '' }}"><a href="{{ url('admin/customers') }}"><i class="fa fa-calendar-check-o"></i><span>Customers</span></a></li>
                <li class="{{ request()->is('admin/vehicle-type-rates') ? 'active' : '' }}"><a href="{{ url('admin/vehicle-type-rates') }}"><i class="fa fa-list-ul"></i><span>Vehicle Type Rate</span></a></li>
                <li class="{{ request()->is('admin/promo-codes') ? 'active' : '' }}"><a href="{{ url('admin/promo-codes') }}"><i class="fa fa-list-ul"></i><span>Promo Code</span></a></li>
                <li class="{{ request()->is('admin/vehicles') ? 'active' : '' }}"><a href="{{ url('admin/vehicles') }}"><i class="fa fa-list-ul"></i><span>Vehicles</span></a></li>
                <li class="{{ request()->is('admin/surge-rates') ? 'active' : '' }}">
                    <a href="{{ url('admin/surge-rates') }}">
                        <i class="fa fa-bolt"></i><span>Surge Rates</span>
                    </a>
                </li>
                <li class="{{ request()->is('admin/settings') ? 'active' : '' }}"><a href="{{ url('admin/settings') }}"><i class="fa fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </nav>
    </div>

    <div class="page">
        
        {{-- Header --}}
            @include('admin.layout.header')
            {{-- .Header --}}
            

            {{-- Content --}}
            @yield('content')
            {{-- /Content --}}


                <div class="section-body">
                    <footer class="footer">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6 col-sm-12">
                                    <a href="templateshub.net">Develop by Daniyal</a>
                                </div>
                                <!-- <div class="col-md-6 col-sm-12 text-md-right">
                                    <ul class="list-inline mb-0">
                                        <li class="list-inline-item"><a href="doc/index.html">Documentation</a></li>
                                        <li class="list-inline-item"><a href="javascript:void(0)">FAQ</a></li>
                                        <li class="list-inline-item"><a href="javascript:void(0)"
                                                class="btn btn-outline-primary btn-icon">Buy Now</a></li>
                                    </ul>
                                </div> -->
                            </div>
                        </div>
                    </footer>
                </div>
        
    </div>    
</div>


<script src="{{ asset('assets/bundles/lib.vendor.bundle.js') }}"></script>

<script src="{{ asset('assets/bundles/apexcharts.bundle.js') }}"></script>
<script src="{{ asset('assets/bundles/counterup.bundle.js') }}"></script>
<script src="{{ asset('assets/bundles/knobjs.bundle.js') }}"></script>
<script src="{{ asset('assets/bundles/c3.bundle.js') }}"></script>
<script src="{{ asset('assets/plugins/dropify/js/dropify.min.js') }}"></script>
<script src="{{ asset('assets/js/form/dropify.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

<script src="{{ asset('assets/js/core.js') }}"></script>
<script src="{{ asset('assets/js/page/project-index.js') }}"></script>
<script src="{{ asset('assets/js/custom.js') }}"></script>

<script>
    @if(Session::has('success'))
    toastr['success']('{{ session('success')['text'] }}', 'Successfully');
    @elseif(Session::has('error'))
    toastr['error']('{{ session('error')['text'] }}', 'Oops!');
    @elseif(Session::has('info'))
    toastr['info']('{{ session('info')['text'] }}', 'Alert!');
    @elseif(Session::has('warning'))
    toastr['warning']('{{ session('warning')['text'] }}', 'Alert!');
    @endif         
</script>
@stack('scripts')

</body>

<!-- soccer/project/  07 Jan 2020 03:37:22 GMT -->
</html>
