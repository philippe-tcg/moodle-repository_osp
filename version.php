<?php
// This file is part of the Moodle repository plugin "OSP"
//
// OSP is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// OSP is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License is available on <http://www.gnu.org/licenses/>
//
// OSP has been developed by:
//	- Ruben Heradio: rheradio@issi.uned.es
//  - Luis de la Torre: ldelatorre@dia.uned.es
//
//  at the Universidad Nacional de Educacion a Distancia, Madrid, Spain



/**
 * Version file for the osp repository plugin
 *
 * @package    repository
 * @subpackage osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015061600;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2013111800;        // Requires this Moodle version
$plugin->cron = 0;
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.3 (Build: 2015061600)';
$plugin->component = 'repository_osp';  // Full name of the plugin (used for diagnostics)
