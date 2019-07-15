<?php
/**
 * Copyright (c) 2019. All rights reserved.
 * @author: Sviatoslav Lashkiv
 * @mail:   ss.lashkiv@gmail.com
 * @github: https://github.com/sviatoslav-lashkiv
 */

class Magecloud_PaymentFailedEmail_Helper_Data extends Mage_Checkout_Helper_Data
{

    /**
     * Send email id payment was failed
     *
     * @param Mage_Sales_Model_Quote $checkout
     * @param string $message
     * @param string $checkoutType
     * @return Mage_Checkout_Helper_Data
     */
    public function sendPaymentFailedEmail($checkout, $message, $checkoutType = 'onepage')
    {
        // Start rewriting
        $enable_sending_failed_payment_email = Mage::getStoreConfig('checkout/payment_failed/enable_sending_failed_payment_email', $checkout->getStoreId());
        if (!$enable_sending_failed_payment_email) {
            return $this;
        }
        // End rewriting

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = Mage::getStoreConfig('checkout/payment_failed/template', $checkout->getStoreId());

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', $checkout->getStoreId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method', $checkout->getStoreId());
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever', $checkout->getStoreId());
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/email', $checkout->getStoreId()),
                'name'  => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/name', $checkout->getStoreId())
            )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name'  => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingAddress()->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getAllVisibleItems() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x '. $_item->getQty() . '  '
                . $checkout->getStoreCurrencyCode() . ' '
                . $_item->getProduct()->getFinalPrice($_item->getQty()) . "\n";
        }
        $total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();

        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason'          => $message,
                        'checkoutType'    => $checkoutType,
                        'dateAndTime'     => Mage::app()->getLocale()->date(),
                        'customer'        => Mage::helper('customer')->getFullCustomerName($checkout),
                        'customerEmail'   => $checkout->getCustomerEmail(),
                        'billingAddress'  => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod'  => Mage::getStoreConfig('carriers/' . $shippingMethod . '/title'),
                        'paymentMethod'   => Mage::getStoreConfig('payment/' . $paymentMethod . '/title'),
                        'items'           => nl2br($items),
                        'total'           => $total,
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

}