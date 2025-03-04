<?php

namespace App\Http\Constants;

class ErrorMessages
{
    public const EMAIL_ALREADY_EXISTS = 'Email already exists!';
    public const FAILED_CREATE_MODEL = 'Failed to create %s!';
    public const FAILED_SYNC_MODEL = 'Failed to synchronized %s!';
    public const INVALID_CREDENTIALS = 'Invalid email or password!';
    public const INVALID_ROLE_ACCESS = 'User access not admin!';
    public const INVALID_GALLERY_TYPE_VIDEO = 'Video URL is required for Video type.';
    public const INVALID_GALLERY_TYPE_IMAGE = 'File is required for Foto type.';

    public const UNAUTHORIZED_ACCESS = 'Unauthorized access';
    public const UNAUTHORIZED_API_KEY_ACCESS = 'Unauthorized. Invalid API Key.';
    public const TOKEN_EXPIRED = 'Token has expired';
    public const TOKEN_INVALID = 'Token is invalid';
    public const TOKEN_MISSING = 'Token is missing';
    public const TOKEN_INVALID_MISSING = 'Invalid or missing token';
    public const TOKEN_FAILED_VERIFIED = 'Failed to verify token, please try again';

    public const MESSAGE_NOT_FOUND = '%s not found';

    public const MESSAGE_CANT_SYNC = 'No %s to sync!';
}
