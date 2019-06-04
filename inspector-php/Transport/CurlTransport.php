<?php

namespace Inspector\Transport;


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;

class CurlTransport extends AbstractApiTransport
{
    /**
     * String template for curl error message.
     *
     * @var string
     */
    const ERROR_CURL = 'Curl returned an error. [Error no: %d] [HTTP code: %d] [Message: "%s"] [Response: "%s"]';

    /**
     * String template for curl success message.
     *
     * @var string
     */
    const SUCCESS_CURL = 'Curl sent data successfully. [HTTP code: %d] [Response: "%s"]';

    /**
     * CurlTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct($configuration)
    {
        // System need to have CURL available
        if (!function_exists('curl_init')) {
            throw new InspectorException('cURL PHP extension is not available');
        }

        parent::__construct($configuration);
    }

    /**
     * Deliver items to LOG Engine.
     *
     * @param string $data
     */
    public function sendChunk($data)
    {
        $response = wp_remote_post($this->config->getUrl(), [
            'timeout' => 10,
            'body' => $data,
            'headers' => $this->getApiHeaders(),
        ]);

        $code = wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            error_log(date('Y-m-d H:i:s') . " - [Error] [" . get_class($this) . "] - $code");
        }
    }
}