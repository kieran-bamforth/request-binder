<?php

namespace KieranBamforth\Bundle\RequestBinderBundle\Tests\DependencyInjection\RequestBinder;

use KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection;

class RequestBinderTest extends \PHPUnit_Framework_TestCase
{
    private $formFactory;
    private $formErrorHandler;
    private $om;
    private $requestBinder;

    public function setUp()
    {
        $this->formFactory = \Mockery::mock(
            'Symfony\Component\Form\FormFactory'
        );
        $this->formErrorHandler = \Mockery::mock(
            'KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection\FormErrorHandler\FormErrorHandler'
        );
        $this->om = \Mockery::mock(
            'Doctrine\Common\Persistence\ObjectManager'
        );
        // Create the request binder with mock objects.
        $this->requestBinder = new DependencyInjection\RequestBinder\RequestBinder(
            $this->formFactory,
            $this->formErrorHandler,
            $this->om
        );
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @dataProvider testBindRequestDataProvider
     */
    public function testBindRequest($httpVerb, $expectedFunctionName)
    {
        // Stub the mockBinder's functions as not to error out.
        $mockBinder = \Mockery::mock(
            "KieranBamforth\\Bundle\\UnfriendedAPICommonBundle\\DependencyInjection\\RequestBinder\\RequestBinder[$expectedFunctionName]",
            array($this->formFactory, $this->formErrorHandler, $this->om),
            array(
                $expectedFunctionName => true
            )
        );

        // Tell the mockBinder which function we are expecting it to call as a result of
        // the httpVerb passed to the bindRequest method.
        $mockBinder->shouldReceive($expectedFunctionName)->once();

        // Create the mock request object and set it's httpVerb.
        $mockRequest = \Mockery::mock(
            'Symfony\Component\HttpFoundation\Request',
            array(
                'getMethod' => $httpVerb
            )
        );

        // Assertions made, now handle the mock request!
        $mockBinder->bindRequest($mockRequest, null);
    }

    public function testBindRequestDataProvider()
    {
        return array(
            array('POST', 'persistEntity'),
            array('PUT', 'persistEntity'),
            array('PATCH', 'persistEntity'),
            array('DELETE', 'removeEntity')
        );
    }
}