<?php
declare(strict_types=1);

namespace Local\BlogApi\Model;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception as WebapiException;

class IpWhitelist
{
    /**
     * @param string[] $allowedIpPatterns
     */
    public function __construct(
        private readonly RemoteAddress $remoteAddress,
        private readonly array $allowedIpPatterns = []
    ) {
    }

    public function assertAllowed(): void
    {
        $clientIp = $this->getClientIp();

        if ($clientIp !== null && $this->isAllowed($clientIp)) {
            return;
        }

        throw new WebapiException(
            new Phrase(
                'Client IP "%1" is not allowed to access this endpoint. Allowed IP ranges: %2.',
                [$clientIp ?? 'unknown', implode(', ', $this->allowedIpPatterns)]
            ),
            0,
            WebapiException::HTTP_FORBIDDEN
        );
    }

    private function getClientIp(): ?string
    {
        $clientIp = $this->remoteAddress->getRemoteAddress();

        return is_string($clientIp) && $clientIp !== '' ? $clientIp : null;
    }

    private function isAllowed(string $clientIp): bool
    {
        foreach ($this->allowedIpPatterns as $pattern) {
            if ($this->matchesPattern($clientIp, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matchesPattern(string $clientIp, string $pattern): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return false;
        }

        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('\*', '[0-9]{1,3}', preg_quote($pattern, '/')) . '$/';
            return (bool)preg_match($regex, $clientIp);
        }

        return $clientIp === $pattern;
    }
}
