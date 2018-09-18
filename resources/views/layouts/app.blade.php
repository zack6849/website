<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zachary Craig - @yield('title')</title>
    @section('styles')
        <link rel="stylesheet" href="{{mix('css/app.css')}}">
    @show
</head>
<body>
<div id="header">
    @include('layouts.shared.header')
</div>
<div id="content">
    @yield('content')
</div>
<div id="footer">
    @include('layouts.shared.footer')
</div>
</body>
</html>