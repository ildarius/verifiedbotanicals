<?php
// Places a guest Interac e-Transfer order through the same service calls the REST guest checkout uses
// (bypasses the reCAPTCHA webapi check, still runs checkout_submit_all_after).
// usage (from the Magento root, with the cPanel PHP used by the `M` wrapper in ~/.bashrc):
//   PHP_INI_SCAN_DIR=/opt/cpanel/ea-php83/root/etc/php.d:$HOME/php-cli.d /opt/cpanel/ea-php83/root/usr/bin/php \
//     -d memory_limit=-1 dev/tools/guest-to-customer/place-test-order.php <email>   -> prints order entity id
// Use an @example.com email so the welcome email never reaches a real inbox.
require dirname(__DIR__, 3) . '/app/bootstrap.php';
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');
$om->get(\Magento\Store\Model\StoreManagerInterface::class)->setCurrentStore('fresh1_en');
$email = $argv[1];
$cart = $om->get(\Magento\Quote\Api\GuestCartManagementInterface::class)->createEmptyCart();
$item = $om->create(\Magento\Quote\Api\Data\CartItemInterface::class)->setSku('RB25')->setQty(1)->setQuoteId($cart);
$om->get(\Magento\Quote\Api\GuestCartItemRepositoryInterface::class)->save($item);
$addr = function () use ($om, $email) {
    return $om->create(\Magento\Quote\Api\Data\AddressInterface::class)->setFirstname('Test')->setLastname('Guestconvert')
        ->setStreet(['123 Test St'])->setCity('Toronto')->setRegionId(74)->setRegionCode('ON')->setPostcode('M5V 2T6')
        ->setCountryId('CA')->setTelephone('4165550100')->setEmail($email);
};
$info = $om->create(\Magento\Checkout\Api\Data\ShippingInformationInterface::class)
    ->setShippingAddress($addr())->setBillingAddress($addr())->setShippingCarrierCode('matrixrate')->setShippingMethodCode('matrixrate_8');
$om->get(\Magento\Checkout\Api\GuestShippingInformationManagementInterface::class)->saveAddressInformation($cart, $info);
$payment = $om->create(\Magento\Quote\Api\Data\PaymentInterface::class)->setMethod('interac_etransfer');
echo $om->get(\Magento\Checkout\Api\GuestPaymentInformationManagementInterface::class)
    ->savePaymentInformationAndPlaceOrder($cart, $email, $payment, $addr()), "\n";
