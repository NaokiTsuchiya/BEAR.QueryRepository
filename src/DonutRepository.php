<?php

declare(strict_types=1);

namespace BEAR\QueryRepository;

use BEAR\Resource\AbstractUri;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

use function assert;

final class DonutRepository
{
    /** @var ResourceStorageInterface */
    private $resourceStorage;

    /** @var HeaderSetter */
    private $headerSetter;

    /** @var ResourceInterface */
    private $resource;

    /** @var QueryRepository */
    private $queryRepository;

    /** @var CdnCacheControlHeaderSetterInterface */
    private $cacheControlHeaderSetter;

    public function __construct(
        QueryRepository $queryRepository,
        HeaderSetter $headerSetter,
        ResourceStorageInterface $resourceStorage,
        ResourceInterface $resource,
        CdnCacheControlHeaderSetterInterface $cacheControlHeaderSetter
    ) {
        $this->resourceStorage = $resourceStorage;
        $this->headerSetter = $headerSetter;
        $this->resource = $resource;
        $this->queryRepository = $queryRepository;
        $this->cacheControlHeaderSetter = $cacheControlHeaderSetter;
    }

    public function get(ResourceObject $ro): ?ResourceObject
    {
        $maybeState = $this->queryRepository->get($ro->uri);
        if ($maybeState instanceof ResourceState) {
            $ro->headers = $maybeState->headers;
            $ro->view = $maybeState->view;

            return $ro;
        }

        return $this->refreshDonut($ro);
    }

    public function createDonut(ResourceObject $ro, ?int $sMaxAge): ResourceObject
    {
        $renderer = new DonutRenderer();
        $etags = new Etags();
        ($this->cacheControlHeaderSetter)($ro, $sMaxAge);
        $donut = ResourceDonut::create($ro, $renderer, $etags, $sMaxAge);
        $this->resourceStorage->saveDonut($ro->uri, $donut, $sMaxAge);

        $donut->render($ro, $renderer);
        $etags->setSurrogateKey($ro);
        ($this->headerSetter)($ro, 0, null);
        ($this->cacheControlHeaderSetter)($ro, $donut->ttl);
        $this->saveView($ro, $sMaxAge);

        return $ro;
    }

    public function refresh(AbstractUri $uri): void
    {
        $this->purge($uri);
        $this->resource->get((string) $uri);
    }

    private function purge(AbstractUri $uri): void
    {
        $this->queryRepository->purge($uri);
        $this->resourceStorage->deleteDonut($uri);
    }

    private function refreshDonut(ResourceObject $ro): ?ResourceObject
    {
        $donut = $this->resourceStorage->getDonut($ro->uri);
        if (! $donut instanceof ResourceDonut) {
            return null;
        }

        $donut->refresh($this->resource, $ro);
        ($this->headerSetter)($ro, $donut->ttl, null);
        $ro->headers['ETag'] .= 'r'; // mark refreshed by resource static
        ($this->cacheControlHeaderSetter)($ro, $donut->ttl);
        $this->saveView($ro, $donut->ttl);

        return $ro;
    }

    private function saveView(ResourceObject $ro, ?int $ttl): bool
    {
        assert(isset($ro->headers['ETag']));
        $this->resourceStorage->updateEtag($ro->uri, $ro->headers['ETag'], $ttl);

        return $this->resourceStorage->saveDonutView($ro, $ttl);
    }
}
