<?php

namespace Report\Summary\Dependencies;

class DependenciesReportTestClass extends DependenciesReportTestBaseClass
{
    private $dependencyTestObject;

    public function __construct(DependencyTestClass $dependencyTestObject)
    {
        $this->dependencyTestObject = $dependencyTestObject;
    }

    public function doSomething(DependencyArgumentTestClass $dependencyArgumentTestObject)
    {
        // do nothing
        throw new DependencyTestException();
    }
}

class DependenciesReportTestBaseClass
{
}

class DependencyTestClass
{
}

class DependencyArgumentTestClass
{
}

class DependencyTestException extends \Exception
{
}
