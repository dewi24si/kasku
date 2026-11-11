<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('kasku:pindahkan-foto-struk')]
#[Description('Pindahkan foto struk lama dari disk public ke disk privat dan perbarui lampiran chat. Aman dijalankan ulang.')]
class PindahkanFotoStruk extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk(Transaction::RECEIPT_DISK);

        $moved = 0;
        $missing = 0;
        $alreadyPrivate = 0;
        $linkedTransactions = 0;

        ChatMessage::query()
            ->where('attachment->type', 'receipt_photo')
            ->chunkById(100, function ($messages) use ($publicDisk, $privateDisk, &$moved, &$missing, &$alreadyPrivate, &$linkedTransactions): void {
                foreach ($messages as $message) {
                    $attachment = $message->attachment;
                    $path = $attachment['path'] ?? $this->pathFromLegacyUrl($attachment['url'] ?? null, $message->user_id);

                    if ($path === null) {
                        $this->warn("Pesan #{$message->id}: path foto tidak dikenali, dilewati.");

                        continue;
                    }

                    if (isset($attachment['url'])) {
                        if (! $privateDisk->exists($path) && $publicDisk->exists($path)) {
                            $privateDisk->writeStream($path, $publicDisk->readStream($path));
                        }

                        if ($privateDisk->exists($path)) {
                            $publicDisk->delete($path);
                            $moved++;
                        } else {
                            $missing++;
                            $this->warn("Pesan #{$message->id}: file {$path} tidak ditemukan.");
                        }

                        unset($attachment['url']);
                        $attachment['path'] = $path;
                        $message->update(['attachment' => $attachment]);
                    } else {
                        $alreadyPrivate++;
                    }

                    $linkedTransactions += Transaction::query()
                        ->where('user_id', $message->user_id)
                        ->where('chat_message_id', $message->id)
                        ->whereNull('receipt_path')
                        ->update(['receipt_path' => $path]);
                }
            });

        $this->info("{$moved} foto dipindahkan, {$alreadyPrivate} sudah di disk privat, {$missing} file tidak ditemukan, {$linkedTransactions} transaksi ditautkan ke fotonya.");

        return self::SUCCESS;
    }

    /**
     * Ambil path relatif (receipts/{user_id}/file.jpg) dari URL lama, hanya kalau foldernya milik pengirim pesan, seperti http://kasku.test/storage/receipts/1/file.jpg.
     */
    private function pathFromLegacyUrl(?string $url, int $userId): ?string
    {
        if (blank($url) || ! Str::contains($url, '/storage/')) {
            return null;
        }

        $path = Str::after(parse_url($url, PHP_URL_PATH) ?? '', '/storage/');

        if (! Str::startsWith($path, "receipts/{$userId}/") || Str::contains($path, '..')) {
            return null;
        }

        return $path;
    }
}
