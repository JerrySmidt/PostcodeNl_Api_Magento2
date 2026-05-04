<?php

namespace PostcodeEu\AddressValidation\Service;

use Magento\Framework\App\Area;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

class CsrfValidator
{
    private FormKeyValidator $_formKeyValidator;
    private HttpRequest $_request;
    private State $_appState;

    public function __construct(
        FormKeyValidator $formKeyValidator,
        HttpRequest $request,
        State $appState
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->_request = $request;
        $this->_appState = $appState;
    }

    public function validate(): void
    {
        try {
            if ($this->_appState->getAreaCode() === Area::AREA_ADMINHTML) {
                return;
            }
        }
        catch (LocalizedException $e) {
            // Area code not set.
        }

        if (!$this->_request->isAjax() || !$this->_formKeyValidator->validate($this->_request)) {
            throw new LocalizedException(__('Invalid request'));
        }
    }
}
