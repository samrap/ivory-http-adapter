<?php

/*
 * This file is part of the Ivory Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\HttpAdapter;

use Ivory\HttpAdapter\Message\InternalRequestInterface;
use Ivory\HttpAdapter\Normalizer\BodyNormalizer;
use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\EventLoop\Factory as EventLoopFactory;
use React\HttpClient\Factory as HttpClientFactory;
use React\HttpClient\Response;

/**
 * React http adapter.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class ReactHttpAdapter extends AbstractHttpAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'react';
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(InternalRequestInterface $internalRequest)
    {
        $loop = EventLoopFactory::create();
        $dnsResolverFactory = new DnsResolverFactory();
        $httpClientFactory = new HttpClientFactory();
        $url = (string) $internalRequest->getUrl();

        $error = null;
        $response = null;
        $body = null;

        $request = $httpClientFactory->create($loop, $dnsResolverFactory->createCached('8.8.8.8', $loop))->request(
            $internalRequest->getMethod(),
            $url,
            $this->prepareHeaders($internalRequest, true, true, true)
        );

        $request->on('error', function (\Exception $onError) use (&$error) {
            $error = $onError;
        });

        $request->on('response', function (Response $onResponse) use (&$response, &$body) {
            $onResponse->on('data', function ($data) use (&$body) {
                $body .= $data;
            });

            $response = $onResponse;
        });

        $request->end($this->prepareBody($internalRequest));
        $loop->run();

        if ($error !== null) {
            throw HttpAdapterException::cannotFetchUrl($url, $this->getName(), $error->getMessage());
        }

        return $this->createResponse(
            $response->getVersion(),
            (integer) $response->getCode(),
            $response->getReasonPhrase(),
            $response->getHeaders(),
            BodyNormalizer::normalize($body, $internalRequest->getMethod())
        );
    }
}