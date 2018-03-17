<?php

namespace SForce\Test;

if (class_exists(\PHPUnit\Framework\TestCase::class)) {
    // PHPUnit 6+
    class TestCase extends \PHPUnit\Framework\TestCase
    {
    }
} elseif (class_exists(\PHPUnit_Framework_TestCase::class)) {
    // PHPUnit 5
    class TestCase extends \PHPUnit_Framework_TestCase
    {
    }
}
