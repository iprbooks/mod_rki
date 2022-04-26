<?php

$services = array(
    'lab_rkiservice' => array(
        'functions' => array('mod_rki_search_books', 'mod_rki_book_read_url', 'mod_rki_category_tree', 'mod_rki_level_tree'),
        'requiredcapability' => ['mod_rki:get_tree'],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'rkiIntegration',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ),
);

$functions = array(
    'mod_rki_search_books' => array(
        'classname' => 'mod_rki_external',
        'methodname' => 'search_books',
        'classpath' => 'mod/rki/externallib.php',
        'description' => 'Получение списка книг',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('rki:get_tree')
    ),
    'mod_rki_book_read_url' => array(
        'classname' => 'mod_rki_external',
        'methodname' => 'book_read_url',
        'classpath' => 'mod/rki/externallib.php',
        'description' => 'Получение ссылки на книгу',
        'type' => 'read',
        'ajax' => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('rki:get_tree')
    ),
    'mod_rki_category_tree' => array(
        'classname' => 'mod_rki_external',
        'methodname' => 'category_tree',
        'classpath' => 'mod/rki/externallib.php',
        'description' => 'Получение дерева категорий',
        'type' => 'read',
        'ajax' => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('rki:get_tree'),
    ),
    'mod_rki_level_tree' => array(
        'classname' => 'mod_rki_external',
        'methodname' => 'level_tree',
        'classpath' => 'mod/rki/externallib.php',
        'description' => 'Получение дерева уровней',
        'type' => 'read',
        'ajax' => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('rki:get_tree'),
    ),
);