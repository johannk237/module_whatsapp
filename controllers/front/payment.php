<?php
class whatsapp_paymentPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        if (!($this->module instanceof Ps_Checkpayment)) {
            Tools::redirect('index.php?controller=order');

            return;
        }
        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');

            return;
        }

        $this->context->smarty->assign([
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'isoCode' => $this->context->language->iso_code,
            'manager_name' => $this->module->manager_name,
            'checkAddress' => Tools::nl2br($this->module->whatsapp_number),
            'this_path' => $this->module->getPathUri(),
            'this_path_check' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
        ]);

        $this->setTemplate('payment_execution.tpl');
    }
}
