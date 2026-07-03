<?php

namespace App\Enums;

enum UserStatus: string
{
    case VERIFYING = 'verifying';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';
    case O_ETERNO = 'o_eterno';
}
