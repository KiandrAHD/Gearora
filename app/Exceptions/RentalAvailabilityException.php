<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika unit fisik yang diminta tidak cukup pada periode yang dipilih.
 */
class RentalAvailabilityException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $variantName = null,
        public readonly int $requested = 0,
        public readonly int $available = 0,
    ) {
        parent::__construct($message);
    }
}
