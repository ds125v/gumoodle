<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 2009 onwards  E-Learn Design Limited                    //
// http://www.e-learndesign.co.uk
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

$settings->add(new admin_setting_configtext('block_wiforms_email', get_string('email', 'block_wiforms'),
                   get_string('configemail', 'block_wiforms'), '', PARAM_CLEAN));

$settings->add(new admin_setting_configtext('block_wiforms_subject', get_string('subject', 'block_wiforms'),
                   get_string('configsubject', 'block_wiforms'), get_string('defaultsubject','block_wiforms'), PARAM_CLEAN));
