@php
    $map = [
        'upcoming' => ['nx-badge-blue', 'Upcoming'],
        'ongoing' => ['nx-badge-green', 'Ongoing'],
        'completed' => ['nx-badge-gray', 'Completed'],
        'inactive' => ['nx-badge-red', 'Inactive'],
        'paid' => ['nx-badge-green', 'Paid'],
        'partly_paid' => ['nx-badge-amber', 'Part paid'],
        'overdue' => ['nx-badge-red', 'Overdue'],
        'due' => ['nx-badge-gray', 'Due'],
        'active' => ['nx-badge-green', 'Active'],
        'cancelled' => ['nx-badge-red', 'Cancelled'],
        'scheduled' => ['nx-badge-blue', 'Scheduled'],
    ];
    [$cls, $label] = $map[$status] ?? ['nx-badge-gray', ucfirst($status)];
@endphp
<span class="nx-badge {{ $cls }}">{{ $label }}</span>
