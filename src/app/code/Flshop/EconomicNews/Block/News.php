<?php

namespace Flshop\EconomicNews\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\HTTP\Client\Curl;

class News extends Template
{
    protected $curl;

    public function __construct(
        Template\Context $context,
        Curl $curl,
        array $data = []
    ) {
        $this->curl = $curl;
        parent::__construct($context, $data);
    }

    public function getEconomicNews()
    {
        $apiKey = 'b3b218f2cb1147dbb3efba5ee4a1a318'; // Replace with your NewsAPI key
        $apiUrl = 'https://newsapi.org/v2/top-headlines?category=business&language=en&apiKey=' . $apiKey;

        try {
            // Set the User-Agent header with your actual website URL
            $this->curl->setHeaders([
                'User-Agent' => 'FlshopEconomicNews/1.0 (https://magento.test)'
            ]);

            $this->curl->get($apiUrl);
            $response = $this->curl->getBody();
            $newsData = json_decode($response, true);

            if ($newsData === null) {
                throw new \Exception('Failed to decode JSON response: ' . json_last_error_msg());
            }

            if (isset($newsData['status']) && $newsData['status'] === 'ok') {
                return $newsData['articles'];
            } else {
                $errorMessage = isset($newsData['message']) ? $newsData['message'] : 'Unknown error';
                throw new \Exception('API Error: ' . $errorMessage);
            }
        } catch (\Exception $e) {
            // For debugging: Display the error message
            echo '<pre>Error: ' . $e->getMessage() . '</pre>';
        }

        return [];
    }
}
