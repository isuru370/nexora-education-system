@extends('layouts.app')

@section('title', 'ID Card Designer')
@section('page-title', 'ID Card Designer')

@push('styles')
<style>
    .cd-tabs .nav-link { border-radius: 12px; font-weight: 600; }
    .cd-stage {
        background: repeating-conic-gradient(#eef2f7 0% 25%, #f8fafc 0% 50%) 50% / 18px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        overflow: hidden;
    }
    .cd-scale { position: relative; margin: 0 auto; }
    .cd-scale > .nx-id-card {
        transform-origin: top left;
        box-shadow: 0 8px 26px rgba(15, 23, 42, .18);
        user-select: none;
    }
    .cd-scale .nx-id-field { cursor: move; outline: 1px dashed rgba(37, 99, 235, .45); }
    .cd-scale .nx-id-field.cd-off { display: none; }
    .cd-scale .nx-id-field.cd-active { outline: 2px solid #f59e0b; z-index: 50; }
    .cd-scale .nx-id-field img { pointer-events: none; }
    .cd-handle {
        position: absolute; right: 0; bottom: 0; width: 2.2mm; height: 2.2mm;
        background: #f59e0b; cursor: nwse-resize; z-index: 60; display: none;
    }
    .cd-scale .nx-id-field.cd-active .cd-handle { display: block; }
    .cd-field-list .list-group-item { cursor: pointer; display: flex; align-items: center; gap: .6rem; }
    .cd-field-list .list-group-item.active { background: #eff6ff; color: #1e3a8a; border-color: #bfdbfe; }
    .cd-props label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .2rem; }
    .cd-props .form-control, .cd-props .form-select { font-size: .9rem; }
    .cd-props input[type=color] { height: 34px; padding: 2px; }
    .cd-hint { font-size: .82rem; color: #64748b; }
</style>
@endpush

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">ID Card Designer</h1>
                    <p class="hero-subtitle">
                        Upload this institute's card template, then drag the QR code, ID number, photo and the other
                        details to their places. The printed cards, PNG downloads and PDFs use this design.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">Super Admin only</small>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <ul class="nav nav-pills cd-tabs mb-3">
            @foreach (\App\Support\CardDesign::TYPES as $item)
                <li class="nav-item">
                    <a class="nav-link {{ $item === $type ? 'active' : '' }}"
                        href="{{ route('card-designer.index', ['type' => $item]) }}">
                        {{ \App\Support\CardDesign::LABELS[$item] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('card-designer.update', $type) }}" enctype="multipart/form-data" id="cdForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="fields" id="cdFields">
            <input type="hidden" name="remove_background" id="cdRemoveBg" value="0">

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="nx-card mb-3">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-credit-card-2-front text-primary"></i>
                                    {{ \App\Support\CardDesign::LABELS[$type] }}
                                    <small class="text-muted fw-normal">{{ $design['width'] }} x {{ $design['height'] }} mm</small>
                                </h5>
                                <p class="nx-card-subtitle">
                                    Drag a box to move it; drag the orange corner to resize. Arrow keys move the
                                    selected box 0.5 mm (Shift: 0.1 mm). Sample details are shown.
                                </p>
                            </div>
                            @if ($design['saved'])
                                <span class="badge bg-success-subtle text-success">Custom design</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Default design</span>
                            @endif
                        </div>
                        <div class="p-3">
                            <div class="cd-stage" id="cdStage">
                                <div class="cd-scale" id="cdScale">
                                    @include('id-cards.card', ['card' => $card, 'attrs' => 'id="cdCard"'])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="nx-card mb-3">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-image text-primary"></i> Card template (background)</h5>
                                <p class="nx-card-subtitle">
                                    PNG or JPG, max 5 MB, the same shape as the card
                                    ({{ $design['width'] }} x {{ $design['height'] }} mm, e.g.
                                    {{ round($design['width'] / 25.4 * 300) }} x {{ round($design['height'] / 25.4 * 300) }} px).
                                    @if (!$design['background'])
                                        Now: the default template.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="p-3 d-flex flex-wrap gap-2 align-items-center">
                            <input type="file" name="background" id="cdBackground" accept="image/png,image/jpeg"
                                class="form-control" style="max-width: 380px">
                            @if ($design['background'])
                                <button type="button" class="nx-btn nx-btn-secondary" id="cdRemoveBgBtn">
                                    <i class="bi bi-x-circle"></i> Use the default template
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="nx-card mb-3">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-ui-checks text-primary"></i> Fields</h5>
                                <p class="nx-card-subtitle">Tick to show on the card; click to edit.</p>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush cd-field-list" id="cdList">
                            @foreach ($design['fields'] as $key => $field)
                                <li class="list-group-item" data-field="{{ $key }}">
                                    <input type="checkbox" class="form-check-input m-0 cd-on" data-field="{{ $key }}"
                                        {{ $field['on'] ? 'checked' : '' }} aria-label="Show {{ $field['label'] }}">
                                    <span>{{ $field['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="nx-card mb-3 cd-props" id="cdProps">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title" id="cdPropsTitle">Select a field</h5>
                                <p class="nx-card-subtitle">Sizes in millimetres.</p>
                            </div>
                        </div>
                        <div class="p-3" id="cdPropsBody" hidden>
                            <div class="row g-2">
                                <div class="col-3"><label for="p-x">Left</label><input type="number" step="0.1" min="0" class="form-control" id="p-x" data-prop="x"></div>
                                <div class="col-3"><label for="p-y">Top</label><input type="number" step="0.1" min="0" class="form-control" id="p-y" data-prop="y"></div>
                                <div class="col-3"><label for="p-w">Width</label><input type="number" step="0.1" min="1" class="form-control" id="p-w" data-prop="w"></div>
                                <div class="col-3"><label for="p-h">Height</label><input type="number" step="0.1" min="1" class="form-control" id="p-h" data-prop="h"></div>

                                <div class="col-12 cd-text-only cd-own-text"><label for="p-text">Text</label><input type="text" maxlength="120" class="form-control" id="p-text" data-prop="text"></div>

                                <div class="col-4 cd-text-only"><label for="p-size">Font size</label><input type="number" step="0.1" min="0.5" max="20" class="form-control" id="p-size" data-prop="size"></div>
                                <div class="col-4 cd-text-only"><label for="p-color">Colour</label><input type="color" class="form-control" id="p-color" data-prop="color"></div>
                                <div class="col-4 cd-text-only"><label for="p-align">Align</label>
                                    <select class="form-select" id="p-align" data-prop="align">
                                        <option value="left">Left</option><option value="center">Centre</option><option value="right">Right</option>
                                    </select>
                                </div>
                                <div class="col-6 cd-text-only">
                                    <div class="form-check mt-1"><input type="checkbox" class="form-check-input" id="p-bold" data-prop="bold"><label class="form-check-label" for="p-bold">Bold</label></div>
                                </div>
                                <div class="col-6 cd-text-only">
                                    <div class="form-check mt-1"><input type="checkbox" class="form-check-input" id="p-upper" data-prop="upper"><label class="form-check-label" for="p-upper">CAPITALS</label></div>
                                </div>

                                <div class="col-6">
                                    <div class="form-check"><input type="checkbox" class="form-check-input" id="p-bg-on"><label class="form-check-label" for="p-bg-on">Box colour</label></div>
                                    <input type="color" class="form-control" id="p-bg" value="#ffffff">
                                </div>
                                <div class="col-6">
                                    <div class="form-check"><input type="checkbox" class="form-check-input" id="p-frame-on"><label class="form-check-label" for="p-frame-on">Border</label></div>
                                    <input type="color" class="form-control" id="p-frame" value="#1d4ed8">
                                </div>
                                <div class="col-6"><label for="p-radius">Round corners</label><input type="number" step="0.1" min="0" max="10" class="form-control" id="p-radius" data-prop="radius"></div>
                                <div class="col-6 cd-box-only"><label for="p-padding">Inner space</label><input type="number" step="0.1" min="0" max="10" class="form-control" id="p-padding" data-prop="padding"></div>

                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="cdFieldDefault">
                                        <i class="bi bi-arrow-counterclockwise"></i> This field: default
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary">
                            <i class="bi bi-check-circle"></i> Save design
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if ($design['saved'])
            <form method="POST" action="{{ route('card-designer.reset', $type) }}" class="mt-3"
                onsubmit="return confirm('Go back to the default design? The uploaded template is removed.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-link text-danger p-0">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset the whole card to the default design
                </button>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    @include('card-designer.scripts')
@endpush
