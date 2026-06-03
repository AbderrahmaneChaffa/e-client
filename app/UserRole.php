<?php

namespace App;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case SUPERADMIN = 'superadmin';

    public function hasAdminAccess(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPERADMIN], true);
    }
}
