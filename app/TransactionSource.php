<?php

namespace App;

enum TransactionSource: string
{
    case Chat = 'chat';
    case Manual = 'manual';
}
