<?php

namespace App\Services\Web\Admission;

use App\Models\Admission;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdmissionService
{
    /**
     * Admissions list with optional search.
     */
        public function paginate(
        ?string $search = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $search = trim((string) $search);

        return Admission::query()
            ->withCount('payments')
            ->when($search !== '', function ($query) use ($search) {
                // Grouped so later filters are not OR-ed with the search.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_filter(['search' => $search]));
    }

    /**
     * Create a new admission.
     */
    public function create(array $data): Admission
    {
        return Admission::create($this->fillableData($data));
    }

    /**
     * Load an admission with its payments for the show page.
     */
    public function getShowData(Admission $admission): Admission
    {
        return $admission->load([
            'payments' => function ($query) {
                $query->latest();
            },
        ]);
    }

    /**
     * Update an admission.
     */
    public function update(Admission $admission, array $data): Admission
    {
        $admission->update($this->fillableData($data));

        return $admission;
    }

    /**
     * Delete an admission.
     *
     * An admission that already has payments is kept for history;
     * deactivate it instead.
     *
     * @throws DomainException
     */
    public function delete(Admission $admission): void
    {
        if ($admission->payments()->exists()) {
            throw new DomainException(
                'This admission has payments and cannot be deleted. Deactivate it instead.'
            );
        }

        $admission->delete();
    }

    /**
     * Only the columns this service is allowed to write.
     */
    private function fillableData(array $data): array
    {
        return [
            'name' => $data['name'],
            'amount' => $data['amount'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'note' => $data['note'] ?? null,
        ];
    }
}