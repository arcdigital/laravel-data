<?php

namespace Spatie\LaravelData\Concerns;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Enumerable;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\DataPipeline;
use Spatie\LaravelData\DataPipes\AuthorizedDataPipe;
use Spatie\LaravelData\DataPipes\CastPropertiesDataPipe;
use Spatie\LaravelData\DataPipes\DefaultValuesDataPipe;
use Spatie\LaravelData\DataPipes\MapPropertiesDataPipe;
use Spatie\LaravelData\DataPipes\ValidatePropertiesDataPipe;
use Spatie\LaravelData\Normalizers\ArraybleNormalizer;
use Spatie\LaravelData\Normalizers\ArrayNormalizer;
use Spatie\LaravelData\Normalizers\ModelNormalizer;
use Spatie\LaravelData\Normalizers\ObjectNormalizer;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\LaravelData\Resolvers\DataFromSomethingResolver;
use Spatie\LaravelData\Resolvers\EmptyDataResolver;
use Spatie\LaravelData\Support\Wrapping\WrapExecutionType;
use Spatie\LaravelData\Transformers\DataTransformer;

trait BaseData
{
    protected static string $collectionClass = DataCollection::class;

    protected static string $paginatedCollectionClass = PaginatedDataCollection::class;

    protected static string $cursorPaginatedCollectionClass = CursorPaginatedDataCollection::class;

    public static function optional(mixed ...$payloads): ?static
    {
        if (count($payloads) === 0) {
            return null;
        }

        foreach ($payloads as $payload) {
            if ($payload !== null) {
                return static::from(...$payloads);
            }
        }

        return null;
    }

    public static function from(mixed ...$payloads): static
    {
        return app(DataFromSomethingResolver::class)->execute(
            static::class,
            ...$payloads
        );
    }

    public static function normalizers(): array
    {
        return [
            ModelNormalizer::class,
            ArraybleNormalizer::class,
            ObjectNormalizer::class,
            ArrayNormalizer::class,
        ];
    }

    public static function pipeline(): DataPipeline
    {
        return DataPipeline::create()
            ->into(static::class)
            ->through(AuthorizedDataPipe::class)
            ->through(MapPropertiesDataPipe::class)
            ->through(ValidatePropertiesDataPipe::class)
            ->through(DefaultValuesDataPipe::class)
            ->through(CastPropertiesDataPipe::class);
    }

    public static function collection(Enumerable|array|AbstractPaginator|Paginator|AbstractCursorPaginator|CursorPaginator|DataCollection $items): DataCollection|CursorPaginatedDataCollection|PaginatedDataCollection
    {
        if ($items instanceof Paginator || $items instanceof AbstractPaginator) {
            return new (static::$paginatedCollectionClass)(static::class, $items);
        }

        if ($items instanceof AbstractCursorPaginator || $items instanceof CursorPaginator) {
            return new (static::$cursorPaginatedCollectionClass)(static::class, $items);
        }

        return new (static::$collectionClass)(static::class, $items);
    }

    public static function empty(array $extra = []): array
    {
        return app(EmptyDataResolver::class)->execute(static::class, $extra);
    }

    public static function createByContainerInjection(): bool
    {
        return true;
    }

    public function transform(
        bool $transformValues = true,
        WrapExecutionType $wrapExecutionType = WrapExecutionType::Disabled,
    ): array {
        return DataTransformer::create($transformValues, $wrapExecutionType)->transform($this);
    }
}
