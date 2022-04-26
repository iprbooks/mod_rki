<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/rki:get_tree' => array(
        'riskbitmask'  => RISK_SPAM | RISK_PERSONAL,
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
    )),
    'mod/rki:addinstance' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
    'clonepermissionsfrom' => 'moodle/course:manageactivities'
    )
);