<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Pesan yang sama untuk email terdaftar maupun tidak, supaya tidak bisa dipakai menebak email yang terdaftar.
     */
    public const GENERIC_STATUS = 'Kalau email itu terdaftar, link reset sudah kami kirim. Cek inbox atau folder spam.';

    /**
     * Display the forgot password view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link when the email belongs to a registered user.
     *
     * Status broker (terkirim, tidak terdaftar, atau dibatasi) sengaja tidak dibedakan ke pengguna. Pengiriman
     * email ditunda sampai respons terkirim supaya lama respons juga tidak membocorkan email yang terdaftar.
     */
    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        defer(fn () => Password::sendResetLink(['email' => $email]));

        return back()->with('status', self::GENERIC_STATUS);
    }
}
