<?php

namespace Flshop\ExchangeRate\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CacheInterface;

class ExchangeRate extends Template
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Cache Key Prefix
     *
     * @var string
     */
    protected $cacheKeyPrefix = 'exchange_rate_';

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param CacheInterface $cache
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Curl $curl,
        LoggerInterface $logger,
        CacheInterface $cache,
        array $data = []
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->cache = $cache;
        parent::__construct($context, $data);
    }

    /**
     * Get Exchange Rates
     *
     * @param string $baseCurrency
     * @return array
     */
    public function getExchangeRates($baseCurrency = 'USD')
    {
        // Define the API key (hardcoded)
        $apiKey = '34f3b5830573447cc3a91b67'; // Replace with your ExchangeRate-API key
        if ($apiKey === 'YOUR_EXCHANGERATE_API_KEY') {
            $this->logger->error('ExchangeRate API Key is not set.');
            $this->setData('error_message', 'Exchange Rate API key is not configured.');
            return [];
        }

        $cacheKey = $this->cacheKeyPrefix . strtoupper($baseCurrency);
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData) {
            return unserialize($cachedData);
        }

        $apiUrl = 'https://v6.exchangerate-api.com/v6/' . $apiKey . '/latest/' . urlencode($baseCurrency);

        try {
            // Set User-Agent header (optional but good practice)
            $this->curl->setHeaders([
                'User-Agent' => 'FlshopExchangeRateModule/1.0 (https://magento.test)'
            ]);

            $this->curl->get($apiUrl);
            $response = $this->curl->getBody();
            $rateData = json_decode($response, true);

            if ($rateData === null) {
                throw new \Exception('Failed to decode JSON response: ' . json_last_error_msg());
            }

            if (isset($rateData['result']) && $rateData['result'] === 'success') {
                // Cache the data for 1 hour (3600 seconds)
                $this->cache->save(serialize($rateData['conversion_rates']), $cacheKey, [], 3600);
                return $rateData['conversion_rates'];
            } else {
                $errorMessage = isset($rateData['error-type']) ? $rateData['error-type'] : 'Unknown error';
                throw new \Exception('API Error: ' . $errorMessage);
            }
        } catch (\Exception $e) {
            // Log the error
            $this->logger->error('ExchangeRate API Error: ' . $e->getMessage());

            // Optionally, display a user-friendly message
            $this->setData('error_message', 'Unable to retrieve exchange rates at this time.');
        }

        return [];
    }
}
