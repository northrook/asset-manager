<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use Core\AssetManager\Config\AssetReference;
use InvalidArgumentException;
use Throwable;

final class MissingReferenceException extends InvalidArgumentException
{
    /**
     * @param AssetReference|string $reference
     * @param string[]              $references
     * @param null|Throwable        $previous
     */
    public function __construct(
        AssetReference|string $reference,
        array                 $references,
        ?Throwable            $previous = null,
    ) {
        $message = "AssetReference {$reference} not found.";
        $message .= "\n\n".'Available references: '.\implode( ', ', $references );

        parent::__construct( $message, 404, $previous );
    }
}
