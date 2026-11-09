{{--
    Email atur ulang kata sandi. Memakai x-mail::layout langsung (bukan x-mail::message) supaya footer dan teks
    cadangan link berbahasa Indonesia, tanpa teks bawaan Laravel yang berbahasa Inggris.
--}}
<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
Kasku
</x-mail::header>
</x-slot:header>

# Halo, {{ $name }}

Kami menerima permintaan untuk mengatur ulang kata sandi akun Kasku kamu. Ketuk tombol di bawah untuk membuat kata sandi baru.

<x-mail::button :url="$url" color="primary">
Atur Ulang Kata Sandi
</x-mail::button>

Link ini berlaku selama {{ $expireMinutes }} menit. Kalau kamu tidak meminta reset kata sandi, abaikan email ini. Kata sandimu tidak akan berubah.

Salam,<br>
Tim Kasku

<x-slot:subcopy>
<x-mail::subcopy>
Kalau tombol di atas tidak bisa diketuk, salin dan buka link ini di browser: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-mail::subcopy>
</x-slot:subcopy>

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} Kasku. Catat keuangan cukup lewat chat.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
