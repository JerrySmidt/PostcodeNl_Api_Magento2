<?php

namespace PostcodeEu\AddressValidation\Controller\Adminhtml\Address;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use PostcodeEu\AddressValidation\Api\PostcodeModelInterface;

Class Api extends Action implements HttpGetActionInterface
{
    protected $_resultJsonFactory;
    protected $_postcodeModel;
    protected $_serviceOutputProcessor;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PostcodeModelInterface $postcodeModel,
        ServiceOutputProcessor $serviceOutputProcessor
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_postcodeModel = $postcodeModel;
        $this->_serviceOutputProcessor = $serviceOutputProcessor;
    }

    /**
     * Call address API methods
     *
     * @return Json
     */
    public function execute(): Json
    {
        // Get params from path. Slice from index 4 to exclude
        // <admin-front-name>/<route-front-name>/<controller-name>/<action-name>
        $params = array_slice(explode('/', trim($this->getRequest()->getPathInfo(), '/')), 4);
        $params = array_map('rawurldecode', $params);

        switch (array_shift($params))
        {
            case 'postcode':
                $serviceMethod = 'getNlAddress';
            break;
            case 'autocomplete':
                $serviceMethod = 'getAddressAutocomplete';
            break;
            case 'address_details':
                $serviceMethod = count($params) > 1 ? 'getAddressDetailsCountry' : 'getAddressDetails';
            break;
            default:
                throw new \Exception('Invalid service method');
        }

        $result = $this->_postcodeModel->$serviceMethod(...$params);
        $result = $this->_serviceOutputProcessor->process($result, PostcodeModelInterface::class, $serviceMethod);
        return $this->_resultJsonFactory->create()->setData($result);
    }
}
