@extends('layouts.app')
@section('role', 'adjutant')
@section('title', 'Training Schedule')
@section('heading', 'Training Schedule')
@section('subtitle', 'Sessions scheduled for the current academic year')
@section('actions')@if (in_array($role ?? 'adjutant', ['adjutant','corps_s1']))<button class="btn" onclick="openAddSession()">+ Add Session</button>@endif
@endsection
@section('content')
  @include('partials.calendar', ['editable' => in_array($role ?? 'adjutant', ['adjutant','corps_s1'])])
@endsection
