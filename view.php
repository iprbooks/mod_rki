<?php

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once('externallib.php');

global $USER, $PAGE, $DB, $OUTPUT;
// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$l = optional_param('l', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('rki', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('rki', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($l) {
    $moduleinstance = $DB->get_record('rki', array('id' => $l), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rki', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_rki'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_rki\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('rki', $moduleinstance);
$event->trigger();


$settings = get_config("rki");
if (isset($settings->token) && !empty($settings->token)) {
    $_SESSION['subscriberToken'] = $settings->token;
} else if (isset($USER->profile['mod_rki_token']) && !empty($USER->profile['mod_rki_token'])) {
    $_SESSION['subscriberToken'] = $USER->profile['mod_rki_token'];
}

$PAGE->set_url('/mod/rki/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

$dataBook = mod_rki_external::exec("/books/" . $moduleinstance->content . "/get", array());
$dataBook = json_decode($dataBook, true);
if (array_key_exists('success', $dataBook) && $dataBook['success'] == false) {
    echo "<h2 style=\"text-align: center;\">Ошибка: " . $dataBook['message'] . "<br/> Обратитесь в службу технической поддержки</h2>";
    return;
}
$book = $dataBook['data'];

$params = array(
    "email" => $USER->email,
    "fullname" => "",
    "user_type" => 1,
    "publication_id" => $moduleinstance->content,
    "open_method" => "iframe",
    "page_id" => $moduleinstance->page_start,
);
$dataUrl = mod_rki_external::exec("/security/generateAutoAuthUrl", $params);
$dataUrl = json_decode($dataUrl, true);
$url = $dataUrl['data'];

$comment = "</br>";
if ($moduleinstance->page_start > 0 && $moduleinstance->page_end > 0) {
    $comment .= '<span><strong>Комментарий: </strong> Читать с ' . $moduleinstance->page_start . ' по ' . $moduleinstance->page_end . ' стр.</span><br>';
} elseif ($moduleinstance->page_start > 0) {
    $comment .= '<span><strong>Комментарий: </strong> Читать с ' . $moduleinstance->page_start . ' стр.</span><br>';
}

echo
    '<div class="row">
        <div class="col-sm-2">
            <img src="https://www.ros-edu.ru/' . $book['image'] . '" class="img-responsive thumbnail" alt="">
            <a style="color:#a53436;background-color:white;border-color:#a53436;width:140px" class="btn mt-3" href=' . $url . ' target="_blank">Читать</a>
        </div>
        <div class="col-sm-10"> 
            <span class="book_title"><strong>Название: </strong> ' . $book['title'] . '</span><br>
            <span><strong>Авторы: </strong> ' . $book['authors'] . '</span><br>
            <span><strong>Издательство: </strong>' . $book['pubhouses'] . '</span><br>
            <span><strong>Год издания: </strong>' . $book['year'] . '</span><br>
            <span><strong>Тип издания: </strong>' . $book['longtitle'] . '</span><br>
            <span><strong>ISBN: </strong>' . $book['isbn'] . '</span><br>
            <span><strong>Описание: </strong>' . $book['description'] . '</span><br>' . $comment .
    '</div>
    </div>';

echo $OUTPUT->footer();
