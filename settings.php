<?php

defined('MOODLE_INTERNAL') || die();
global $ADMIN;

if ($ADMIN->fulltree) {
    global $PAGE;

    $settings->add(new admin_setting_configtext('rki/user_id',
        get_string('rki:user_id', 'mod_rki'),
        get_string('rki:user_id_descr', 'mod_rki'), null, PARAM_INT));

    $settings->add(new admin_setting_configtext('rki/token',
        get_string('rki:token', 'mod_rki'),
        get_string('rki:token_desc', 'mod_rki'), ''));

}
