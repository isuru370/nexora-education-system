@extends('layouts.app')

@section('title', 'Import Bulk Students')
@section('page-title', 'Import Bulk Students')

@section('breadcrumb')
    <li class="ui-breadcrumb-item">
        <a href="{{ route('dashboard') }}">Dashboard</a>
    </li>
    <li class="ui-breadcrumb-item active">Students</li>
@endsection

@section('content')

<div class="ui-card">
    <div class="ui-card-header">
        <h4>Import Students (CSV / Excel)</h4>
    </div>

    <div class="ui-card-body">

        {{-- Success Message --}}
        @if(session('success'))
            <div class="ui-alert ui-alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Import Errors --}}
        @if(session('importErrors'))
            <div class="ui-alert ui-alert-danger">
                <strong>Import Errors:</strong>
                <ul class="tw:mb-0!">
                    @foreach(session('importErrors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="ui-alert ui-alert-danger">
                <ul class="tw:mb-0!">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="tw:mb-4!">
                <label class="ui-form-label">Select File</label>
                <input type="file" 
                       name="file" 
                       class="ui-form-control" 
                       accept=".csv,.xlsx,.xls" 
                       required>
                <small class="tw:text-bs-secondary-color!">
                    Allowed formats: CSV, XLSX, XLS
                </small>
            </div>

            <button type="submit" class="ui-btn ui-btn-primary">
                Import Students
            </button>

            <a href="{{ asset('student_import_template_with_dummy_data.xlsx') }}" 
               class="ui-btn ui-btn-secondary">
                Download Sample Template
            </a>
        </form>

    </div>
</div>

@endsection