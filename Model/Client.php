<?php

/**
 * @author Mygento Team
 * @copyright 2016-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Shipment
 */

namespace Mygento\Shipment\Model;

class Client implements \Mygento\Shipment\Api\Client\AbstractClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->curl = $curl;
    }

    /**
     *
     * @param array $options
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function getHttpClient(array $options = [])
    {
        $this->curl->setOptions($options);
        return $this->curl;
    }
}
