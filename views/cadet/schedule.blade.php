@extends('layouts.app')
@section('role', 'cadet')
@section('title', 'Training Schedule')
@section('heading', 'Training Schedule')
@section('subtitle', 'Published schedule · view only')
@section('content')
  @include('partials.calendar', ['editable' => false])
@endsection
