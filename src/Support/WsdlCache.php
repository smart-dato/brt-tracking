<?php

namespace SmartDato\BrtTracking\Support;

/**
 * Helper class that can cache and patch WSDL files locally.
 *
 * The BRT webservices provide WSDL files over HTTPS but embed HTTP locations.
 * This class downloads the WSDL files, replaces http:// addresses with https://,
 * and stores them in a configurable directory so that PHP's SoapClient can
 * correctly call the endpoints over SSL.
 */
class WsdlCache
{
    /**
     * Download and patch each configured WSDL file.
     */
    public function cacheAndPatch(): void
    {
        $wsdls = config('brt-tracking.wsdl', []);
        foreach ($wsdls as $key => $url) {
            $xml = @file_get_contents($url);
            if ($xml === false) {
                continue;
            }
            // Replace any http:// occurrences in soap:address locations with https://.
            $patched = preg_replace('~location="http://~i', 'location="https://', $xml);
            $path = $this->pathFor($url);
            if (! is_dir(dirname($path))) {
                @mkdir(dirname($path), 0775, true);
            }
            file_put_contents($path, $patched);
        }
    }

    /**
     * Resolve the local path for a WSDL URL, caching if necessary.
     */
    public function getPatched(string $url): string
    {
        $path = $this->pathFor($url);
        if (! file_exists($path)) {
            $this->cacheAndPatch();
        }

        return $path;
    }

    /**
     * Compute the file path for a WSDL URL.
     */
    protected function pathFor(string $url): string
    {
        $base = rtrim(config('brt-tracking.wsdl_cache_path'), DIRECTORY_SEPARATOR);

        return $base.'/'.md5($url).'.wsdl';
    }
}
