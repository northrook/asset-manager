<?php

declare(strict_types=1);

namespace Core\AssetManager\Exception;

use InvalidArgumentException;
use Throwable;

final class UnresolvedAssetException extends InvalidArgumentException
{
    /**
     * @param string         $reference
     * @param string[]       $references
     * @param null|Throwable $previous
     */
    public function __construct(
        public readonly string $reference,
        public readonly array  $references,
        ?Throwable             $previous = null,
    ) {
        $message = "Asset '{$reference}' not found.";
        $message .= "\n\n".'Available: '.\implode( ', ', $references );

        parent::__construct( $message, 404, $previous );
    }
}
