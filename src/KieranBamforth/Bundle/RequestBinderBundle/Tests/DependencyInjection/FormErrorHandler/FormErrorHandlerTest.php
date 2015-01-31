<?php

namespace KieranBamforth\Bundle\RequestBinderBundle\Tests\DependencyInjection\FormErrorHandler;

use KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection;

class FormErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $formErrorHandler;

    public function setUp()
    {
        $this->formErrorHandler = new DependencyInjection\FormErrorHandler\FormErrorHandler();
    }

    public function testHandle()
    {
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException');
        $mockedForm = \Mockery::mock('Symfony\Component\Form\Form');
        $this->formErrorHandler->handle($mockedForm);
    }
}