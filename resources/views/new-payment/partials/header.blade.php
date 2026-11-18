{{-- =============================================================
     Header — New Payment Page
============================================================= --}}
<div class="bs-d-flex tw:flex! tw:justify-between! tw:items-center! tw:mb-6! tw:flex-wrap! tw:gap-4!">

    {{-- Left: Title --}}
    <div>
        <h4 class="tw:mb-1! tw:font-bold!">
            <i class="bi bi-credit-card-2-front-fill tw:mr-2! tw:text-bs-primary!"></i>
            Payment Management
        </h4>

        <p class="tw:text-bs-secondary-color! tw:mb-0!">
            QR / Student ID → Payment → Auto Print
        </p>
    </div>


    {{-- Right: Actions --}}
    <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-2! tw:flex-wrap!">

        <span class="ui-badge tw:bg-bs-success!">
            <i class="bi bi-lightning-charge-fill tw:mr-1!"></i>
            Fast Payment
        </span>

        <a href="{{ route('payments.index') }}"
           class="ui-btn ui-btn-light tw:bs-border! custom-btn">
            <i class="bi bi-receipt tw:mr-1!"></i>
            Today's Receipts
        </a>

    </div>

</div>