<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Stringable;

final class AssetReference implements Stringable
{
    /** @var string `lower-case.dot.notated` */
    public readonly string $name;

    /** @var string `type.name` */
    public readonly string $reference;

    public function __construct(
        protected array $sources = [],
    ) {}

    public function __toString()
    {
        return $this->name;
    }
}
