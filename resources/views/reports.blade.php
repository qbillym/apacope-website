@extends('layouts.app')@section('t','Reports & exports')@section('c')
<div class="card"><h3>Download a report</h3><p class="mu">Files open in Excel. They contain only the classes you are allowed to see.</p><div class="row">@foreach($kinds as $k=>$l)<a class="btn navy" href="/reports/{{ $k }}">⇩ {{ $l }}</a>@endforeach</div></div>
@endsection
