<?php

namespace App\Observers;

use App\Models\User;
use App\Services\KeluargaLinkingService;

class UserObserver
{
    public function __construct(private KeluargaLinkingService $linking) {}

    public function created(User $user): void
    {
        $this->linking->syncForUser($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('no_kk')) {
            $this->linking->syncForUser($user, $user->getOriginal('no_kk'));
        }
    }
}
