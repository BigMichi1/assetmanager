<?php
namespace AssetManagerTest\Service;

use AssetManager\Service\MimeResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class MimeResolverTest extends TestCase
{
    public function testGetMimeType()
    {
        //Fails
        $mimeResolver = new MimeResolver();
        Assert::assertEquals('text/plain', $mimeResolver->getMimeType('bacon.porn'));

        //Success
        Assert::assertEquals('application/x-httpd-php', $mimeResolver->getMimeType(__FILE__));
        Assert::assertEquals('application/x-httpd-php', $mimeResolver->getMimeType(strtoupper(__FILE__)));
    }

    public function testGetExtension()
    {
        $mimeResolver = new MimeResolver;

        Assert::assertEquals('css', $mimeResolver->getExtension('text/css'));
        Assert::assertEquals('js', $mimeResolver->getExtension('application/javascript'));
    }

    public function testGetUrlMimeType()
    {
        $mimeResolver = new MimeResolver;

        Assert::assertEquals('application/javascript', $mimeResolver->getMimeType('http://foo.bar/file.js'));
    }
}
