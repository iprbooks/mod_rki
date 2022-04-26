<?php

namespace mod_rki\event;

defined('MOODLE_INTERNAL') || die();


class course_module_viewed extends \core\event\course_module_viewed {

    protected function init() {
        $this->data['objecttable'] = 'rki';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_objectid_mapping() {
        return array('db' => 'rki', 'restore' => 'rki');
    }
}
