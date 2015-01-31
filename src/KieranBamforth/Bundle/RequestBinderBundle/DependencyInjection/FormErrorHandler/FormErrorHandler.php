<?php

namespace KieranBamforth\Bundle\RequestBinderBundle\DependencyInjection\FormErrorHandler;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

class FormErrorHandler
{
    /**
     * Takes an invalid form and throws a HTTP exception with the body
     * set to a JSON string of standardized form errors.
     *
     * @param Form $form The form that contains the error.
     *
     * @throws HttpException The detailed exception of what went wrong.
     */
    public function handle(Form $form)
    {
        $formErrors = $form->getErrors(true);

        $standardizedErrors = array();
        foreach($formErrors as $error) {
            array_push($standardizedErrors, $this->getStandardizedError($error));
        }

        throw new HttpException(
            400,
            json_encode($standardizedErrors)
        );
    }

    /**
     * Takes a form error and standardizes it into an array.
     *
     * @param FormError $error The form error.
     *
     * @returns array The standardized form error.
     */
    public function getStandardizedError(FormError $error)
    {
        $cause = $error->getCause();

        $standardizedError = array (
            'messageTemplate' => $cause->getMessage(),
            'messageParameters' => $cause->getMessageParameters()
        );

        if ("" !== $propertyPath = $cause->getPropertyPath()) {
            $standardizedError['propertyPath'] = $propertyPath;
        }

        return $standardizedError;
    }
}