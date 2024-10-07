<?php
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testShouldCheckAssertTrue(){
        $this->assertTrue(true);
    }

    public function testShouldCheckAssertEquals(){
        $this->assertEquals("a", "a");
    }
}

