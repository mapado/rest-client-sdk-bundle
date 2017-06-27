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
     * setRequestStack
     *
     * @param RequestStack $requestStack
     * @access public
     * @return RestClient
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $request = $requestStack->getMasterRequest();

        if ($request) {
            $this->setCurrentRequest($request);
        }

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
