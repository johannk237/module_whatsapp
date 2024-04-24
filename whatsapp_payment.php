<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Whatsapp_Payment extends PaymentModule
{
    private $_html = '';
    private $_postErrors = [];

    public $manager_name;
    public $whatsapp_number;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'whatsapp_payment';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.5';
        $this->author = 'Johann';
        $this->controllers = ['payment', 'validation'];

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(['CHEQUE_NAME', 'CHEQUE_ADDRESS']);
        if (isset($config['CHEQUE_NAME'])) {
            $this->manager_name = $config['CHEQUE_NAME'];
        }
        if (isset($config['CHEQUE_ADDRESS'])) {
            $this->whatsapp_number = $config['CHEQUE_ADDRESS'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Whatsapp Payment', [], 'Modules.WhatsappPayment.Admin');
        $this->description = $this->trans('This module allows you to continue your payments by using whatsapp.', [], 'Modules.WhatsappPayment.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete these details?', [], 'Modules.WhatsappPayment.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        if ((!isset($this->manager_name) || !isset($this->whatsapp_number) || empty($this->manager_name) || empty($this->whatsapp_number))) {
            $this->warning = $this->trans('The "Manager Name" and "Whatsapp Number" fields must be configured before using this module.', [], 'Modules.WhatsappPayment.Admin');
        }

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', [], 'Modules.WhatsappPayment.Admin');
        }

        $this->extra_mail_vars = [
            '{manager_name}' => Configuration::get('CHEQUE_NAME'),
            '{whatsapp_number}' => Configuration::get('CHEQUE_ADDRESS'),
            '{whatsapp_number_html}' => Tools::nl2br(Configuration::get('CHEQUE_ADDRESS')),
        ];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
        ;
    }

    public function uninstall()
    {
        return Configuration::deleteByName('CHEQUE_NAME')
            && Configuration::deleteByName('CHEQUE_ADDRESS')
            && parent::uninstall()
        ;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('CHEQUE_NAME')) {
                $this->_postErrors[] = $this->trans('The "Manager Name" field is required.', [], 'Modules.WhatsappPayment.Admin');
            } elseif (!Tools::getValue('CHEQUE_ADDRESS')) {
                $this->_postErrors[] = $this->trans('The "Whatsapp Number" field is required.', [], 'Modules.WhatsappPayment.Admin');
            }
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('CHEQUE_NAME', Tools::getValue('CHEQUE_NAME'));
            Configuration::updateValue('CHEQUE_ADDRESS', Tools::getValue('CHEQUE_ADDRESS'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Notifications.Success'));
    }

    private function _displayCheck()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    public function getContent()
    {
        $this->_html = '';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars()
        );

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pay by Whatsapp', [], 'Modules.WhatsappPayment.Admin'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
                ->setAdditionalInformation($this->fetch('module:whatsapp_payment/views/templates/front/payment_infos.tpl'));

        return [$newOption];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['order']->getCurrentState();
        $rest_to_paid = $params['order']->getOrdersTotalPaid() - $params['order']->getTotalPaid();
        if (in_array($state, [Configuration::get('PS_OS_CHEQUE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')])) {
            $this->smarty->assign([
                'total_to_pay' => Tools::displayPrice(
                    $rest_to_paid,
                    new Currency($params['order']->id_currency),
                    false
                ),
                'shop_name' => $this->context->shop->name,
                'manager_name' => $this->manager_name,
                'checkAddress' => $this->whatsapp_number,
                'status' => 'ok',
                'id_order' => $params['order']->id,
            ]);
            if (isset($params['order']->reference) && !empty($params['order']->reference)) {
                $this->smarty->assign('reference', $params['order']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }

        return $this->fetch('module:whatsapp_payment/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int) ($cart->id_currency));
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Contact details', [], 'Modules.WhatsappPayment.Admin'),
                    'icon' => 'icon-envelope',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Manager Name', [], 'Modules.WhatsappPayment.Admin'),
                        'name' => 'CHEQUE_NAME',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Whatsapp Number', [], 'Modules.WhatsappPayment.Admin'),
                        'desc' => $this->trans('Number where the customer should be redirect to.', [], 'Modules.WhatsappPayment.Admin'),
                        'name' => 'CHEQUE_ADDRESS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'CHEQUE_NAME' => Tools::getValue('CHEQUE_NAME', Configuration::get('CHEQUE_NAME')),
            'CHEQUE_ADDRESS' => Tools::getValue('CHEQUE_ADDRESS', Configuration::get('CHEQUE_ADDRESS')),
        ];
    }

    public function getTemplateVars()
    {
        $cart = $this->context->cart;
        $total = $this->trans(
            '%amount% (tax incl.)',
            [
                '%amount%' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH)),
            ],
            'Modules.WhatsappPayment.Admin'
        );

        $manager_name = Configuration::get('CHEQUE_NAME');
        if (!$manager_name) {
            $manager_name = '___________';
        }

        $whatsapp_number = Tools::nl2br(Configuration::get('CHEQUE_ADDRESS'));
        if (!$whatsapp_number) {
            $whatsapp_number = '___________';
        }

        return [
            'checkTotal' => $total,
            'checkOrder' => $manager_name,
            'checkAddress' => $whatsapp_number,
        ];
    }
}
