<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Compiler;

use IteratorAggregate;
use Support\Interface\DataObject;
use Countable;
use Traversable;
use ArrayIterator;

/**
 * @implements IteratorAggregate<int, string>
 */
final readonly class AssetReference extends DataObject implements Countable, IteratorAggregate
{
    /** @var string[] */
    public array $source;

    /**
     * @param string   $name
     * @param string[] $source
     */
    public function __construct(
        public string $name,
        string|array  $source,
    ) {
        $this->source = \is_string( $source ) ? [$source] : $source;
    }

    public function count() : int
    {
        return \count( $this->source );
    }

    /**
     * @return ArrayIterator<int, string>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator( $this->source );
    }
}
