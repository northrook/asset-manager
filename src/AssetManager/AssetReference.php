<?php

declare(strict_types=1);

namespace Core\AssetManager;

use Core\Asset\Type;
use Core\Interface\DataObject;
use Stringable;

final readonly class AssetReference extends DataObject implements Stringable
{
    /** @var string `type.name` */
    public string $name;

    public Type $type;

    /** @var string[] */
    public array $source;

    /**
     * @param string          $name
     * @param string|string[] $sources
     * @param Type            $type
     */
    public function __construct(
        string $name,
        array  $sources,
    ) {
        $sources = (array) $sources;
        \ksort( $sources );
        $this->source = $sources;
        \assert(
            \ctype_alpha( \str_replace( ['.', '-'], '', $name ) ),
            "Asset names must only contain ASCII characters, underscores and dashes. {$name} provided.",
        );

        $this->name = $name;
        $this->type = Type::from( $name, true );
    }

    public function __toString()
    {
        return $this->name;
    }
}
