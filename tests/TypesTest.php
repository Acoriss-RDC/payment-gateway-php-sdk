<?php

namespace Acoriss\PaymentGateway\Tests;

use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testArrayShapesDocumentationExists(): void
    {
        // Just ensure the Types class loads without error; shapes are in PHPDoc.
        $ref = new \ReflectionClass(\Acoriss\PaymentGateway\Types::class);
        $this->assertTrue($ref->isFinal());
        $this->assertSame('Types', $ref->getShortName());
    }
}
