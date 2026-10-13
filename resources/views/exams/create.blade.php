@extends('layouts.app')

@section('title', 'Create Exam')
@section('page-title', 'Create Exam')

@section('content')
    @include('exams.partials.form', [
        'exam' => null,
        'classes' => $classes,
        'categories' => $categories,
        'halls' => $halls
    ])
@endsection