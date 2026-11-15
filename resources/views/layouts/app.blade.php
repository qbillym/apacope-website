<!doctype html><html lang="en"><head><meta charset="utf-8"><script>try{if(localStorage.getItem('apa_low')==='1')document.documentElement.dataset.low='1'}catch(e){}</script><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('t','GS APACOPE')</title><link rel="stylesheet" href="{{ asset('css/apacope.css') }}"></head>
<body class="app">@php($u=auth()->user())
@php($isClassTeacher=$u->role==='trainer'&&$u->isClassTeacher())
@php($roleLabel=$u->role==='trainer'?($isClassTeacher?'Class teacher':'Subject teacher'):$u->label())
@php($menu=\App\Services\Menu::items())
@php($hasTssAccess=$u->role==='dost'||($u->role==='trainer'&&$u->classes()->where('section','tvet')->exists()))
@php($g='')
@php($active=collect($menu)->first(fn($m)=>request()->is(ltrim($m[2],'/'))))
@php($crumbGroup=$active[0]??'Overview')
@php($crumbGroup=$crumbGroup==='Academic'?'Academics':$crumbGroup)
@php($initials=collect(explode(' ',$u->name))->map(fn($w)=>mb_substr($w,0,1))->take(2)->join(''))
@if(session('splash'))@include('loader')@endif
<aside><div class="side-profile"><img class="logo-transparent" src="{{ asset('images/logo.png') }}" alt="GSA"><div><b>{{ $roleLabel }}</b><small>{{ $u->name }}</small></div></div>
<nav>@foreach($menu as $m)@if((!$m[4]||in_array($u->role,$m[4]))&&($m[2]!=='/attendance'||$u->role!=='trainer'||$isClassTeacher)&&($m[2]!=='/tss'||$hasTssAccess)&&!\App\Services\Perm::denied($u->role,trim($m[2],'/')))@if($m[0]!==$g)<h4>{{ $m[0] }}</h4>@php($g=$m[0])@endif<a href="{{ $m[2] }}" class="{{ request()->is(ltrim($m[2],'/'))?'on':'' }}"><i>{{ $m[3] }}</i>{{ $m[1] }}</a>@endif @endforeach</nav>
<div class="side-footer"><span>GSA APACOPE</span><small>Learning together, moving forward.</small><form method="post" action="/logout">@csrf<button>↗ Sign out</button></form></div></aside>
<div class="mn"><div class="tb">
<div class="crumb"><span>{{ $crumbGroup }}</span><i>/</i><b>@yield('t')</b></div>
<div class="tb-meta"><span class="term">{{ \App\Models\Setting::val('term','Term 2') }} · {{ \App\Models\Setting::val('academic_year',date('Y')) }}</span><span class="today">{{ now()->format('l, d F Y') }}</span>
<div class="tb-profile"><div class="av">{{ $initials }}</div><div><b>{{ $u->name }}</b><small>{{ $roleLabel }}</small></div></div>
<button class="tgl no-print" type="button" onclick="window.print()" title="Print or save as PDF" aria-label="Print or save as PDF">🖨</button><button class="tgl" type="button" onclick="lowd()" title="Low-data mode for slow internet" aria-label="Toggle low-data mode">📶</button></div></div>
<main class="{{ request()->is('home')?'dashboard-main':'' }}"><h1 style="margin-bottom:18px">@yield('t')</h1>@if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif @if(session('err'))<div class="flash errf">{{ session('err') }}</div>@endif @if($errors->any())<div class="flash errf">{{ $errors->first() }}</div>@endif @yield('c')</main></div>
<script src="{{ asset('js/app.js') }}"></script></body></html>
