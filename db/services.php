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

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'mod_securepdf_get_pages' => [
        'classname' => 'mod_securepdf\external\get_pages',
        'methodname' => 'request',
        'description' => 'Get pages of the pdf.',
        'type' => 'read',
        'ajax' => true,
    ],
);
