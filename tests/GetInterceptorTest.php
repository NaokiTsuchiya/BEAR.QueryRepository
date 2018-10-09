<?php
/**
 * This file is part of the BEAR.QueryRepository package.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace BEAR\QueryRepository;

use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class GetInterceptorTest extends TestCase
{
    /**
     * @var ResourceInterface
     */
    private $resource;

    public function setUp()
    {
        $this->resource = (new Injector(new QueryRepositoryModule(new ResourceModule('FakeVendor\HelloWorld'))))->getInstance(ResourceInterface::class);
        parent::setUp();
    }

    public function testLastModifiedHeader()
    {
        $user = $this->resource->get->uri('app://self/user')->withQuery(['id' => 1])->eager->request();
        // put
        $expect = 'Last-Modified';
        $this->assertArrayHasKey($expect, $user->headers);
        $time = $user['time'];
        // get
        $user = $this->resource->get->uri('app://self/user')->withQuery(['id' => 1])->eager->request();
        $this->assertArrayHasKey($expect, $user->headers);
        $expect = $time;
        $this->assertSame($expect, $user['time']);
    }

    public function testCacheControlHeaderNone()
    {
        $user = $this->resource->get->uri('app://self/control-none')->eager->request();
        $this->assertArrayHasKey('Cache-Control', $user->headers);
        $this->assertSame('max-age=60', $user->headers['Cache-Control']);
    }

    public function testCacheControlHeaderExpiry()
    {
        $user = $this->resource->get->uri('app://self/control-expiry')->eager->request();
        $this->assertArrayHasKey('Cache-Control', $user->headers);
        $this->assertSame('public, max-age=30', $user->headers['Cache-Control']);
    }
}
