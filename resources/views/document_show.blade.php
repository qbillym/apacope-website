@extends('layouts.app')@section('t',$doc->type)@section('c')
<div class="card"><div class="row no-print"><button class="btn" onclick="window.print()">🖨 Print / Save as PDF</button><a class="btn navy" href="/documents">← All documents</a>@if($doc->user_id==auth()->id())<form method="post" action="/documents/{{ $doc->id }}" onsubmit="return confirm('Delete this document?')">@csrf @method('DELETE')<button>Delete</button></form>@endif</div>
<h2 style="margin-top:8px">{{ $doc->title }}</h2><p class="mu">Groupe Scolaire APACOPE · {{ $doc->type }} · {{ $doc->created_at->format('d F Y') }} · {{ $doc->user->name }}</p><div style="white-space:pre-wrap;line-height:1.8;margin-top:18px">{{ $doc->body }}</div></div>
@endsection
