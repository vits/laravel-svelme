<?php


use Illuminate\Http\JsonResponse;
use Vits\Svelme\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function invokePrivateMethod($obj, $name, ...$args)
{
    $class = new \ReflectionClass($obj);
    $method = $class->getMethod($name);
    return $method->invoke($obj, ...$args);
}

function getPrivateProperty($object, $property)
{
    $class = new \ReflectionClass($object);
    $property = $class->getProperty($property);
    $property->setAccessible(true);
    return $property->getValue($object);
}

function setPrivateProperty($object, $property, $value)
{
    $class = new \ReflectionClass($object);
    $property = $class->getProperty($property);
    $property->setAccessible(true);
    $property->setValue($object, $value);
}
