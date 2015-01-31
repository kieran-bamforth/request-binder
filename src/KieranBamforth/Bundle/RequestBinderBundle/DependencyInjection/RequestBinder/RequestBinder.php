<?php

namespace KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection\RequestBinder;

use Doctrine\Common\Persistence\ObjectManager;
use KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection\FormErrorHandler\FormErrorHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class RequestBinder
 *
 * Takes a POST / PATCH / DELETE Request and attempts to bind it
 * to an underlying Entity object via the use of Symfony Forms.
 */
class RequestBinder
{
    private $formFactory;
    private $formErrorHandler;
    private $om;

    /**
     * @param FormFactory      $formFactory      The FormFactory that is used to create Forms.
     * @param FormErrorHandler $formErrorHandler If a Form is invalid, then it is passed to
     *                                           the FormErrorHandler for further processing.
     * @param ObjectManager    $om               The underlying object manager that is used for persisting/
     *                                           removing objects.
     */
    public function __construct(FormFactory $formFactory, FormErrorHandler $formErrorHandler, ObjectManager $om)
    {
        $this->formFactory = $formFactory;
        $this->formErrorHandler = $formErrorHandler;
        $this->om = $om;
    }

    /**
     * If the Request is POST/PATCH, takes a Request and attempts to bind it to an underlying Entity.
     * If the Request is DELETE, attempts to hard delete.
     *
     * @param Request $request The incoming Request object.
     * @param mixed   $entity  The Entity to bind the Request to.
     *
     * @return mixed If the Request is a successful then the newly-bound Entity is returned.
     *
     */
    public function bindRequest(Request $request, $entity)
    {
        $httpVerb = $request->getMethod();

        if ($httpVerb === 'POST' || $httpVerb === 'PUT' || $httpVerb === 'PATCH') {
            $this->persistEntity($request, $entity);
        } else if ($httpVerb === 'DELETE') {
            $this->removeEntity($entity);
        }

        return $entity;
    }

    /**
     * Obtains a Form with the Entity set as the underlying object, and then tries to submit it.
     * As part of the submission, data-binding occurs and then validation is carried out on the
     * newly-bound Entity. If validation of the newly-bound Entity fails, then the Form is passed to
     * the FormErrorHandler where a detailed exception is thrown.
     *
     * @param Request $request The Request to extract the submitted data from.
     * @param mixed   $entity  The Entity to bind the Request to.
     *
     * @throws HttpException If validation of the newly-bound Entity fails, a HTTP 400 exception is thrown.
     */
    public function persistEntity(Request $request, &$entity)
    {
        $form = $this->getForm($request, $entity);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            $this->formErrorHandler->handle($form);
        }
    }

    /**
     * If the passed entity has the SoftDeletableInterface, the soft delete method is called.
     * Otherwise, the Entity is removed from the Object Manager. Note that the removal is not flushed,
     * it is up to the calling class to flush any changes caused by this service.
     *
     * @param mixed $entity The Entity to remove
     */
    public function removeEntity(&$entity)
    {
        $this->om->remove($entity);
    }

    /**
     * Attempts to create a form with the name "<http verb><entity class name">.
     *
     * For example, if a POST request was made with the entity type "AbstractEntity", this function
     * would tell the FormFactory to create a Form named "postAbstractEntity".
     *
     * Remember to create the Form's class and register it as a service. Details here:
     * http://symfony.com/doc/current/book/forms.html#creating-form-classes,
     * http://symfony.com/doc/current/book/forms.html#defining-your-forms-as-services.
     *
     * @param Request $request The request to get the HTTP verb from.
     * @param mixed   $entity  The underlying entity to bind to the form.
     *
     * @return Form The Form object.
     */
    public function getForm(Request $request, $entity)
    {
        $httpVerb = $request->getMethod();
        $reflection = new \ReflectionClass($entity);
        $formName = strtolower($httpVerb) . $reflection->getShortName();

        return $this->formFactory->createNamedBuilder('', $formName, $entity)
            ->setMethod($httpVerb)
            ->getForm();
    }
}
