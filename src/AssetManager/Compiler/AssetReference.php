<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Compiler;

use Core\Service\AssetManager\Asset\Type;
use IteratorAggregate;
use Support\{FileInfo, Normalize};
use Support\Interface\DataObject;
use Countable;
use Traversable;
use ArrayIterator;
use Stringable;

/**
 * @implements IteratorAggregate<int, string>
 */
final readonly class AssetReference extends DataObject implements Countable, IteratorAggregate
{
    public string $name;

    public FileInfo $path;

    /** @var string[] */
    public array $source;

    /**
     * @param string|Stringable $name
     * @param string|Stringable $path
     * @param string[]          $source
     * @param Type              $type
     */
    public function __construct(
        string|Stringable       $name,
        string|Stringable       $path,
        string|Stringable|array $source,
        public Type             $type,
    ) {
        $this->setName( $name );
        $this->setPath( $path );
        $this->setSource( $source );
    }

    private function setName( string|Stringable $name ) : void
    {
        $name = (string) $name;
        if ( ! \str_starts_with( $name, $this->type->name ) ) {
            $name = "{$this->type->name}.{$name}";
        }
        $this->name = Normalize::key( $name, '.' );
    }

    private function setPath( string|Stringable $path ) : void
    {
        $this->path = new FileInfo( $path );
    }

    /**
     * @param string|string[]|Stringable $source
     *
     * @return void
     */
    private function setSource( string|Stringable|array $source ) : void
    {
        $this->source = \is_array( $source ) ? $source : [(string) $source];
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
