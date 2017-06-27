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

        $this->setCurrentRequest($this->requestStack->getMasterRequest());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function mergeDefaultParameters(array $parameters)
    {
        $request = $this->getCurrentRequest();

        $defaultParameters = [];
        if ($request) {
            $language = $request->headers->get('Accept-Language');

            $defaultParameters['headers'] = [
                'Accept-Language' => $language,
            ];
        }

        $allParameters = array_replace_recursive($defaultParameters, $parameters);

        return parent::mergeDefaultParameters($allParameters);
    }
}
