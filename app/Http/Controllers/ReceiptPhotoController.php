<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Transaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptPhotoController extends Controller
{
    /**
     * Foto struk milik sebuah transaksi. Hanya pemilik transaksi yang boleh membukanya.
     */
    public function transaction(Transaction $transaction): StreamedResponse
    {
        Gate::authorize('view', $transaction);

        return $this->photoResponse($transaction->receipt_path);
    }

    /**
     * Foto struk yang dilampirkan di pesan chat. Hanya pengirim pesan yang boleh membukanya.
     */
    public function chatMessage(ChatMessage $chatMessage): StreamedResponse
    {
        Gate::authorize('view', $chatMessage);

        $attachment = $chatMessage->attachment ?? [];

        abort_unless(($attachment['type'] ?? null) === 'receipt_photo', 404);

        return $this->photoResponse($attachment['path'] ?? null);
    }

    private function photoResponse(?string $path): StreamedResponse
    {
        $disk = Storage::disk(Transaction::RECEIPT_DISK);

        abort_if(blank($path) || ! $disk->exists($path), 404);

        return $disk->response($path, headers: [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
