<?php

namespace App\Exceptions;

use RuntimeException;

class GalleryTokenExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Gallery API token has expired or is invalid.');
    }
}
