<?php

use Illuminate\Pagination\LengthAwarePaginator;

it('uses query-only page links so nested admin prefixes are never duplicated', function () {
    $paginator = new LengthAwarePaginator(
        collect(range(1, 10)),
        20,
        10,
        1,
        ['path' => 'admin/transaksi']
    );

    $html = view('vendor.pagination.table-footer', [
        'paginator' => $paginator,
        'elements' => [
            [1 => 'admin/transaksi?page=1', 2 => 'admin/transaksi?page=2'],
        ],
    ])->render();

    expect($html)
        ->toContain('href="?page=2"')
        ->not->toContain('admin/admin/transaksi')
        ->not->toContain('href="admin/transaksi?page=2"');
});

it('requires every paginated livewire table to use the integrated table footer', function () {
    $viewsPath = resource_path('views/livewire');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
    $violations = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        if (! str_contains($contents, '<table') || ! str_contains($contents, '->links(')) {
            continue;
        }

        if (preg_match('/->links\\(\\s*\\)/', $contents)
            || ! str_contains($contents, "links('vendor.pagination.table-footer')")) {
            $violations[] = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($violations)->toBe([]);
});
