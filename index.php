<?php

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \mod_rki\event\course_module_instance_list_viewed::create(array(
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/rki/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_rki');
echo $OUTPUT->heading($modulenameplural);

$rkis = get_all_instances_in_course('rki', $course);

if (empty($rkis)) {
    notice(get_string('nonewmodules', 'mod_rki'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($rkis as $rki) {
    if (!$rki->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/rki/view.php', array('id' => $rki->coursemodule)),
            format_string($rki->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/rki/view.php', array('id' => $rki->coursemodule)),
            format_string($rki->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($rki->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
