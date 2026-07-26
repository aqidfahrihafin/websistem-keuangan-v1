<?php

namespace App\Livewire\Concerns;

/**
 * Pairs with Livewire\WithPagination on any component that lists data in a
 * table - $perPage feeds straight into ->paginate($this->perPage) in
 * render(). Standardized to default 10 with the same options everywhere,
 * even on pages that used to hardcode a different size (15/20), so the
 * control behaves identically across the whole app.
 */
trait WithPerPage
{
    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }
}
