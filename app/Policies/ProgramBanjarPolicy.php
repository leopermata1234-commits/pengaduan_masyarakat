<?php

namespace App\Policies;

use App\Models\ProgramBanjar;
use App\Models\User;

class ProgramBanjarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('program.view');
    }

    public function view(?User $user, ProgramBanjar $programBanjar): bool
    {
        if (! $user) {
            return in_array($programBanjar->status, ProgramBanjar::PUBLIC_STATUSES, true);
        }

        if (! $user->can('program.view')) {
            return false;
        }

        return ! $user->hasRole('Masyarakat')
            || in_array($programBanjar->status, ProgramBanjar::PUBLIC_STATUSES, true);
    }

    public function create(User $user): bool
    {
        return $user->can('program.create');
    }

    public function update(User $user, ProgramBanjar $programBanjar): bool
    {
        return $user->can('program.edit');
    }

    public function delete(User $user, ProgramBanjar $programBanjar): bool
    {
        return $user->can('program.delete');
    }
}
