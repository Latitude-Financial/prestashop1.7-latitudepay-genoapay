<?php
class latitude_officialreturnModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;

    /**
     * @var integer - Order state
     */
    const PAYMENT_ACCEPECTED = 2;
    const PAYMENT_ERROR = 8;

    const PAYMENT_SUCCESS_STATES = [
        'COMPLETED'
    ];

    const PAYMENT_FAILED_STATES = [
        'UNKNOWN',
        'FAILED'
    ];

    /**
     * [initContent description]
     * @return [type]
     */
    public function initContent()
    {
        parent::initContent();
        // Add the validation
        $reference = Tools::getValue('reference');
        $currencyCode = $this->context->currency->iso_code;
        $gatewayName = $this->module->getPaymentGatewayNameByCurrencyCode($currencyCode);

        if (Configuration::get(Latitude_Official::LATITUDE_FINANCE_DEBUG_MODE)) {
            $logMessage = "======CALLBACK INFO STARTS======\n";
            $logMessage .= json_encode(Tools::getAllValues(), JSON_PRETTY_PRINT);
            $logMessage .= "\n======CALLBACK INFO ENDS======";
            BinaryPay::log($logMessage, true, 'latitudepay-finance-' . date('Y-m-d') . '.log');
        }

        if (!$this->context->cookie->reference || $this->context->cookie->reference !== $reference) {
            Tools::redirect(Context::getContext()->shop->getBaseURL(true));
        }

        $cart = $this->context->cart;
        $responseState = Tools::getValue('result');

        // Verify payment token
        $token = $this->context->cookie->payment_token;
        $this->context->cookie->payment_token = null;
        if ($token !== Tools::getValue('token')) {
            $this->errors[] = Context::getContext()->getTranslator()->trans("Invalid payment token.");
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Verify signature
        if (!$this->validateSignature($gatewayName)) {
            $this->errors[] = Context::getContext()->getTranslator()->trans("Invalid response signature.");
            Tools::redirect('index.php?controller=order&step=1');
        }

        // success
        if (in_array($responseState, self::PAYMENT_SUCCESS_STATES)) {
            $verifyCartAmount = floatval($this->context->cookie->__get('cart_amount'));
            if (!$verifyCartAmount) {
                $this->context->cookie->__set('latitude_finance_redirect_error', $this->translateErrorMessage("Your order was placed already."));
                Tools::redirect('index.php?controller=order&step=1');
            }
            $this->context->cookie->__unset('cart_amount');
            $orderAmount = $cart->getOrderTotal();
            $this->module->validateOrder(
                $cart->id,
                $verifyCartAmount< $orderAmount ? self::PAYMENT_ERROR : self::PAYMENT_ACCEPECTED,
                $verifyCartAmount,
                $gatewayName,
                $verifyCartAmount < $orderAmount ?
                    sprintf(
                        'Invalid payment amount detected! Correct amount: %s, paid: %s',
                        $verifyCartAmount,
                        $orderAmount
                    ) : '',
                [ 'transaction_id' => Tools::getValue('token') ]
            );
        } else {
            $this->errors[] = Context::getContext()->getTranslator()
                ->trans("Your purchase order has been cancelled.");
            Tools::redirect('index.php?controller=cart&action=show');
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        Tools::redirect('index.php?controller=order-confirmation&id_cart='. (int)$cart->id. '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder. '&key=' . $customer->secure_key);
    }

    /**
     * translateErrorMessage
     * @param  string $message
     * @return string
     */
    protected function translateErrorMessage($message)
    {
        switch ($message) {
            case 'The customer cancelled the purchase.':
                $message =  'Your purchase order has been cancelled.';
                break;
            default:
                // do nothing
                break;
        }
        return $message;
    }

    /**
     * Check if response signature is valid
     * @param $gatewayName
     * @param $request
     * @return bool
     */
    private function validateSignature($gatewayName)
    {
        /**
         * @var BinaryPay $gateway
         */
        $gateway = $this->module->getGateway($gatewayName);
        $gluedString = $gateway->recursiveImplode(
            array(
                'token' => Tools::getValue('token'),
                'reference' => $this->context->cookie->reference,
                'message' => Tools::getValue('message'),
                'result' => Tools::getValue('result'),
            ),
            '',
            true
        );
        $signature = hash_hmac( 'sha256', base64_encode( $gluedString ), $gateway->getConfig( 'password' ) );
        return $signature === Tools::getValue('signature');
    }

    private function recreatCart($order) {
        $this->context->cart = new Cart($order->id_cart);
        $this->context->cookie->id_cart = $order->id_cart;
    }

}
