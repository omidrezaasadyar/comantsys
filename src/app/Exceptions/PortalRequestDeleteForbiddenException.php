<?php

namespace App\Exceptions;

use Exception;

class PortalRequestDeleteForbiddenException extends Exception
{
    public function __construct(
        string $message = 'حذف درخواست پورتال فقط توسط مدیر ارشد (super_admin) امکان‌پذیر است.',
    ) {
        parent::__construct($message);
    }
}
