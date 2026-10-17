@extends('layouts.app')

@section('title', 'Edit Fee Option')
@section('page-title', 'Edit Fee Option')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Edit Fee Option</h1>
                <p class="hero-subtitle">{{ $feeOption->label }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-category-fee-options.index', $classCategoryFee->id) }}"
                        class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Edit Fee Option</h5>
                <p class="nx-card-subtitle">Update the predefined fee option</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('class-category-fee-options.update', [
                    'classCategoryFeeId' => $classCategoryFee->id,
                    'id' => $feeOption->id,
                  ]) }}" method="POST">
                @csrf
                @method('PUT')
                @include('class_category_fee_options.partials.form')
            </form>
        </div>
    </div>

</div>
@endsection