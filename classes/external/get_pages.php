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
 * Securepdf external functions and service definitions.
 *
 * @package    mod_securepdf
 * @category   external
 * @copyright  2022 Avigail Rosenfeld
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.11
 */

namespace mod_securepdf\external;

use coding_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use restricted_context_exception;

/**
 * Class get_pages
 *
 * @package    mod_securepdf\external
 * @copyright  2022 Yedidia Klein <yedidia@openapp.co.il>
 * @since      Moodle 3.1
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class get_pages extends external_api {

    /**
     * Definition of parameters for {@see request}.
     *
     * @return external_function_parameters
     */
    public static function request_parameters() {
        return new external_function_parameters([
            'coursemoduleid' => new external_value(PARAM_INT, 'course module id'),
            'num_page' => new external_value(PARAM_INT, 'num of selected page'),
        ]);
    }

    /**
     * Definition of return type for {@see request}.
     *
     * @return external_multiple_structure
     */
    public static function request_returns() {
        return null;
    }

    /**
     * Gets the pages which are available for this PDF.
     *
     * @param int $coursemoduleid
     * @param int $numpage
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function request($coursemoduleid, $numpage = 1) {

        global $PAGE, $USER, $DB;
        require_once(dirname(__FILE__) . '/../../../../config.php');

        $params = ['coursemoduleid' => $coursemoduleid, 'num_page' => $numpage];
        self::validate_parameters(self::request_parameters(), $params);

        $settings = get_config('securepdf');

        // Load context.
        list($course, $cm) = get_course_and_cm_from_cmid($coursemoduleid, 'securepdf');
        self::validate_context($cm->context);
        $context = $cm->context;
        $page = $numpage;
        $securepdf = new \mod_securepdf\securepdf($context, $cm, $course);

        require_login($course, true, $cm);
        require_capability('mod/securepdf:view', $context);

        if (!$securepdf->check_imagick()) {
            die();
        }
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
            'context' => $context,
            'other' => $page + 1
        ));
        $event->trigger();

        // Use cache if image is cached, instead of parsing the PDF again.
        $cache = \cache::make('mod_securepdf', 'pages');
        $data = $cache->get($cm->id . '_' . $page);
        $numpages = $cache->get($cm->id);

        // If there is no cache - we should parse the PDF and write cache.
        if (!$data || !$numpages) {
            // First call the adhoc task for generating the cache of all pages
            // This situation happen while cache was purged
            // otherwise the cache is created on create/update resource.
            $adhoccache = new \mod_securepdf\task\create_cache();
            $adhoccache->set_custom_data(['moduleid' => $cm->id]);
            \core\task\manager::queue_adhoc_task($adhoccache);

            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_securepdf', 'content', 0, 'sortorder', false);
            foreach ($files as $file) {
                $content = $file->get_content();
            }

            $im = new \imagick();
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
                $im->setImageAlphaChannel(\Imagick::VIRTUALPIXELMETHOD_WHITE);
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
        $completion = new \completion_info($course);
        // Check if user viewed all pages.
        $allpages = $DB->count_records('securepdf_pageviews', ['module' => $cm->id, 'userid' => $USER->id]);
        if ($allpages == $numpages) {
            $completion->set_module_viewed($cm);
        }

        $result = [
            'coursemoduleid' => $cm->id,
            'pagecount' => $numpages,
            'initialpage' => $page,
            'base64' => $base64,
        ];
        return $result;
    }
}
