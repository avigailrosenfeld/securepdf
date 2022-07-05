<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of securepdf
 *
 * @package    mod_securepdf
 * @copyright  2020 Yedidia Klein <yedidia@openapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_securepdf\securepdf;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/classes/securepdf.php');

$coursemoduleid = optional_param('id', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

if ($coursemoduleid > 0) {
    $path = '/mod/securepdf/view.php/' . $coursemoduleid . '/';
    if ($page > 0) {
        $path .= 'page/' . $page . '/';
    }
    redirect(new \moodle_url($path));
}

$base64 = null;
$settings = get_config('securepdf');

// Support for Vue.js Router and its URL structure.
$paths = explode('/', $_SERVER['REQUEST_URI']);
$baseindex = array_search('view.php', $paths);
if ($paths[$baseindex] === 'view.php' && count($paths) > $baseindex + 1) {
    $coursemoduleid = intval($paths[$baseindex + 1]);

    $baseindexpage = array_search('page', $paths);
    if (count($paths) > $baseindexpage + 1 && $paths[$baseindexpage + 1] > 0) {
        $page = intval($paths[$baseindexpage + 1]);
    }
}

list($course, $cm) = get_course_and_cm_from_cmid($coursemoduleid, 'securepdf');
$context = context_module::instance($coursemoduleid);
$securepdf = new securepdf($context, $cm, $course);

require_login($course, true, $cm);
require_capability('mod/securepdf:view', $context);

$PAGE->set_pagelayout('incourse');

$url = new moodle_url('/mod/securepdf/view.php', ['id' => $coursemoduleid]);
$PAGE->set_url($url);

if (!securepdf::check_imagick()) {
    echo $OUTPUT->header();
    echo $OUTPUT->footer();
    die();
}

$title = get_string('modulename', 'mod_securepdf');
$PAGE->set_context($cm->context);
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Update page views in table - in order to be able to set completion.
$pageview = ['module' => $cm->id,
             'userid' => $USER->id,
             'page' => $page
            ];
$exist = $DB->get_record('securepdf_pageviews', $pageview);
if ($exist) {
    $pageview['timemodified'] = time();
    $pageview['id'] = $exist->id;
    $DB->update_record('securepdf_pageviews', $pageview);
} else {
    $pageview['timemodified'] = time();
    $pageview['timecreated'] = time();
    $DB->insert_record('securepdf_pageviews', $pageview);
}

$event = \mod_securepdf\event\page_view::create(array(
    'objectid' => $securepdf->get_instance()->id,
    'context' => context_module::instance($cm->id),
    'other' => $page + 1
));
$event->trigger();

// Use cache if image is cached, instead of parsing the PDF again.
$cache = cache::make('mod_securepdf', 'pages');
$data = $cache->get($cm->id . '_' . $page);
$numpages = $cache->get($cm->id);

// If there is no cache - we should parse the PDF and write cache.
if (!$data || !$numpages) {
    // First call the adhoc task for generating the cache of all pages
    // This situation happen while cache was purged
    // therwise the cache is created on create/update resource.
    $adhoccache = new \mod_securepdf\task\create_cache();
    $adhoccache->set_custom_data(['moduleid' => $cm->id]);
    \core\task\manager::queue_adhoc_task($adhoccache);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_securepdf', 'content', 0, 'sortorder', false);
    foreach ($files as $file) {
        $content = $file->get_content();
    }

    $im = new imagick();
    $im->setResolution($settings->resolution, $settings->resolution);
    try {
        $im->readImageBlob($content);
    } catch (Exception $e) {
        echo $OUTPUT->header();
        \core\notification::error(get_string('imagick_pdf_policy', 'mod_securepdf'));
        echo $e;
        echo $OUTPUT->footer();
        die();
    }
    $numpages = $im->getNumberImages();
    $result = $cache->set($cm->id, $numpages);

    if ($page <= $numpages) {
        $im->setIteratorIndex($page - 1);
        $im->setImageFormat('jpeg');
        $im->setImageAlphaChannel(Imagick::VIRTUALPIXELMETHOD_WHITE);
        $img = $im->getImageBlob();
        $base64 = base64_encode($img);
    } else {
        $error = get_string('nosuchpage', 'mod_securepdf');
    }
    $im->destroy();
} else {
    // Get image from cache.
    $base64 = $data;
}

// Update 'viewed' state if required by completion system.
// It's here and not in top of this file because we need the total number of pages in this PDF.
$completion = new completion_info($course);
// Check if user viewed all pages.
$allpages = $DB->count_records('securepdf_pageviews', ['module' => $cm->id, 'userid' => $USER->id]);
if ($allpages == $numpages) {
    $completion->set_module_viewed($cm);
}

echo $OUTPUT->header();

$pages = [];
for ($i = 0; $i < $numpages; $i++) {
    $pages[$i]['url'] = $CFG->wwwroot . '/mod/securepdf/view.php/' . $coursemoduleid . '/page/' . ($i + 1);
    $pages[$i]['page'] = $i + 1;
}

$next = 0;
if (($page + 1) < $numpages) {
    $next = $page + 1;
}

$nexturl = $CFG->wwwroot . '/mod/securepdf/view.php/' . $coursemoduleid . '/page/' . $next;
$previousurl = $CFG->wwwroot . '/mod/securepdf/view.php/' . $coursemoduleid . '/page/' . ($page - 1);

if ($base64) {
    $PAGE->requires->js_call_amd('mod_securepdf/app-lazy', 'init', [
        'coursemoduleid' => $cm->id,
        'pagecount' => $numpages,
        'initialpage' => $page,
    ]);
} else {
    echo $OUTPUT->heading(get_string('route_not_found', 'mod_securepdf'));
}

// Add VUE front pages.
?>
    <div id="mod-securepdf-app">
        <router-view></router-view>
    </div>
<?php

echo $OUTPUT->footer();

