<?php

namespace Mapado\RestClientSdkBundle;

use Mapado\RestClientSdk\RestClient;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequestAwareRestClient
 * @author Julien Deniau <julien.deniau@mapado.com>
 */
class RequestAwareRestClient extends RestClient
{
    /**
     * requestStack
     *
     * @var RequestStack
     * @access private
     */
    private $requestStack;

    /**
     * setRequestStack
     *
     * @param RequestStack $requestStack
     * @access public
     * @return RestClient
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function mergeDefaultParameters(array $parameters)
    {
        $parameters = parent::mergeDefaultParameters($parameters);

        $language = $this->requestStack->getMasterRequest()->headers->get('Accept-Language');

        $parameters['headers'] = isset($parameters['headers']) ? $parameters['headers'] : [];
        $parameters['headers'] = array_merge($parameters['headers'], ['Accept-Language' => $language]);

        return $parameters;
    }
}
