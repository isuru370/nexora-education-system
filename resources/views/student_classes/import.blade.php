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

    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Import / Update Classes</h1>
                <p class="hero-subtitle">Classes, categories and fee options from one CSV file. New rows are added, existing ones are updated. Nothing is deleted.</p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
            <div class="nx-card tw:h-full! tw:p-6!">
                <h5 class="nx-card-title"><span class="ui-badge tw:[background:var(--nx-blue)]! tw:mr-2!">1</span> Download</h5>
                <p class="tw:text-(color:--nx-muted)! tw:text-[.875em] tw:mb-4!">New classes: start from the template. To change fees or classes: download the current classes, edit them in Excel.</p>
                <div class="tw:grid! tw:gap-2!">
                    <a href="{{ route('class-import.template') }}" class="nx-btn nx-btn-light"><i class="bi bi-file-earmark-arrow-down"></i> Empty template</a>
                    <a href="{{ route('class-import.export') }}" class="nx-btn nx-btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Current classes</a>
                </div>
            </div>
        </div>
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
            <div class="nx-card tw:h-full! tw:p-6!">
                <h5 class="nx-card-title"><span class="ui-badge tw:[background:var(--nx-blue)]! tw:mr-2!">2</span> Edit</h5>
                <ul class="tw:text-[.875em] tw:text-(color:--nx-muted)! tw:mb-0! tw:pl-4!">
                    <li>One row = one fee option of a class.</li>
                    <li>A class with 3 options = 3 rows with the same class details.</li>
                    <li>Change the fee, add rows, set <code>option_active</code> to <code>no</code> to stop an option.</li>
                    <li>Save as <strong>CSV UTF-8</strong>.</li>
                </ul>
            </div>
        </div>
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
            <div class="nx-card tw:h-full! tw:p-6!">
                <h5 class="nx-card-title"><span class="ui-badge tw:[background:var(--nx-blue)]! tw:mr-2!">3</span> Upload &amp; check</h5>
                <form method="POST" action="{{ route('class-import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" class="ui-form-control tw:mb-4!" required>
                    <button class="nx-btn nx-btn-primary tw:w-full!"><i class="bi bi-eye"></i> Upload and preview</button>
                </form>
                <p class="tw:text-[.875em] tw:text-(color:--nx-muted)! tw:mt-2! tw:mb-0!">Nothing is saved until you confirm the preview.</p>
            </div>
        </div>
    </div>

    <div class="nx-card tw:mb-6!">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-table tw:text-(color:--nx-blue)!"></i> Columns</h5>
                <p class="nx-card-subtitle">Keep the first row of the template exactly as it is.</p>
            </div>
        </div>
        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead><tr><th>Column</th><th>What to write</th></tr></thead>
                <tbody>
                    @foreach ($columns as $column)
                        <tr><td><code>{{ $column }}</code></td><td class="tw:text-[.875em]">{{ $help[$column] ?? '' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="nx-card tw:mb-6!">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-list-check tw:text-(color:--nx-blue)!"></i> Values you can use</h5>
                <p class="nx-card-subtitle">Copy the exact spelling.</p>
            </div>
        </div>
        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:p-4!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <h6 class="tw:font-bold!">Teachers (ID - name)</h6>
                <div class="tw:text-[.875em]" style="max-height: 260px; overflow:auto">
                    @foreach ($lists['teachers'] as $t)<div><code>{{ $t->custom_id }}</code> {{ $t->full_name }}</div>@endforeach
                </div>
            </div>
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <h6 class="tw:font-bold!">Branches</h6>
                <div class="tw:text-[.875em] tw:mb-4!">@foreach ($lists['branches'] as $b)<div>{{ $b->name }} <span class="tw:text-(color:--nx-muted)!">({{ $b->code }})</span></div>@endforeach</div>
                <h6 class="tw:font-bold!">Organizers</h6>
                <div class="tw:text-[.875em]">@forelse ($lists['organizers'] as $o)<div><code>{{ $o->code }}</code> {{ $o->name }}</div>@empty <span class="tw:text-(color:--nx-muted)!">None</span> @endforelse</div>
            </div>
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <h6 class="tw:font-bold!">Categories</h6>
                <div class="tw:text-[.875em] tw:mb-4!">{{ $lists['categories']->implode(', ') ?: '-' }}</div>
                <h6 class="tw:font-bold!">Subjects</h6>
                <div class="tw:text-[.875em] tw:mb-4!">{{ $lists['subjects']->implode(', ') ?: '-' }}</div>
                <h6 class="tw:font-bold!">Grades</h6>
                <div class="tw:text-[.875em]">{{ $lists['grades']->implode(', ') ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
