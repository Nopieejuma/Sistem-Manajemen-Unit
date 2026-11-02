<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustContainerProxy extends TrustProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('deployment.container')) {
            $this->proxies = config('deployment.trusted_proxies');
            $this->headers = Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO;
            $host = (string) parse_url(config('app.url'), PHP_URL_HOST);
            $request::setTrustedHosts(['^'.preg_quote($host, '/').'$']);
        }

        return parent::handle($request, $next);
    }
}
