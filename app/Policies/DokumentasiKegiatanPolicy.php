<?php

namespace App\Policies;

use App\Models\DokumentasiKegiatan;
use App\Models\User;

class DokumentasiKegiatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dokumentasi.view');
    }

    public function view(?User $user, DokumentasiKegiatan $dokumentasiKegiatan): bool
    {
        if (! $user) {
            return $dokumentasiKegiatan->status === DokumentasiKegiatan::STATUS_PUBLISHED;
        }

        if (! $user->can('dokumentasi.view')) {
            return false;
        }

        return ! $user->hasRole('Masyarakat') || $dokumentasiKegiatan->status === DokumentasiKegiatan::STATUS_PUBLISHED;
    }

    public function create(User $user): bool
    {
        return $user->can('dokumentasi.create');
    }

    public function update(User $user, DokumentasiKegiatan $dokumentasiKegiatan): bool
    {
        return $user->can('dokumentasi.edit');
    }

    public function delete(User $user, DokumentasiKegiatan $dokumentasiKegiatan): bool
    {
        return $user->can('dokumentasi.delete');
    }
}
