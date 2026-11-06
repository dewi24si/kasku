# Desain Baru Kasku — 3 Pilihan

Dokumen ini berisi 3 arah desain untuk mendesain ulang tampilan Kasku. **Belum ada kode yang diubah.**
Pilih satu (atau gabungan), nanti baru diterapkan ke `resources/css/app.css` dan view Blade/Livewire.

## Cara pilihan ini disusun

Disusun dengan skill **ui-ux-pro-max** (pencarian `--design-system`, `--domain style`, `color`, `typography`, `ux`).

Catatan jujur soal hasil skill: rekomendasi otomatis untuk "Personal Finance Tracker" adalah
**Glassmorphism + biru tua (#1E40AF) + font Caveat/Quicksand**. Itu persis yang kamu larang dan juga
tidak cocok (font tulisan tangan untuk angka uang sulit dibaca). Jadi hasil itu **tidak dipakai**.
Ketiga pilihan di bawah diambil dari gaya lain di database skill yang lolos syaratmu:

| Pilihan | Basis gaya dari skill | Basis font dari skill |
|---|---|---|
| A. Buku Kas | `minimalism-and-swiss-style` | "Friendly SaaS" (Plus Jakarta Sans) |
| B. Warung Hangat | `nature-distilled` + `organic-biophilic` | "Soft Rounded" (Nunito Sans) |
| C. Bento Tegas | `neubrutalism` + `bento-box-grid` | "Bauhaus Geometric" (Outfit) |

## Aturan yang berlaku untuk ketiganya

- **Tanpa** gradasi ungu/biru, **tanpa** glassmorphism/backdrop-blur, **tanpa** emoji sebagai ikon.
- **Hanya 1 warna aksen.** Merah (pengeluaran/hapus) dan kuning amber (peringatan budget) tetap ada,
  tapi diperlakukan sebagai **warna status**, bukan aksen. Dipakai seperlunya, tidak untuk dekorasi.
- **Warna kategori disatukan.** Sekarang tiap kategori punya warna sendiri (oranye, biru, pink, indigo,
  ungu, dst. di `CategorySeeder`). Di desain baru, ikon kategori memakai warna netral, dan grafik memakai
  gradasi terang-gelap dari warna aksen + abu-abu. Kolom `color` di database tetap ada, hanya tidak dipakai di UI.
- **Emoji dihapus** dari pesan AI dan view (±39 baris: `📋`, `📈`, dst.), diganti ikon Lucide di kartu atau teks biasa.
- Angka rupiah memakai `font-variant-numeric: tabular-nums` supaya digitnya sejajar di daftar.
- Kontras teks minimal 4.5:1 (light & dark), target sentuh minimal 44×44px, fokus keyboard terlihat,
  `prefers-reduced-motion` dihormati (dari checklist skill).
- Struktur tetap: mobile-first, lebar maks ~480px, navigasi bawah 4 menu, dark mode.

---

## Pilihan A — "Buku Kas"

**Konsep:** seperti buku kas / nota yang rapi. Tenang, serius, fokus ke angka.
Hampir tanpa bayangan; pemisahnya garis tipis. Angka besar menjadi elemen visual utama.

**Cocok kalau:** ingin terlihat matang dan profesional saat seminar, dan mudah dibaca dosen penguji.

### Warna

| Peran | Light | Dark |
|---|---|---|
| Latar (base-200) | `#F6F5F1` kertas | `#111315` |
| Kartu (base-100) | `#FFFFFF` | `#1A1D20` |
| Garis (base-300) | `#E4E2DB` | `#2A2E33` |
| Teks utama | `#16181D` tinta | `#ECEAE4` |
| Teks sekunder | `#5F6368` | `#9AA0A6` |
| **Aksen: Emerald tua** | `#0F7B5F` | `#3DD6A3` |
| Status pengeluaran | `#C0352B` | `#F08A80` |
| Status peringatan | `#B45309` | `#FBBF24` |

Kontras aksen di atas latar kertas: ±4.8:1 (lolos), teks putih di atas aksen: ±5.2:1 (lolos).

### Tipografi
- UI: **Plus Jakarta Sans** (sudah dipakai, tidak perlu ganti).
- Angka rupiah: **JetBrains Mono** 500, supaya terasa seperti nota.
- Saldo di dashboard: 40px / bold / letter-spacing rapat.

### Bentuk & efek
- Radius kecil: kartu 12px, tombol 10px (lebih tegas dari sekarang yang 20px).
- Tanpa bayangan; pakai border 1px. Hover hanya ganti warna latar (200ms).
- Daftar transaksi gaya buku kas: nama di kiri, garis titik-titik, nominal rata kanan.

### Komponen kunci
- **Navigasi bawah:** latar putih, garis atas 1px, menu aktif = ikon + label warna aksen + garis 2px di atas ikon.
- **Chat:** bubble user = latar tinta `#16181D` teks putih. Pesan AI **tanpa bubble**: teks polos dengan garis
  vertikal tipis di kiri (seperti catatan pinggir). Kartu hasil fungsi = kotak bergaris 1px.
- **Kartu transaksi di chat:** mirip struk; header kecil "DICATAT" huruf kapital mono, total di bawah garis putus-putus.

### Mockup

```
┌──────────────────────────────┐
│ Kasku              Nov 2026 ▾│
│                              │
│ SALDO BULAN INI              │
│ Rp1.245.000                  │  ← mono, 40px
│ ───────────────────────────  │
│ Masuk      Rp2.000.000       │
│ Keluar     Rp755.000         │  ← merah status
│                              │
│ TRANSAKSI                    │
│ ⌂ Makan siang ...... Rp25.000│
│ ⌂ Gojek ke kampus .. Rp18.000│
│ ⌂ Uang saku ....... +Rp500.000│ ← aksen emerald
├──────────────────────────────┤
│  Chat   Dashboard  Budget  Profil │
│          ▔▔▔▔                │
└──────────────────────────────┘
```

**Kelebihan:** paling mudah dibaca, paling dekat dengan kode sekarang (tema sekarang sudah monokrom),
risiko rusak paling kecil.
**Kekurangan:** kesannya bisa terlalu serius untuk mahasiswa.
**Besar perubahan:** kecil–sedang. Sebagian besar di `app.css` + kelas Tailwind di view.

---

## Pilihan B — "Warung Hangat"

**Konsep:** hangat dan ramah, seperti buku catatan di meja kayu. Latar krem, sudut sangat bulat,
bayangan lembut berwarna hangat. Mengurus uang terasa santai, tidak menakutkan.

**Cocok kalau:** ingin menonjolkan "teman keuangan mahasiswa" dan beda dari aplikasi bank yang dingin.

### Warna

| Peran | Light | Dark |
|---|---|---|
| Latar (base-200) | `#F5F0E6` krem | `#1B1915` |
| Kartu (base-100) | `#FFFCF6` | `#25221C` |
| Garis (base-300) | `#E6DDCB` | `#38332A` |
| Teks utama | `#2B2620` coklat tua | `#EEE7D8` |
| Teks sekunder | `#6E6457` | `#A89E8E` |
| **Aksen: Hijau zaitun** | `#4D6B3C` | `#A3C48E` |
| Status pengeluaran | `#B23A2E` bata | `#E8907F` |
| Status peringatan | `#A86A00` | `#F2C14E` |

Kontras aksen di atas krem: ±5.3:1 (lolos). Sengaja **tidak** memakai terakota sebagai aksen
(rekomendasi asli skill), karena terlalu mirip merah pengeluaran.

### Tipografi
- Judul: **Nunito Sans** 800 (bulat dan ramah, tapi tetap jelas untuk angka).
- Isi: **Nunito Sans** 400–600.
- Saldo: 36px / extra-bold.
- *Butuh ganti font* (sekarang Plus Jakarta Sans), artinya ubah link Google Fonts di layout.

### Bentuk & efek
- Radius besar: kartu 24px, tombol & input bentuk pil (full).
- Bayangan lembut hangat: `0 6px 20px rgb(77 60 30 / 0.08)`. Tanpa blur latar.
- Ikon kategori dalam lingkaran krem gelap (`#EDE4D3`) dengan ikon coklat, bukan warna-warni.
- Animasi masuk pelan (ease-out 250ms), tombol sedikit mengecil saat ditekan (scale 0.97).

### Komponen kunci
- **Navigasi bawah:** "pil" mengambang dengan jarak 12px dari tepi bawah, menu aktif = pil kecil hijau
  zaitun berisi ikon + label.
- **Chat:** bubble user hijau zaitun teks putih, bubble AI krem gelap `#EFE7D7`. Ujung bubble bulat semua kecuali satu sudut.
- **Kartu hasil fungsi:** kartu putih-krem dengan header ikon Lucide dalam lingkaran zaitun muda.
- **Empty state:** ilustrasi garis sederhana (SVG satu warna), bukan emoji.

### Mockup

```
┌──────────────────────────────┐
│  Halo, Dewi                  │
│  ╭────────────────────────╮  │
│  │ Saldo bulan ini        │  │
│  │ Rp1.245.000            │  │
│  │ ↑ Rp2,0 jt  ↓ Rp755 rb │  │
│  ╰────────────────────────╯  │
│                              │
│  ╭───╮ Makan siang  -Rp25.000│
│  │ ⌂ │ Hari ini · Makan       │
│  ╰───╯                       │
│  ╭───╮ Uang saku   +Rp500.000│
│  │ ⌂ │ Kemarin · Pemasukan   │
│  ╰───╯                       │
│                              │
│   ╭──────────────────────╮   │
│   │ (Chat) Dash Budget Profil │ ← pil mengambang
│   ╰──────────────────────╯   │
└──────────────────────────────┘
```

**Kelebihan:** paling ramah dan berkarakter, terasa "dibuat untuk mahasiswa".
**Kekurangan:** dark mode versi hangat perlu dicek kontrasnya lebih teliti (skill menandai dark mode
gaya ini "conditional"); perlu ganti font.
**Besar perubahan:** sedang. `app.css`, layout (font + navigasi), semua kartu chat.

---

## Pilihan C — "Bento Tegas"

**Konsep:** berani dan muda. Garis hitam tebal, bayangan keras tanpa blur (offset 4px),
dashboard disusun seperti kotak bento dengan ukuran berbeda. Satu warna aksen lime yang mencolok.

**Cocok kalau:** ingin paling beda dan paling diingat saat demo; target pengguna Gen Z.

### Warna

| Peran | Light | Dark |
|---|---|---|
| Latar (base-200) | `#F4F4F0` | `#0E0E0E` |
| Kartu (base-100) | `#FFFFFF` | `#181818` |
| Garis tebal | `#111111` | `#E8E8E8` |
| Teks utama | `#111111` | `#F2F2F2` |
| Teks sekunder | `#555555` | `#A3A3A3` |
| **Aksen: Lime** | `#C6F432` (sebagai latar) | `#C6F432` |
| Teks di atas aksen | `#111111` | `#111111` |
| Status pengeluaran | `#D7263D` | `#FF6B7A` |
| Status peringatan | `#B45309` | `#FBBF24` |

Aturan penting: lime **hanya dipakai sebagai latar** dengan teks hitam (kontras ±14:1).
Teks lime di atas putih **dilarang** karena tidak terbaca.

### Tipografi
- **Outfit** untuk semuanya: 900 untuk saldo & judul, 700 kapital untuk tombol/label nav, 500 untuk isi.
- Saldo: 44px / 900 / tracking-tight.
- *Butuh ganti font.*

### Bentuk & efek
- Border 2px hitam di kartu, tombol, input. Radius sedang 14px (tidak 0px seperti brutalism murni,
  supaya tetap ramah di HP).
- Bayangan keras: `4px 4px 0 #111`. Saat ditekan, kartu "turun" (translate 2px, bayangan 2px).
- Dashboard = grid bento 2 kolom: kotak saldo 2×1 (latar lime), kotak masuk/keluar 1×1, grafik 2×1.

### Komponen kunci
- **Navigasi bawah:** batang putih border atas 2px hitam; menu aktif = kotak lime berisi ikon + label kapital.
- **Chat:** bubble user hitam teks putih, bubble AI putih dengan border 2px. Kartu hasil fungsi pakai
  bayangan keras dan header strip lime tipis.
- **Konfirmasi hapus:** tombol "Ya, hapus" merah border hitam; "Batal" putih border hitam.

### Mockup

```
┌──────────────────────────────┐
│ KASKU                 NOV 26 │
│ ┏━━━━━━━━━━━━━━━━━━━━━━━━━━┓ │
│ ┃ SALDO        (latar lime)┃▌│
│ ┃ Rp1.245.000              ┃▌│
│ ┗━━━━━━━━━━━━━━━━━━━━━━━━━━┛▌│
│ ┏━━━━━━━━━━━┓ ┏━━━━━━━━━━━━┓ │
│ ┃ MASUK     ┃▌┃ KELUAR     ┃▌│
│ ┃ Rp2,0 jt  ┃▌┃ Rp755 rb   ┃▌│
│ ┗━━━━━━━━━━━┛▌┗━━━━━━━━━━━━┛▌│
│ ┏━━━━━━━━━━━━━━━━━━━━━━━━━━┓ │
│ ┃ ▁▃▅▂▇▃▁  grafik minggu   ┃▌│
│ ┗━━━━━━━━━━━━━━━━━━━━━━━━━━┛▌│
├━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┤
│ CHAT [DASHBOARD] BUDGET PROFIL│
└──────────────────────────────┘
```

**Kelebihan:** paling khas dan berani, kuat untuk demo dan screenshot laporan.
**Kekurangan:** aksennya lime, bukan emerald, jadi **menyimpang dari CLAUDE.md** ("warna utama hijau
emerald") dan CLAUDE.md perlu diperbarui. Kesannya ramai jika tidak disiplin. Perubahan paling besar.
**Besar perubahan:** besar. `app.css`, layout, dashboard (jadi grid bento), semua kartu chat & form.

---

## Ringkasan perbandingan

| | A. Buku Kas | B. Warung Hangat | C. Bento Tegas |
|---|---|---|---|
| Kesan | Rapi, serius | Ramah, hangat | Berani, muda |
| Aksen | Emerald tua `#0F7B5F` | Zaitun `#4D6B3C` | Lime `#C6F432` |
| Font | Plus Jakarta Sans + JetBrains Mono | Nunito Sans | Outfit |
| Sudut | 12px | 24px / pil | 14px + border tebal |
| Bayangan | Tidak ada | Lembut hangat | Keras offset |
| Sesuai CLAUDE.md | Ya (emerald) | Hampir (hijau) | Tidak (lime), perlu update |
| Besar perubahan | Kecil–sedang | Sedang | Besar |
| Risiko kontras dark mode | Rendah | Sedang | Rendah |

**Rekomendasi:** **A** kalau prioritasnya aman dan rapi untuk seminar; **B** kalau ingin karakter
yang paling pas dengan target mahasiswa. C paling mencolok, tapi butuh kerja paling banyak dan mengubah warna utama.

Tinggal sebut pilihannya (misalnya "A", atau "A tapi navigasinya pakai pil dari B").
