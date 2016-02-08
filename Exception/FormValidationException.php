<?php

namespace MediaMonks\RestApiBundle\Exception;

use MediaMonks\RestApiBundle\Response\Error;
use MediaMonks\RestApiBundle\Util\StringUtil;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class FormValidationException extends \Exception
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * FormValidationException constructor.
     * @param FormInterface $form
     * @param int|string $message
     * @param \Exception|string $code
     */
    public function __construct(
        FormInterface $form,
        $message = Error::MESSAGE_FORM_VALIDATION,
        $code = Error::CODE_FORM_VALIDATION
    ) {
        $this->form    = $form;
        $this->message = $message;
        $this->code    = $code;
    }

    /**
     * @return array
     */
    public function getFieldErrors()
    {
        return $this->getErrorMessages($this->form);
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    protected function getErrorMessages(FormInterface $form)
    {
        $errors = [];
        foreach ($form->getErrors() as $error) {
            if (empty($error)) {
                continue;
            }
            if ($form->isRoot()) {
                $errors[] = $this->toErrorArray($error);
            } else {
                $errors[] = $this->toErrorArray($error, $form);
            }
        }
        foreach ($form->all() as $child) {
            if (empty($child)) {
                continue;
            }
            if (!$child->isValid()) {
                foreach ($this->getErrorMessages($child) as $error) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * @param FormError $error
     * @param FormInterface $child
     * @return array
     */
    protected function toErrorArray(FormError $error = null, FormInterface $child)
    {
        $data = [];
        if (is_null($child)) {
            $data['field'] = '#';
        } else {
            $data['field'] = $child->getName();
        }
        if (!is_null($error->getCause()) && !is_null($error->getCause()->getConstraint())) {
            $data['code'] = $this->getErrorCode(StringUtil::classToSnakeCase($error->getCause()->getConstraint()));
        } else {
            $this->getErrorCodeByMessage($error);
        }
        $data['message'] = $error->getMessage();

        return $data;
    }

    /**
     * @param FormError $error
     * @return string
     */
    protected function getErrorCodeByMessage(FormError $error)
    {
        if (stristr($error->getMessage(), 'csrf')) {
            return $this->getErrorCode('csrf');
        }
        return $this->getErrorCode('general');
    }

    /**
     * @param string $value
     * @return string
     */
    protected function getErrorCode($value)
    {
        return sprintf(Error::CODE_FORM_VALIDATION . '.%s', $value);
    }
}
