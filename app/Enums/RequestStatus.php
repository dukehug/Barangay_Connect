<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case NeedsRevision = 'needs_revision';
    case AwaitingPayment = 'awaiting_payment';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Approved = 'approved';
    case Processing = 'processing';
    case AwaitingSignature = 'awaiting_signature';
    case Ready = 'ready';
    case Released = 'released';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::NeedsRevision => 'Needs revision',
            self::AwaitingPayment => 'Awaiting office payment',
            self::AwaitingConfirmation => 'Awaiting your confirmation',
            self::Approved => 'Approved',
            self::Processing => 'Ready to print',
            self::AwaitingSignature => 'Awaiting officer signature',
            self::Ready => 'Ready for pickup',
            self::Released => 'Released',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Confirmation expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending, self::NeedsRevision, self::AwaitingPayment, self::AwaitingConfirmation => 'warning',
            self::Processing, self::AwaitingSignature => 'info',
            self::Ready => 'primary',
            self::Approved, self::Released => 'success',
            self::Rejected => 'danger',
            self::Cancelled, self::Expired => 'gray',
        };
    }
}
