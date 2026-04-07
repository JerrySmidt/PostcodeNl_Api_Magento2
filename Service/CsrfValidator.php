<?php

namespace PostcodeEu\AddressValidation\Service;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

class CsrfValidator
{
    private FormKeyValidator $_formKeyValidator;
    private HttpRequest $_request;

    public function __construct(
        FormKeyValidator $formKeyValidator,
        HttpRequest $request
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->_request = $request;
    }

    public function validate(): void
    {
        if (!$this->_request->isAjax() || !$this->_formKeyValidator->validate($this->_request))
        {
            throw new LocalizedException(__('Invalid request'));
        }
    }
}
