<?php

namespace App\Http\Constants;

class ValidationMessages
{
    public const FIELD_REQUIRED = 'The %s field is required.';
    public const FIELD_EMAIL = 'The %s field must be a valid email address.';
    public const FIELD_MATCH = 'The %s field must match the %s field.';

    public const  SYNC_PAYMENT = "Pembayaran Uang Kost Bulanan";
}
