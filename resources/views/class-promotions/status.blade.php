@if ($promotion->status === \App\Models\ClassPromotion::UNDONE)
    <span class="nx-badge nx-badge-gray">Undone</span>
@elseif ($promotion->status === \App\Models\ClassPromotion::DONE)
    <span class="nx-badge nx-badge-green">Done</span>
@else
    <span class="nx-badge nx-badge-amber">Starts {{ $promotion->start_date->format('Y-m-d') }}</span>
@endif
