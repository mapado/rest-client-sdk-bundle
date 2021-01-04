<?php

namespace Mapado\RestClientSdkBundle\DataCollector;

use Mapado\RestClientSdk\SdkClient;
use Mapado\RestClientSdk\SdkClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class RestClientSdkDataCollector
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RestClientSdkDataCollector extends DataCollector
{
    /**
     * @var SdkClientRegistry
     */
    private $registry;

    public function __construct(SdkClientRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?\Throwable $exception
     */
    public function collect(Request $request, Response $response, $exception = null)
    {
        $sdkClientList = $this->registry->getSdkClientList();

        $this->data = [
            'historyLogged' => !empty($sdkClientList),
            'requestHistory' => []
        ];

        foreach ($sdkClientList as $key => $sdk) {
            $this->data['requestHistory'][$key] = $sdk->getRestClient()->getRequestHistory();
        }
    }

    public function getRequestHistory()
    {
        return $this->data['requestHistory'];
    }

    public function isHistoryLogged()
    {
        return $this->data['historyLogged'];
    }

    public function getNbRequest()
    {
        $carry = 0;
        foreach ($this->data['requestHistory'] as $requestHistory) {
            $carry += count($requestHistory);
        }

        return $carry;
    }

    public function getRequestTime()
    {
        $carry = 0;
        foreach ($this->data['requestHistory'] as $requestHistory) {
            foreach ($requestHistory as $request) {
                $carry += $request['queryTime'];
            }
        }

        return $carry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mapado_rest_client_sdk';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }
}
