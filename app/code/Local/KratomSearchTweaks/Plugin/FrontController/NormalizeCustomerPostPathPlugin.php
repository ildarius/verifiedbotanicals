<?php

namespace Local\KratomSearchTweaks\Plugin\FrontController;

use Magento\Framework\App\FrontController;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

class NormalizeCustomerPostPathPlugin
{
    public function beforeDispatch(FrontController $subject, RequestInterface $request): array
    {
        if (!$request instanceof HttpRequest) {
            return [$request];
        }

        if (strtoupper($request->getMethod()) === 'POST') {
            return [$request];
        }

        $path = strtolower(trim((string)$request->getPathInfo(), '/'));
        if (!in_array($path, ['customer/account/createpost', 'customer/account/loginpost'], true)) {
            return [$request];
        }

        $serverMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
        $contentLength = (int)($request->getServer('CONTENT_LENGTH') ?: ($_SERVER['CONTENT_LENGTH'] ?? 0));
        $postValue = $request->getPostValue();
        $hasPostPayload = $serverMethod === 'POST'
            || $contentLength > 0
            || !empty($_POST)
            || !empty($postValue);

        if ($hasPostPayload) {
            $request->setMethod('POST');
        }

        return [$request];
    }
}
