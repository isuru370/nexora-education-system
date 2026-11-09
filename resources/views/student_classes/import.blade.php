@extends('layouts.app')

@section('title', 'Import / Update Classes')
@section('page-title', 'Import / Update Classes')

@section('content')
@php
    $help = [
        'branch' => 'Branch name or code',
        'class_name' => 'Same name = the same class (it is updated)',
        'teacher_id' => 'Teacher ID, e.g. T001',
        'teacher_name' => 'Checked against the ID',
        'subject' => 'New names are added',
        'grade' => 'New names are added',
        'medium' => 'Sinhala, English, Tamil (empty = Sinhala)',
        'class_type' => 'offline / online / hybrid',
        'class_active' => 'yes / no (empty = keep)',
        'category' => 'Theory, Revision... (empty = class only)',
        'option_label' => 'Normal, Half card... (same label = updated)',
        'fee' => 'Monthly fee, e.g. 2000',
        'is_default' => 'yes / no',
        'option_active' => 'yes / no (empty = keep)',
        'teacher_percentage' => 'e.g. 70',
        'organizer_code' => 'e.g. ORG001 (can be empty)',
        'organizer_percentage' => 'e.g. 0',
        'institution_percentage' => 'Teacher + organizer + institute = 100',
    ];
@endphp
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Import / Update Classes</h1>
                <p class="hero-subtitle">Classes, categories and fee options from one CSV file. New rows are added, existing ones are updated. Nothing is deleted.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="nx-card h-100 p-4">
                <h5 class="nx-card-title"><span class="badge bg-primary me-2">1</span> Download</h5>
                <p class="text-muted small mb-3">New classes: start from the template. To change fees or classes: download the current classes, edit them in Excel.</p>
                <div class="d-grid gap-2">
                    <a href="{{ route('class-import.template') }}" class="nx-btn nx-btn-light"><i class="bi bi-file-earmark-arrow-down"></i> Empty template</a>
                    <a href="{{ route('class-import.export') }}" class="nx-btn nx-btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Current classes</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="nx-card h-100 p-4">
                <h5 class="nx-card-title"><span class="badge bg-primary me-2">2</span> Edit</h5>
                <ul class="small text-muted mb-0 ps-3">
                    <li>One row = one fee option of a class.</li>
                    <li>A class with 3 options = 3 rows with the same class details.</li>
                    <li>Change the fee, add rows, set <code>option_active</code> to <code>no</code> to stop an option.</li>
                    <li>Save as <strong>CSV UTF-8</strong>.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="nx-card h-100 p-4">
                <h5 class="nx-card-title"><span class="badge bg-primary me-2">3</span> Upload &amp; check</h5>
                <form method="POST" action="{{ route('class-import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" class="form-control mb-3" required>
                    <button class="nx-btn nx-btn-primary w-100"><i class="bi bi-eye"></i> Upload and preview</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Nothing is saved until you confirm the preview.</p>
            </div>
        </div>
    </div>

    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-table text-primary"></i> Columns</h5>
                <p class="nx-card-subtitle">Keep the first row of the template exactly as it is.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead><tr><th>Column</th><th>What to write</th></tr></thead>
                <tbody>
                    @foreach ($columns as $column)
                        <tr><td><code>{{ $column }}</code></td><td class="small">{{ $help[$column] ?? '' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-list-check text-primary"></i> Values you can use</h5>
                <p class="nx-card-subtitle">Copy the exact spelling.</p>
            </div>
        </div>
        <div class="row g-3 p-3">
            <div class="col-md-6 col-xl-4">
                <h6 class="fw-bold">Teachers (ID - name)</h6>
                <div class="small" style="max-height: 260px; overflow:auto">
                    @foreach ($lists['teachers'] as $t)<div><code>{{ $t->custom_id }}</code> {{ $t->full_name }}</div>@endforeach
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <h6 class="fw-bold">Branches</h6>
                <div class="small mb-3">@foreach ($lists['branches'] as $b)<div>{{ $b->name }} <span class="text-muted">({{ $b->code }})</span></div>@endforeach</div>
                <h6 class="fw-bold">Organizers</h6>
                <div class="small">@forelse ($lists['organizers'] as $o)<div><code>{{ $o->code }}</code> {{ $o->name }}</div>@empty <span class="text-muted">None</span> @endforelse</div>
            </div>
            <div class="col-md-6 col-xl-4">
                <h6 class="fw-bold">Categories</h6>
                <div class="small mb-3">{{ $lists['categories']->implode(', ') ?: '-' }}</div>
                <h6 class="fw-bold">Subjects</h6>
                <div class="small mb-3">{{ $lists['subjects']->implode(', ') ?: '-' }}</div>
                <h6 class="fw-bold">Grades</h6>
                <div class="small">{{ $lists['grades']->implode(', ') ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
