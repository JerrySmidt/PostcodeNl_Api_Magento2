<?php

namespace PostcodeEu\AddressValidation\Plugin;

use Magento\Customer\Model\Metadata\Form;
use Magento\Framework\App\RequestInterface;
use PostcodeEu\AddressValidation\Helper\StoreConfigHelper;

class SortSalesOrderAddressFields
{
    /**
     * @var StoreConfigHelper
     */
    private $_storeConfigHelper;

    /**
     * @var RequestInterface
     */
    private $_request;

    /**
     * @param RequestInterface $request
     * @param StoreConfigHelper $storeConfigHelper
     */
    public function __construct(
        RequestInterface $request,
        StoreConfigHelper $storeConfigHelper
    ) {
        $this->_request = $request;
        $this->_storeConfigHelper = $storeConfigHelper;
    }

    /**
     * Reorder address fields.
     *
     * @param Form $subject
     * @param array $result
     * @return array
     */
    public function afterGetAttributes(Form $subject, array $result)
    {
        if ($this->_storeConfigHelper->isSetFlag('change_fields_position')
            && strpos($this->_request->getFullActionName(), 'sales_order_create') !== false
        ) {
            $sortOrders = [
                'country_id' => 70,
                'street' => 80,
                'postcode' => 90,
                'city' => 100,
                'region' => 110,
                'region_id' => 110,
            ];

            foreach ($sortOrders as $code => $sortOrder) {
                if (isset($result[$code])) {
                    $result[$code]->setSortOrder($sortOrder);
                }
            }
        }

        return $result;
    }
}
