<?php

namespace App\Policies;

use App\Models\Pengaduan;
use App\Models\User;

class PengaduanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pengaduan.view');
    }

    public function view(User $user, Pengaduan $pengaduan): bool
    {
        if (! $user->can('pengaduan.view')) {
            return false;
        }

        return ! $user->hasRole('Masyarakat')
            || $pengaduan->user_id === $user->id
            || $pengaduan->visibilitas === Pengaduan::VISIBILITAS_PUBLIK;
    }

    public function create(User $user): bool
    {
        return $user->can('pengaduan.create');
    }

    public function update(User $user, Pengaduan $pengaduan): bool
    {
        return $user->can('pengaduan.edit');
    }

    public function delete(User $user, Pengaduan $pengaduan): bool
    {
        return $user->can('pengaduan.delete');
    }

    public function respond(User $user, Pengaduan $pengaduan): bool
    {
        return $user->can('pengaduan.respond');
    }

    public function verify(User $user, Pengaduan $pengaduan): bool
    {
        return $user->can('pengaduan.verify');
    }
}
