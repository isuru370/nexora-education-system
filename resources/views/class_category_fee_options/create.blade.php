@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                Create Fee Option
            </h4>

            <p class="text-muted mb-0">
                Add a new predefined fee option.
            </p>
        </div>
    </div>


    <div class="card">

        <div class="card-body">

            <form
                action="{{ route(
                    'class-category-fee-options.store',
                    $classCategoryFee->id
                ) }}"
                method="POST"
            >

                @csrf

                @include(
                    'class_category_fee_options.partials.form'
                )

            </form>

        </div>

    </div>

</div>

@endsection