<?php

declare(strict_types=1);

namespace Core\Service\AssetManager\Compiler;

use Core\Service\AssetManager\Asset\Type;
use Psr\Log\LoggerInterface;
use Support\{FileInfo, Normalize};
use Traversable;
use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<string, AssetReference>
 */
final class AssetScanner implements IteratorAggregate
{
    protected const string SCAN_DIRECTORIES = '/**';

    protected const string SCAN_FILES = '/*.*';

    private readonly string $directoryPath;

    /** @var array<string, AssetReference> */
    private array $results = [];

    public function __construct(
        string                            $directoryPath,
        private readonly ?Type            $type = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->directoryPath = Normalize::path( $directoryPath );
        $this->parseScannedFileInfo(
            ...$this->directoryScan(),
        );
    }

    /**
     * @return ArrayIterator<string, AssetReference>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator( $this->results );
    }

    /**
     * @return array<string, AssetReference>
     */
    public function getResults() : array
    {
        return $this->results;
    }

    /**
     * @param null|self::SCAN_DIRECTORIES|self::SCAN_FILES|string $glob
     *
     * @return FileInfo[]
     */
    public function directoryScan( ?string $glob = null ) : array
    {
        $glob ??= $this->type ? $this::SCAN_DIRECTORIES : $this::SCAN_FILES;

        $scan = [];

        foreach ( \glob( $this->directoryPath.$glob ) ?: [] as $file ) {
            $reference = new FileInfo( $file );
            if ( ! $reference->getType() ) {
                $this->logger?->warning(
                    'Could not determine type for file: {file}.',
                    ['file' => $file],
                );

                continue;
            }

            $scan[] = $reference;
        }

        return $scan;
    }

    public function parseScannedFileInfo( FileInfo ...$fileInfo ) : self
    {
        foreach ( $fileInfo as $reference ) {
            if ( $reference->isDir() ) {
                $path = [];

                foreach ( \glob( "{$reference}/*.*" ) ?: [] as $subDirectory ) {
                    $segmentFileInfo = new FileInfo( $subDirectory );
                    if ( $this->type ) {
                        if ( Type::from( $segmentFileInfo->getExtension() ) !== $this->type ) {
                            $this->logger?->warning( 'Unexpected file type in asset directory.' );
                        }
                        else {
                            $path[] = $segmentFileInfo->getRealPath();
                        }
                    }
                    else {
                        $path[] = $segmentFileInfo->getRealPath();
                    }
                }
            }
            else {
                $path = [$reference->getRealPath()];
            }

            if ( ! $path ) {
                continue;
            }

            $name = $reference->getFilename();

            $this->results[$name] = new AssetReference( $name, $path );
        }

        return $this;
    }
}
