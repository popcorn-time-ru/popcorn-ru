<?php

namespace App\Traktor;

use App\Traktor\Exception\AuthorizationException;
use App\Traktor\Exception\AvailabilityException;
use App\Traktor\Exception\MissingApiKeyException;
use App\Traktor\Exception\RequestException;
use App\Traktor\Exception\UnknownMethodException;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Alan Ly <hello@alan.ly>
 */
class Client
{

    /**
     * Constant containing the end-point for the Trakt.tv API.
     */
    const TRAKT_API_ENDPOINT = 'http://api.trakt.tv';

    /**
     * @var string
     */
    protected $apiKey = null;

    /**
     * @var GuzzleClient
     */
    protected $client = null;

    /**
     * Construct a new instance of Traktor.
     *
     * @param null|GuzzleClient $client
     */
    public function __construct(GuzzleClient $client = null)
    {
        if (!$client) {
            $this->client = new GuzzleClient;
        } else {
            $this->client = $client;
        }
    }

    /**
     * Performs a GET request against the API and returns the results as
     * instance(s) of `stdClass`.
     *
     * @param string $method
     * @param null|array $params
     * @return mixed
     */
    public function get($method, $params = null)
    {
        if (!$this->apiKey) {
            throw new MissingApiKeyException('The request API key is unset.');
        }

        if (!$params) {
            $params = [];
        }

        $target = $this->assembleGetRequestTarget($method, $params);

        $response = $this->performGetRequest($target);

        return $this->parseResponse($response);
    }

    /**
     * Creates the complete request target based on the requested method and
     * any associated parameters.
     *
     * @param string $method
     * @param array $params
     * @return string
     */
    protected function assembleGetRequestTarget($method, $params = [])
    {
        $method = preg_replace('/\./', '/', $method);
        $params = http_build_query($params);

        $target = self::TRAKT_API_ENDPOINT
            . '/' . $method
            . '?' . $params;

        return $target;
    }

    /**
     * Executes the GET request specified by `$target`.
     *
     * @param string $target
     * @return ResponseInterface
     */
    protected function performGetRequest($target)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'trakt-api-version' => 2,
            'trakt-api-key' => $this->getApiKey(),
        ];
        return $this->client->get($target, ['headers' => $headers]);
    }

    /**
     * Get the user key for the API session.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the user key for the API session.
     *
     * @param string $key
     * @return void
     */
    public function setApiKey($key)
    {
        $this->apiKey = $key;
    }

    /**
     * Parse a response, appropriately converting from JSON to `stdClass` as
     * well as handling errors.
     *
     * @param ResponseInterface
     * @return mixed
     */
    protected function parseResponse(ResponseInterface $response)
    {
        $this->checkResponseErrors($response);

        try {
            $body = $response->getBody();
            $decodedBody = json_decode($body, false);
        } catch (GuzzleHttp\Exception\ParseException $e) {
            throw new RequestException('Unable to parse response: '
                . $response->getBody());
        }

        return $decodedBody;
    }

    /**
     * Checks a GuzzleHttp response for errors, throwing the appropriate
     * exception if necessary.
     *
     * @param ResponseInterface
     * @return void
     */
    protected function checkResponseErrors($response)
    {
        $responseStatusCode = intval($response->getStatusCode());

        if ($responseStatusCode === 200) return;

        try {
            $body = $response->getBody();
            $decodedBody = json_decode($body, false);
        } catch (\GuzzleHttp\Exception\ParseException $e) {
            throw new RequestException('Unable to parse response: '
                . $response->getBody());
        }

        switch ($responseStatusCode) {
            case 401:
                throw new AuthorizationException($decodedBody->error);
            case 404:
                throw new UnknownMethodException($decodedBody->error);
            case 503:
                throw new AvailabilityException($decodedBody->error);
            default:
                throw new RequestException('Unrecognized status code ('
                    . $responseStatusCode . '): '
                    . $response->getBody());
        }
    }

}
