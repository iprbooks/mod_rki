<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');


class mod_rki_mod_form extends moodleform_mod
{

    public function definition()
    {
        global $PAGE, $USER, $CFG;

        $mform = $this->_form;
        $settings = get_config("rki");
        if (isset($settings->token) && !empty($settings->token)) {
            $_SESSION['token'] = $settings->token;
        } else if (isset($USER->profile['mod_rki_token']) && !empty($USER->profile['mod_rki_token'])) {
            $_SESSION['token'] = $USER->profile['mod_rki_token'];
        }

        $PAGE->requires->js_call_amd('mod_rki/modal_search_handle', 'init');

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('rkiname', 'mod_rki'), array('size' => '64'));

        $mform->addElement('hidden', 'content', '');
        $mform->setType('content', PARAM_TEXT);
        $mform->addRule('content', get_string('content_error', 'mod_rki'), 'required', null, 'client');

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'rkiname', 'mod_rki');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('button', 'modal_show_button', get_string('button_desc', 'mod_rki'));
        $mform->addElement('text', 'content_name', 'Выбранное издание', ['style' => 'width:100%']);
        $mform->setType('content_name', PARAM_TEXT);
        $mform->addRule('content_name', null, 'required', null, 'client');

        $mform->addElement('text', 'page_start', 'Страница c');
        $mform->setType('page_start', PARAM_INT);
        $mform->addRule('page_start', null, 'numeric', null, 'client');

        $mform->addElement('text', 'page_end', 'Страница по');
        $mform->setType('page_end', PARAM_INT);
        $mform->addRule('page_end', null, 'numeric', null, 'client');

        $mform->addHelpButton('modal_show_button', 'rkibutton', 'mod_rki');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
