<?php

namespace App;

enum AiFunctionStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
