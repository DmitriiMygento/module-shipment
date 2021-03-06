<?php

/**
 * @author Mygento Team
 * @copyright 2016-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier as BaseCarrier;
use Mygento\Shipment\Api\Carrier\AbstractCarrierInterface;

abstract class AbstractCarrier extends BaseCarrier implements AbstractCarrierInterface
{
    /**
     * @var \Mygento\Shipment\Model\Carrier $carrier
     */
    protected $carrier;

    /**
     * @var \Mygento\Shipment\Helper\Data $helper
     */
    protected $helper;

    /**
     * @param \Mygento\Shipment\Helper\Data $helper
     * @param \Mygento\Shipment\Model\Carrier $carrier
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Mygento\Shipment\Helper\Data $helper,
        \Mygento\Shipment\Model\Carrier $carrier,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->carrier = $carrier;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $data
        );
    }

    /**
     * Validate shipping request before processing
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return bool|\Magento\Quote\Model\Quote\Address\RateResult\Error
     */
    protected function validateRequest(RateRequest $request)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }
        $this->helper->info('Started calculating to: ' . $request->getDestCity());
        if (strlen($request->getDestCity()) <= 2) {
            $this->helper->info('City strlen <= 2, aborting ...');
            return false;
        }
        if ($this->helper->getConfig('defaultweight')) {
            $request->setPackageWeight((float) $this->helper->getConfig('defaultweight'));
            $this->helper->debug('Set default weight: ' . $request->getPackageWeight());
        }
        $this->helper->debug('Weight: ' . $request->getPackageWeight());
        if (0 >= $request->getPackageWeight()) {
            return $this->returnError('Zero weight');
        }
        return true;
    }

    /**
     * @return float
     */
    protected function getCartTotal()
    {
        return $this->carrier->getCartTotal();
    }

    /**
     * @return mixed
     */
    protected function getResult()
    {
        return $this->carrier->getResult();
    }

    /**
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function getRateMethod()
    {
        return $this->carrier->getRateMethod();
    }

    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return mixed
     */
    public function convertWeight(RateRequest $request)
    {
        return $request->getPackageWeight() * (float) $this->getConfigData('weightunit');
    }

    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @param int $mode
     * @return string
     */
    public function convertCity(RateRequest $request, $mode = MB_CASE_TITLE): string
    {
        return mb_convert_case(trim($request->getDestCity()), $mode, 'UTF-8');
    }

    /**
     *
     * @param string $message
     * @return bool|\Magento\Quote\Model\Quote\Address\RateResult\Error
     */
    protected function returnError($message)
    {
        if ($this->getConfigData('debug')) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__($message));
            return $error;
        }
        return false;
    }

    /**
     * Создание метода доставки
     *
     * @param array $method
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    public function createRateMethod(array $method)
    {
        $rate = $this->getRateMethod();

        $rate->setCarrier($method['code']);
        $rate->setCarrierTitle($method['title']);
        $rate->setMethod($method['method']);
        $rate->setMethodTitle($method['name']);
        $rate->setPrice($method['price']);
        $rate->setCost($method['cost']);

        if (isset($method['estimate'])) {
            $rate->setEstimate(date(
                'Y-m-d',
                strtotime('+' . $method['estimate'] . ' days')
            ));
        }

        if (isset($method['estimate_dates']) && is_array($method['estimate_dates'])) {
            foreach ($method['estimate_dates'] as $key => $value) {
                $method['estimate_dates'][$key] = date('Y-m-d', strtotime($value));
            }
            $rate->setEstimateDates(json_encode($method['estimate_dates']));
        }

        if (isset($method['latitude'])) {
            $rate->setLatitude($method['latitude']);
        }

        if (isset($method['longitude'])) {
            $rate->setLongitude($method['longitude']);
        }

        return $rate;
    }

    /**
     *
     * @return boolean
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }

    /**
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return [];
    }

    /**
     *
     * @return boolean
     */
    public function isCityRequired(): bool
    {
        return true;
    }

    /**
     *
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return null|string
     */
    protected function getPostCode(RateRequest $request)
    {
        $postcode = $request->getDestPostcode();
        if ($postcode && $postcode != '') {
            $digitsOnlyPostcode = preg_replace('/[^0-9]/', '', $postcode);
            if ($digitsOnlyPostcode && $digitsOnlyPostcode != '') {
                return $digitsOnlyPostcode;
            }
        }
        return null;
    }
}
