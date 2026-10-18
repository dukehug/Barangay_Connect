<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmployeeRole: string implements HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Staff => 'Staff',
        };
    }
}
