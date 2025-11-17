<?php
declare(strict_types=1);

namespace App\Shared\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * BlockPrivateIpRule prevents SSRF attacks by blocking private/localhost IP addresses.
 */
class BlockPrivateIpRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure(string, string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if ($host === null) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Block localhost and local domain names
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            $fail('The :attribute cannot point to localhost or private addresses.');
            return;
        }

        // Resolve hostname to IP addresses
        $ips = @gethostbynamel($host);

        if ($ips === false) {
            // If DNS resolution fails, allow it (might be a valid external domain)
            // But log it for investigation
            \Log::warning('DNS resolution failed for webhook URL', [
                'host' => $host,
                'url' => $value,
            ]);
            return;
        }

        // Check each resolved IP
        foreach ($ips as $ip) {
            // Filter out private and reserved IP ranges
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('The :attribute cannot point to private or reserved IP addresses.');
                return;
            }
        }
    }
}

