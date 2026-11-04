<?php

namespace App;

enum TransactionType: string
{
    case Pemasukan = 'pemasukan';
    case Pengeluaran = 'pengeluaran';
}
