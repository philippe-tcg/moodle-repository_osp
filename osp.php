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
 * This plugin is used to access EJS applications from the OSP collection in compadre
 *
 * @package    repository
 * @subpackage osp
 * @copyright  2013 Luis de la Torre and Ruben Heradio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('LIST_ALL_SIMULATIONS_URL', 'http://www.compadre.org/osp/services/REST/osp_moodle.cfm?');
define('SEARCH_URL', 'http://www.compadre.org/osp/services/REST/search_v1_02.cfm?verb=Search&');
define('OSP_THUMBS_PER_PAGE', 10);

class osp {

    private $java_words = array('java', 'jar', 'ejs');
    private $javascript_words = array('javascript', 'js', 'zip', 'ejss');
    private $java_in_keywords = false;
    private $javascript_in_keywords = false;

    function load_xml_file($url, $choice) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $xml = simplexml_load_string(curl_exec($ch));
        curl_close($ch);
        if ($choice == LIST_ALL_SIMULATIONS_URL) {
            return $xml->Results->records;
        } else { // choice == SEARCH_URL
            return $xml->Search->records;
        }
    } //load_xml_file

    function process_record($record){

        /////////////////////////////////////////////////////
        // Remark: a record may include 0..n simulations
        /////////////////////////////////////////////////////

        $result = array();
        $record_as_array = (array) $record;

        // <information common to all the simulations of the record>

        $result['common_information'] = array();

        // author
        $seeker = (array) $record->contributors;
        $author = $seeker['contributor'];
        if (is_array($author)) {
            $author = implode(', ', $author);
        }
        $result['common_information']['author'] = $author;// . $description;

        // date
        $date = $record_as_array['oai-datestamp'];
        $date = preg_replace('/Z/', '', $date);
        $result['common_information']['date'] = strtotime($date);

        // thumbnail
        $result['common_information']['thumbnail'] = (string) $record->{'thumbnail-url'};

        // license
        $result['common_information']['license'] = 'cc-sa';

        // <\information common to all the simulations of the record>

        // <information specific for each simulation of the record>

        $result['simulations'] = array();
        $simulation = array();
        $seeker = $record->{'attached-document'};
        foreach ($seeker as $value) {
            if (is_object($value)) {
                $filename = (string) $value->{'file-name'};
                $filetype = (string) $value->{'file-type'};
                $xml_title = (string) $value->{'title'};
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if (  ( (($extension == 'jar') && $this->java_in_keywords && preg_match('/^ejs_/i', $filename)) ||
                        (($extension == 'zip') && $this->javascript_in_keywords && preg_match('/^ejss_/i', $filename)))
                    && ($filetype == 'Main')
                    && ($xml_title != 'Easy Java Simulations Modeling and Authoring Tool')  ) {

                    // filename title
                    $simulation['title'] = $filename;

                    // source
                    $source = (string) $value->{'access-url'};
                    $simulation['source'] = $source . '&EJSMoodleApp=1';

                    // shorttitle
                    $description = (string) $record->{'description'};
                    $description = preg_replace('/<.+?>/', '', $description);
                    $simulation['shorttitle'] = '`' . $filename . '´: ' . $description;

                    // size
                    $seeker_aux = (array) $value->{'file-name'};
                    $seeker_aux = $seeker_aux['@attributes'];
                    $size = $seeker_aux['file-size'];
                    $simulation['size'] = $size;

                    $result['simulations'][] = $simulation;
                }
            }
        } //foreach

        // <\information specific for each simulation of the record>

        return $result;
    } // process_record


    public function format_keywords($keywords) {

        // < Let's see if java/javascript simulations have to be filtered... >

        // java ?
        $this->java_in_keywords = false;
        $found = false;
        $i = 0;
        $size = sizeof($this->java_words);
        while ( (!($this->java_in_keywords)) && ($i<$size) ) {
            if ( preg_match('/\b' . $this->java_words[$i] . '\b/i', $keywords) ) {
                $this->java_in_keywords = true;
                foreach ( $this->java_words as $java_word) {
                    $keywords = preg_replace('/\b' . $java_word . '\b/i', '', $keywords);
                }
            } else {
                $i++;
            }
        }

        // javascript ?
        $this->javascript_in_keywords = false;
        $found = false;
        $i = 0;
        $size = sizeof($this->javascript_words);
        while ( (!($this->javascript_in_keywords)) && ($i<$size) ) {
            if ( preg_match('/\b' . $this->javascript_words[$i] . '\b/i', $keywords) ) {
                $this->javascript_in_keywords = true;
                foreach ( $this->javascript_words as $javascript_word) {
                    $keywords = preg_replace('/\b' . $javascript_word . '\b/i', '', $keywords);
                }
            } else {
                $i++;
            }
        }

        // by default, no filter is used
        if ( !( $this->java_in_keywords) && !($this->javascript_in_keywords) ) {
            $this->java_in_keywords = true;
            $this->javascript_in_keywords = true;
        }

        // <\ Let's see if java/javascript simulations have to be filtered... >


        $keywords=trim($keywords);
        if ( ($keywords == '') || ($keywords == 'Search') || (strtoupper($keywords) == 'ALL') ){
           $keywords = '*';
        } else {
            // making possible conjunctive boolean searches (a&b&...)
            $keywords=preg_replace('/\s+/', '+', $keywords);
        }

        return $keywords;
    } //format_keywords

    public function search_simulations($keywords, $page) {

        // get $type_of_simulation_to_be_retrieved
        $type_of_simulation_to_be_retrieved = null;
        if ($this->java_in_keywords && $this->javascript_in_keywords) {
            $type_of_simulation_to_be_retrieved = 'EJS+EJSS';
        } elseif ($this->java_in_keywords && !$this->javascript_in_keywords) {
            $type_of_simulation_to_be_retrieved = 'EJS';
        } else {
            $type_of_simulation_to_be_retrieved = 'EJSS';
        }


        // get skip OSP parameter from $page
        $skip = OSP_THUMBS_PER_PAGE * ($page);

        // get records from compadre that fulfill the keywords
        if ($keywords == '*') { // list all simulations
            $records= $this->load_xml_file(LIST_ALL_SIMULATIONS_URL . 'skip=' . $skip .
                '&OSPType=' . $type_of_simulation_to_be_retrieved .
                '&max=' . OSP_THUMBS_PER_PAGE, LIST_ALL_SIMULATIONS_URL);
        } else { // search with a keyword
            $records = $this->load_xml_file(SEARCH_URL . 'Skip=' . $skip .
                '&OSPType=' . $type_of_simulation_to_be_retrieved .
                '&Max=' .
                OSP_THUMBS_PER_PAGE .'&q=' . $keywords, SEARCH_URL);
        }

        $file_list = array();
        if (isset($records->record)) {
            foreach($records->record as $record){
                $processed_record = $this->process_record($record);
                if (!empty($processed_record['simulations'])) {
                    foreach ($processed_record['simulations'] as $simulation) {
                        $list_item = array();
                        $list_item['author'] = $processed_record['common_information']['author'];
                        $list_item['date'] =  $processed_record['common_information']['date'];
                        $list_item['thumbnail'] =  $processed_record['common_information']['thumbnail'];
                        $list_item['license'] =  $processed_record['common_information']['license'];
                        $list_item['title'] =  $simulation['title'];
                        $list_item['source'] =  $simulation['source'];
                        $list_item['shorttitle'] =  $simulation['shorttitle'];
                        $list_item['size'] =  $simulation['size'];
                        $file_list[] = $list_item;
                    }
                }
            }
        }

        return $file_list;
    } //search_simulations

} //class osp