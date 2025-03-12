<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use InvalidArgumentException;
use Throwable;

final class InvalidReferenceException extends InvalidArgumentException
{
    /**
     * @param string         $reference
     * @param null|Throwable $previous
     */
    public function __construct(
        string     $reference,
        ?Throwable $previous = null,
    ) {
        $message = "The string '{$reference}' is not a valid reference name.'";
        $message .= "\n\nAssetReference must only contain ASCII letters, numbers, periods, and hyphens.";

        parent::__construct( $message, 500, $previous );
    }
}
