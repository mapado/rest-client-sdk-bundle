<?php

namespace Mapado\RestClientSdkBundle\DataCollector;

use Mapado\RestClientSdk\SdkClient;
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
     * sdkList
     *
     * @var array<SdkClient> $sdkList
     * @access private
     */
    private $sdkList;

    /**
     * __construct
     *
     * @access public
     */
    public function __construct(\Traversable $sdkList)
    {
        $this->sdkList = $sdkList;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'historyLogged' => !empty($this->sdkList),
            'requestHistory' => []
        ];

        foreach ($this->sdkList as $sdk) {
            $this->data['requestHistory'] += $sdk->getRestClient()->getRequestHistory();
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

    public function getRequestTime()
    {
        return array_reduce(
            $this->data['requestHistory'],
            function ($carry, $item) {
                return $carry + $item['queryTime'];
            }
        );
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
