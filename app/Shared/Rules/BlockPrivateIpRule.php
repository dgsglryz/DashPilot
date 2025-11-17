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
        // Validate value is a string and extract host
        if (!is_string($value) || ($host = parse_url($value, PHP_URL_HOST)) === null) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Block localhost and local domain names, then validate IP addresses
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        $isBlockedHost = in_array(strtolower($host), $blockedHosts, true);
        $ips = @gethostbynamel($host);

        if ($isBlockedHost || $ips === false) {
            if ($isBlockedHost) {
                $fail('The :attribute cannot point to localhost or private addresses.');
            } else {
                // If DNS resolution fails, allow it (might be a valid external domain) but log it
                \Log::warning('DNS resolution failed for webhook URL', [
                    'host' => $host,
                    'url' => $value,
                ]);
            }
            return;
        }

        // Check each resolved IP for private/reserved ranges
        foreach ($ips as $ip) {
            $isValid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($isValid === false) {
                $fail('The :attribute cannot point to private or reserved IP addresses.');
                return;
            }
        }
    }
}

