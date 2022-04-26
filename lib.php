<?php

defined('MOODLE_INTERNAL') || die();

function rki_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

function rki_add_instance($moduleinstance, $mform = null)
{
    global $DB;
    $moduleinstance->timecreated = time();
    return $DB->insert_record('rki', $moduleinstance);
}

function rki_update_instance($moduleinstance, $mform = null)
{
    global $DB;
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    return $DB->update_record('rki', $moduleinstance);
}

function rki_delete_instance($id)
{
    global $DB;

    $exists = $DB->get_record('rki', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('rki', array('id' => $id));
    return true;
}
