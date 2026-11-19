@extends('layouts.app')

@section('title', 'ID Card Designer')
@section('page-title', 'ID Card Designer')

@push('styles')
<style>
    .cd-tabs .ui-nav-link { border-radius: 12px; font-weight: 600; }
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
    .cd-field-list .ui-list-group-item { cursor: pointer; display: flex; align-items: center; gap: .6rem; }
    .cd-field-list .ui-list-group-item.active { background: #eff6ff; color: #1e3a8a; border-color: #bfdbfe; }
    .cd-props label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .2rem; }
    .cd-props .ui-form-control, .cd-props .ui-form-select { font-size: .9rem; }
    .cd-props input[type=color] { height: 34px; padding: 2px; }
    .cd-hint { font-size: .82rem; color: #64748b; }
</style>
@endpush

@section('content')
    <div class="dashboard-page">

        <div class="hero-section tw:mb-6!">
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
            <div class="ui-alert ui-alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="tw:flex! tw:flex-wrap! tw:items-center! tw:gap-4! tw:mb-4!">
            <ul class="ui-nav ui-nav-pills cd-tabs tw:mb-0!">
                @foreach ($types as $item)
                    <li class="ui-nav-item">
                        <a class="ui-nav-link {{ $item === $type ? 'active' : '' }}"
                            href="{{ route('card-designer.index', ['type' => $item]) }}">
                            {{ \App\Support\CardDesign::LABELS[$item] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Card stock: each branch has its own template and layout --}}
            @if ($branchId)
                <form method="GET" action="{{ route('card-designer.index') }}" class="tw:flex! tw:items-center! tw:gap-2! tw:ml-auto!">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <label for="cdBranch" class="tw:font-semibold! tw:whitespace-nowrap! tw:mb-0!"><i class="bi bi-building"></i> Branch</label>
                    <select id="cdBranch" name="branch_id" class="ui-form-select" onchange="this.form.submit()">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (int) $branch->id === (int) $branchId ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        {{-- novalidate: the side-panel boxes (e.g. Font size = 0 on a QR/photo box) are
             only helpers; the browser blocked Save on them without a message.
             The server checks and corrects every value. --}}
        <form method="POST" action="{{ route('card-designer.update', $type) }}" enctype="multipart/form-data" id="cdForm" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="fields" id="cdFields">
            @if ($branchId)
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
            @endif
            <input type="hidden" name="remove_background" id="cdRemoveBg" value="0">

            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:xl:flex-[0_0_auto] tw:xl:w-[66.66666667%]">
                    <div class="nx-card tw:mb-4!">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-credit-card-2-front tw:text-(color:--nx-blue)!"></i>
                                    {{ \App\Support\CardDesign::LABELS[$type] }}
                                    @if ($branchId)
                                        &middot; {{ optional($branches->firstWhere('id', $branchId))->name }}
                                    @endif
                                    <small class="tw:text-(color:--nx-muted)! tw:font-normal!">{{ $design['width'] }} x {{ $design['height'] }} mm</small>
                                </h5>
                                <p class="nx-card-subtitle">
                                    Drag a box to move it; drag the orange corner to resize. Arrow keys move the
                                    selected box 0.5 mm (Shift: 0.1 mm). Sample details are shown.
                                </p>
                            </div>
                            @if ($design['saved'])
                                <span class="ui-badge tw:bg-bs-success-bg-subtle! tw:text-(color:--nx-green)!">Custom design</span>
                            @else
                                <span class="ui-badge tw:bg-bs-secondary-bg-subtle! tw:text-bs-secondary!">Default design</span>
                            @endif
                        </div>
                        <div class="tw:p-4!">
                            <div class="cd-stage" id="cdStage">
                                <div class="cd-scale" id="cdScale">
                                    @include('id-cards.card', ['card' => $card, 'attrs' => 'id="cdCard"'])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="nx-card tw:mb-4!">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-image tw:text-(color:--nx-blue)!"></i> Card template (background)</h5>
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
                        <div class="tw:p-4! tw:flex! tw:flex-wrap! tw:gap-2! tw:items-center!">
                            <input type="file" name="background" id="cdBackground" accept="image/png,image/jpeg"
                                class="ui-form-control" style="max-width: 380px">
                            @if ($design['background'])
                                <button type="button" class="nx-btn nx-btn-secondary" id="cdRemoveBgBtn">
                                    <i class="bi bi-x-circle"></i> Use the default template
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                    <div class="nx-card tw:mb-4!">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-ui-checks tw:text-(color:--nx-blue)!"></i> Fields</h5>
                                <p class="nx-card-subtitle">Tick to show on the card; click to edit.</p>
                            </div>
                        </div>
                        <ul class="ui-list-group ui-list-group-flush cd-field-list" id="cdList">
                            @foreach ($design['fields'] as $key => $field)
                                <li class="ui-list-group-item" data-field="{{ $key }}">
                                    <input type="checkbox" class="ui-form-check-input tw:m-0! cd-on" data-field="{{ $key }}"
                                        {{ $field['on'] ? 'checked' : '' }} aria-label="Show {{ $field['label'] }}">
                                    <span>{{ $field['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="nx-card tw:mb-4! cd-props" id="cdProps">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title" id="cdPropsTitle">Select a field</h5>
                                <p class="nx-card-subtitle">Sizes in millimetres.</p>
                            </div>
                        </div>
                        <div class="tw:p-4!" id="cdPropsBody" hidden>
                            <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[25%]"><label for="p-x">Left</label><input type="number" step="0.1" min="0" class="ui-form-control" id="p-x" data-prop="x"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[25%]"><label for="p-y">Top</label><input type="number" step="0.1" min="0" class="ui-form-control" id="p-y" data-prop="y"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[25%]"><label for="p-w">Width</label><input type="number" step="0.1" min="1" class="ui-form-control" id="p-w" data-prop="w"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[25%]"><label for="p-h">Height</label><input type="number" step="0.1" min="1" class="ui-form-control" id="p-h" data-prop="h"></div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%] cd-text-only cd-own-text"><label for="p-text">Text</label><input type="text" maxlength="120" class="ui-form-control" id="p-text" data-prop="text"></div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%] cd-text-only"><label for="p-size">Font size</label><input type="number" step="0.1" min="0.5" max="20" class="ui-form-control" id="p-size" data-prop="size"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%] cd-text-only"><label for="p-color">Colour</label><input type="color" class="ui-form-control" id="p-color" data-prop="color"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[33.33333333%] cd-text-only"><label for="p-align">Align</label>
                                    <select class="ui-form-select" id="p-align" data-prop="align">
                                        <option value="left">Left</option><option value="center">Centre</option><option value="right">Right</option>
                                    </select>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] cd-text-only">
                                    <div class="ui-form-check tw:mt-1!"><input type="checkbox" class="ui-form-check-input" id="p-bold" data-prop="bold"><label class="ui-form-check-label" for="p-bold">Bold</label></div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] cd-text-only">
                                    <div class="ui-form-check tw:mt-1!"><input type="checkbox" class="ui-form-check-input" id="p-upper" data-prop="upper"><label class="ui-form-check-label" for="p-upper">CAPITALS</label></div>
                                </div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%]">
                                    <div class="ui-form-check"><input type="checkbox" class="ui-form-check-input" id="p-bg-on"><label class="ui-form-check-label" for="p-bg-on">Box colour</label></div>
                                    <input type="color" class="ui-form-control" id="p-bg" value="#ffffff">
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%]">
                                    <div class="ui-form-check"><input type="checkbox" class="ui-form-check-input" id="p-frame-on"><label class="ui-form-check-label" for="p-frame-on">Border</label></div>
                                    <input type="color" class="ui-form-control" id="p-frame" value="#1d4ed8">
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%]"><label for="p-radius">Round corners</label><input type="number" step="0.1" min="0" max="10" class="ui-form-control" id="p-radius" data-prop="radius"></div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%] cd-box-only"><label for="p-padding">Inner space</label><input type="number" step="0.1" min="0" max="10" class="ui-form-control" id="p-padding" data-prop="padding"></div>

                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">
                                    <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-secondary tw:mt-2!" id="cdFieldDefault">
                                        <i class="bi bi-arrow-counterclockwise"></i> This field: default
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tw:grid! tw:gap-2!">
                        <button type="submit" class="nx-btn nx-btn-primary">
                            <i class="bi bi-check-circle"></i> Save design
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if ($design['saved'])
            <form method="POST" action="{{ route('card-designer.reset', $type) }}" class="tw:mt-4!"
                onsubmit="return confirm('Go back to the default design? The uploaded template is removed.')">
                @csrf
                @method('DELETE')
                @if ($branchId)
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif
                <button type="submit" class="ui-btn ui-btn-link tw:text-(color:--nx-red)! tw:p-0!">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset the whole card to the default design
                </button>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    @include('card-designer.scripts')
@endpush
