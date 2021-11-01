<?php

declare(strict_types=1);

namespace BEAR\RepositoryModule\Annotation;

use Attribute;
use BEAR\QueryRepository\DonutCacheableResponseInterceptor;
use BEAR\QueryRepository\DonutCacheModule;
use BEAR\QueryRepository\DonutCommandInterceptor;

/**
 * @Annotation
 * @Target({"METHOD","CLASS"})
 *
 * @see DonutCacheModule
 * @see DonutCacheableResponseInterceptor
 * @see DonutCommandInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_CLASS)]
final class CacheableResponse
{
}
