<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 16.02.2019
 * Time: 13:32
 */

namespace Selastic\Helper;

use Selastic\Exception\RequestFailed;

class Curl
{
    const HEADER_NAMES = [
        'Access-Control-Allow-Credentials',
        'Access-Control-Allow-Headers',
        'Access-Control-Allow-Methods',
        'Access-Control-Allow-Origin',
        'Access-Control-Expose-Headers',
        'Access-Control-Max-Age',
        'Accept-Ranges',
        'Age',
        'Allow',
        'Alternate-Protocol',
        'Cache-Control',
        'Client-Date',
        'Client-Peer',
        'Client-Response-Num',
        'Connection',
        'Content-Disposition',
        'Content-Encoding',
        'Content-Language',
        'Content-Length',
        'Content-Location',
        'Content-MD5',
        'Content-Range',
        'Content-Security-Policy, X-Content-Security-Policy, X-WebKit-CSP',
        'Content-Security-Policy-Report-Only',
        'Content-Type',
        'Date',
        'ETag',
        'Expires',
        'HTTP',
        'Keep-Alive',
        'Last-Modified',
        'Link',
        'Location',
        'P3P',
        'Pragma',
        'Proxy-Authenticate',
        'Proxy-Connection',
        'Refresh',
        'Retry-After',
        'Server',
        'Set-Cookie',
        'Status',
        'Strict-Transport-Security',
        'Timing-Allow-Origin',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Vary',
        'Via',
        'Warning',
        'WWW-Authenticate',
        'X-Aspnet-Version',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-Permitted-Cross-Domain-Policies',
        'X-Pingback',
        'X-Powered-By',
        'X-Robots-Tag',
        'X-UA-Compatible',
        'X-XSS-Protection',
    ];

    /**
     * Optional request to get headers
     *
     * @param $url
     * @return array
     * @throws RequestFailed
     */
    public function options($url)
    {
        $rawOptions = $this->execute([
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => 'OPTIONS',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_VERBOSE        => true,
        ]);

        $rawOptions = array_filter(explode("\r\n", $rawOptions));
        array_shift($rawOptions); // Remove the HTTP {protocol} string


        $options = [];
        foreach ($rawOptions as $option) {
            list($key, $value) = explode(':', $option);
            $options[trim($key)] = trim($value);
        }

        return $options;
    }

    /**
     * @param $options
     * @return mixed
     * @throws RequestFailed
     */
    public function execute($options)
    {
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new RequestFailed($err);
        }
        return $response;
    }

}