<?php

declare(strict_types=1);

namespace Local\GuestToCustomer\Controller\Account;

use Local\GuestToCustomer\Model\NewAccountSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Math\Random;
use Psr\Log\LoggerInterface;

/**
 * Success-page "Set your password" form (AJAX). Form key is checked by Magento's CSRF validator.
 * Only works for the account this checkout session just created (see NewAccountSession).
 */
class SetPassword implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly CustomerSession $customerSession,
        private readonly NewAccountSession $newAccountSession,
        private readonly AccountManagement $accountManagement,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Random $random,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Json
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $customerId = $order->getId() && !$this->customerSession->isLoggedIn()
            ? $this->newAccountSession->getEligibleCustomerId($order)
            : null;
        if ($customerId === null) {
            return $this->error(
                __('This form has expired. Use "Forgot Your Password?" on the sign-in page to set your password.')
            );
        }

        $password = (string)$this->request->getParam('password');
        if ($password === '' || $password !== (string)$this->request->getParam('password_confirmation')) {
            return $this->error(__('Please make sure your passwords match.'));
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            // Same path as the emailed "set your password" link: issue a token and redeem it at once.
            // resetPassword() enforces the store's password rules and invalidates the emailed link.
            $token = $this->random->getUniqueHash();
            $this->accountManagement->changeResetPasswordLinkToken($customer, $token);
            $this->accountManagement->resetPassword($customer->getEmail(), $token, $password);
        } catch (InputException $exception) {
            return $this->error($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('GuestToCustomer: could not set password for customer %d', $customerId),
                ['exception' => $exception]
            );
            return $this->error(__('Something went wrong. Please try again.'));
        }

        $this->newAccountSession->forget();
        $this->customerSession->setCustomerDataAsLoggedIn($this->customerRepository->getById($customerId));
        $this->customerSession->regenerateId();

        return $this->jsonFactory->create()->setData(['success' => true]);
    }

    private function error(string|\Stringable $message): Json
    {
        return $this->jsonFactory->create()->setData(['success' => false, 'message' => (string)$message]);
    }
}
