<!doctype html>
<html lang="en" dir="ltr">

<!-- soccer/project/login.html  07 Jan 2020 03:42:43 GMT -->
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">

<link rel="icon" href="favicon.ico" type="image/x-icon"/>

<title>Login {{ config('app.name') }}</title>

<!-- Bootstrap Core and vandor -->
<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css')}}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<!-- Core css -->
<link rel="stylesheet" href="{{ asset('assets/css/main.css')}}"/>
<link rel="stylesheet" href="{{ asset('assets/css/theme1.css')}}"/>

</head>
<body class="font-montserrat">

<div class="auth">
    <div class="auth_left">
        <div class="card">
            <div class="text-center mb-2">
                <a class="header-brand" href="{{ url('/') }}"><i class="fa fa-soccer-ball-o brand-logo"></i></a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.signin') }}" method="POST">
                    @csrf
                    <div class="card-title">Login to Dashboard</div>
                    <div class="form-group">
                        <input type="email" name="email" class="form-control" id="email" placeholder="Enter email">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" id="password" placeholder="Password">
                    </div>
                    <!-- <div class="form-group">
                        <label class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" />
                        <span class="custom-control-label">Remember me</span>
                        </label>
                    </div> -->
                    <div class="form-footer">
                        <input type="submit" class="btn btn-primary btn-block" name="signin" value="Sign in">
                    </div>
                </form>
            </div>
        </div>        
    </div>
    <div class="auth_right full_img"></div>
</div>

<script src="{{ asset('assets/bundles/lib.vendor.bundle.js') }}"></script>
<!-- Toastr -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />


<script src="{{ asset('assets/js/core.js') }}"></script>
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
</body>

<!-- soccer/project/login.html  07 Jan 2020 03:42:43 GMT -->
</html>