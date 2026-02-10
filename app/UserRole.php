<?php

namespace App;

enum UserRole: string
{
    case ADMIN = 'Admin';
    case CLIENT = 'Client';
}
