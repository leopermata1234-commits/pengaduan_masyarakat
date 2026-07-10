<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->redirectPath($request));
    }

    private function redirectPath($request): string
    {
        return $request->user()?->hasRole('Masyarakat')
            ? route('beranda', absolute: false)
            : route('dashboard', absolute: false);
    }
}
