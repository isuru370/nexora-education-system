@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                Edit Fee Option
            </h4>
            <p class="text-muted mb-0">
                Update the predefined fee option.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- ✅ FIXED: classCategoryFeeId සහ id දෙකම pass කරන්න --}}
            <form action="{{ route('class-category-fee-options.update', [
                    'classCategoryFeeId' => $classCategoryFee->id,
                    'id' => $feeOption->id,
                  ]) }}"
                  method="POST">

                @csrf
                @method('PUT')

                @include('class_category_fee_options.partials.form')

            </form>
        </div>
    </div>

</div>

@endsection