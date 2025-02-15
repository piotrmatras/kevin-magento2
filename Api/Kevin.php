<?php

namespace Kevin\Payment\Api;

use Kevin\Client;
use Kevin\SecurityManager;

/**
 * Class Kevin.
 */
class Kevin
{
    /**
     * Signature verify timeout in milliseconds.
     */
    const SIGNATURE_VERIFY_TIMEOUT = 300000;

    /**
     * @var \Kevin\Payment\Gateway\Config\Config
     */
    protected $config;

    protected $banks;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * Kevin constructor.
     */
    public function __construct(
        \Kevin\Payment\Gateway\Config\Config $config,
        \Magento\Framework\Module\ResourceInterface $moduleResource
    ) {
        $this->config = $config;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @return Client|void
     */
    public function getConnection($clientId = null, $clientSecret = null)
    {
        $options = [
            'error' => 'exception',
            'version' => '0.3',
        ];

        if (getenv('API_KEVIN_DOMAIN')) {
            $options['domain'] = getenv('API_KEVIN_DOMAIN');
        }

        $options = array_merge($options, $this->config->getSystemData());

        return new \Kevin\Client($clientId, $clientSecret, $options);
    }

    /**
     * @return Client|void
     */
    public function getClient()
    {
        $clientId = $this->config->getClientId();
        $clientSecret = $this->config->getClientSecret();

        $client = $this->getConnection($clientId, $clientSecret);

        return $client;
    }

    /**
     * @return array
     */
    public function getProjectSettings()
    {
        try {
            $methods = $this->getClient()->auth()->getProjectSettings();

            return $methods;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array|mixed|void
     */
    public function getAllowedRefund()
    {
        try {
            $settings = $this->getProjectSettings();
            if (isset($settings['allowedRefundsFor'])) {
                return $settings['allowedRefundsFor'];
            }

            return [];
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getPaymentMethods()
    {
        try {
            $settings = $this->getProjectSettings();
            if (isset($settings['paymentMethods'])) {
                return $settings['paymentMethods'];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @param null $country
     *
     * @return array|mixed
     */
    public function getBanks($country = null)
    {
        try {
            if (!$this->banks) {
                $params = [];
                if ($country) {
                    $params = ['countryCode' => $country];
                }
                $kevinAuth = $this->getClient()->auth();

                $banks = $kevinAuth->getBanks($params);
                if (isset($banks['data'])) {
                    $this->banks = $banks['data'];
                }
            }

            return $this->banks;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getBank($bankId)
    {
        try {
            $kevinAuth = $this->getClient()->auth();
            $bank = $kevinAuth->getBank($bankId);

            return $bank;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return mixed
     */
    public function initPayment($params)
    {
        return $this->getClient()->payment()->initPayment($params);
    }

    /**
     * @return mixed
     */
    public function getPaymentStatus($paymentId, $attr)
    {
        return $this->getClient()->payment()->getPaymentStatus($paymentId, $attr);
    }

    /**
     * @return mixed
     */
    public function getPayment($paymentId, $attr)
    {
        return $this->getClient()->payment()->getPayment($paymentId, $attr);
    }

    /**
     * @return array
     */
    public function getAvailableCountries()
    {
        try {
            $kevinAuth = $this->getClient()->auth();
            $response = $kevinAuth->getCountries();

            if (isset($response['data'])) {
                return $response['data'];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * @return mixed
     */
    public function initRefund($paymentId, $attr)
    {
        return $this->getClient()->payment()->initiatePaymentRefund($paymentId, $attr);
    }

    /**
     * @return mixed|void
     */
    public function getRefunds($paymentId)
    {
        $response = $this->getClient()->payment()->getPaymentRefunds($paymentId);
        if (isset($response['data'])) {
            return $response['data'];
        }
    }

    /**
     * @return mixed
     */
    public function verifySignature($endpointSecret, $requestBody, $headers, $webhookUrl)
    {
        $timestampTimeout = self::SIGNATURE_VERIFY_TIMEOUT;
        $isValid = SecurityManager::verifySignature($endpointSecret, $requestBody, $headers, $webhookUrl, $timestampTimeout);

        return $isValid;
    }
}
