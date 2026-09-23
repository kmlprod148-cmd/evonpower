<?php

namespace App\Http;

use Illuminate\Routing\Redirector as BaseRedirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Log;

class Redirector extends BaseRedirector
{
    /**
     * Create a new Redirector instance.
     *
     * @param  \Illuminate\Routing\UrlGenerator  $generator
     * @param  \Illuminate\Session\Store|null  $session
     * @return void
     */
    public function __construct(UrlGenerator $generator, ?SessionStore $session = null)
    {
        parent::__construct($generator);
        
        if ($session) {
            $this->setSession($session);
        }
    }

    /**
     * Create a new redirect response to the previously intended location.
     * Override to prevent redirects to external URLs or CMI payment URLs.
     *
     * @param  mixed  $default
     * @param  int  $status
     * @param  array  $headers
     * @param  bool|null  $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        if (!$this->session) {
            return $this->to($default, $status, $headers, $secure);
        }

        $intendedUrl = $this->session->pull('url.intended', $default);

        // Validate the intended URL to prevent redirects to external URLs or CMI
        if (!$this->isValidUrl($intendedUrl)) {
            Log::warning('Redirector: Blocked redirect to invalid URL', [
                'intended_url' => $intendedUrl,
                'default' => $default,
            ]);
            
            // Use the default instead of the intended URL
            $intendedUrl = $default;
        }

        return $this->to($intendedUrl, $status, $headers, $secure);
    }

    /**
     * Check if the URL is valid and safe to redirect to.
     *
     * @param  string  $url
     * @return bool
     */
    protected function isValidUrl(string $url): bool
    {
        // If it's a relative path, it's safe
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Check if it's a valid relative path
            return strpos($url, '/') === 0 || strpos($url, '?') === 0 || empty($url);
        }

        // Parse the URL
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['host'])) {
            return true; // Relative URL, safe
        }

        // Block CMI payment URLs
        $cmiDomains = [
            'testpayment.cmi.co.ma',
            'payment.cmi.co.ma',
        ];
        
        foreach ($cmiDomains as $cmiDomain) {
            if (strpos($parsedUrl['host'], $cmiDomain) !== false) {
                return false;
            }
        }
        
        // Check for CMI-related paths
        if (isset($parsedUrl['path']) && strpos($parsedUrl['path'], 'est3Dgate') !== false) {
            return false;
        }

        // Check if it's an external URL (different host)
        $appUrl = config('app.url');
        if ($appUrl) {
            $appUrlParsed = parse_url($appUrl);
            
            if (isset($appUrlParsed['host'])) {
                // Compare hosts
                if ($parsedUrl['host'] !== $appUrlParsed['host']) {
                    // Also check if it's a subdomain of the app
                    $appHost = $appUrlParsed['host'];
                    $intendedHost = $parsedUrl['host'];
                    
                    // Allow subdomains of the app (e.g., devcharge.evonpower.com)
                    if (strpos($intendedHost, '.') !== false && strpos($intendedHost, $appHost) === false) {
                        // Check if it's a subdomain pattern
                        $parts = explode('.', $intendedHost);
                        $appParts = explode('.', $appHost);
                        
                        // If the last parts match, it might be a subdomain
                        if (count($parts) > count($appParts)) {
                            $suffix = '.' . implode('.', array_slice($parts, -count($appParts)));
                            if ($suffix === '.' . $appHost) {
                                return true; // It's a subdomain of the app
                            }
                        }
                    }
                    
                    // If hosts don't match and it's not a subdomain, block it
                    return false;
                }
            }
        }

        return true;
    }
}

