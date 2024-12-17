<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\PathfinderInterface;
use Core\Service\AssetManager;
use Core\Service\AssetManager\Asset\{AssetModelInterface, AssetReference, Type};
use Core\SettingsInterface;
use Support\Attribute\EntryPoint;
use Support\{FileInfo, Normalize};
use function String\hashKey;
use const Support\AUTO;

abstract class AssetModel implements AssetModelInterface
{
    /** @var string `16` character alphanumeric */
    private readonly string $assetID;

    protected readonly FileInfo $publicPath;

    protected readonly string $publicUrl;

    final private function __construct(
        private readonly AssetReference        $reference,
        protected readonly PathfinderInterface $pathfinder,
    ) {
        $this->publicPath = $pathfinder->getFileInfo( "dir.assets.public/{$reference->publicUrl}" );
        \assert( $this->publicPath instanceof FileInfo );
        $this->publicUrl = Normalize::url( $this->pathfinder->get( $this->publicPath, 'dir.public' ) );
    }

    public function build( ?string $assetID = null, ?SettingsInterface $settings = null ) : AssetModelInterface
    {
        $this->setAssetID( $assetID );
        return $this;
    }

    #[EntryPoint( [AssetManager::class, 'getAssetModel'] )]
    final public static function fromReference(
        AssetReference      $reference,
        PathfinderInterface $pathfinder,
    ) : self {
        return new static( $reference, $pathfinder );
    }

    public function version() : string
    {
        $modified = $this->publicPath->getMTime() ?: $this->assetID();
        return "?v={$modified}";
    }

    /**
     * @return string `lower-case.dot.notated`
     */
    final public function getName() : string
    {
        return $this->reference->name;
    }

    final public function getType() : Type
    {
        return $this->reference->type;
    }

    final public function getPublicUrl() : string
    {
        return $this->publicUrl;
    }

    final public function getPublicPath( bool $relative = false ) : string
    {
        return $relative
                ? $this->pathfinder->get( (string) $this->publicPath, 'dir.public', true )
                : (string) $this->publicPath;
    }

    final public function getReference() : AssetReference
    {
        return $this->reference;
    }

    final public function getSources() : array
    {
        return (array) $this->reference->source;
    }

    final protected function assetID() : string
    {
        return $this->setAssetID( AUTO );
    }

    /**
     * @param null|string $assetID
     *
     * @return string `16` character alphanumeric
     */
    final protected function setAssetID( ?string $assetID ) : string
    {
        $this->assetID ??= $assetID ?? hashKey(
            [
                $this::class,
                $this->reference->name,
                $this->reference->type->name,
                ...(array) $this->reference->source,
            ],
            'implode',
        );

        \assert(
            \strlen( $this->assetID ) === 16 && \ctype_alnum( $this->assetID ),
            'Asset ID must be 16 alphanumeric characters; ['.\strlen(
                $this->assetID,
            )."] `{$this->assetID}` given",
        );

        return $this->assetID;
    }
}
