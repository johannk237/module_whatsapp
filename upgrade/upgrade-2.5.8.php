<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_5_8($module)
{
    $hook_to_remove_id = Hook::getIdByName('advancedPaymentApi');
    if ($hook_to_remove_id) {
        $module->unregisterHook((int) $hook_to_remove_id);
    }

    return true;
}
