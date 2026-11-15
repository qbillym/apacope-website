@extends('layouts.app')@section('t','Permissions')@section('c')
<form method="post" action="/permissions" class="card">@csrf<h3>Who can use which feature</h3><p class="mu">Ticked means allowed. Untick a box to switch a feature off for that role. Greyed boxes are features that role does not have by design. Changes apply the next time the person opens a page.</p>
<table><tr><th>Feature</th>@foreach($roles as $k=>$l)<th style="text-align:center">{{ $l }}</th>@endforeach</tr>@foreach($feats as $fk=>$f)<tr><td><b>{{ $f['label'] }}</b></td>@foreach($roles as $rk=>$rl)@php($def=!$f['roles']||in_array($rk,$f['roles']))<td style="text-align:center"><input type="checkbox" name="allow[{{ $rk }}][{{ $fk }}]" value="1" @checked($def&&!in_array($fk,$den[$rk]??[])) @disabled(!$def)></td>@endforeach</tr>@endforeach</table>
<p style="margin-top:18px"><button class="btn">Save permissions</button></p></form>
@endsection
