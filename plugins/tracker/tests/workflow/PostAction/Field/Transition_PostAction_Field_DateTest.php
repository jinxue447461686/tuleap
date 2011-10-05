<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(dirname(__FILE__).'/../../../../include/workflow/PostAction/Field/Transition_PostAction_Field_Date.class.php');

class Transition_PostAction_Field_DateTest extends UnitTestCase {
    
    public function testBeforeShouldSetTheDate() {
        $fields_data = array('field_id' => 'value');
        $field_id   = 102;
        $value_type = Transition_PostAction_Field_Date::FILL_CURRENT_TIME;
        $post_action = new Transition_PostAction_Field_Date($field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual($_SERVER['REQUEST_TIME'], $fields_data[$field_id]);
    }
    
    public function testBeforeShouldClearTheDate() {
        $field_id   = 102;
        $fields_data = array(
            'field_id' => 'value',
            $field_id  => '1317817376',
        );
        $value_type = Transition_PostAction_Field_Date::CLEAR_DATE;
        $post_action = new Transition_PostAction_Field_Date($field_id, $value_type);
        $post_action->before($fields_data);
        $this->assertEqual('', $fields_data[$field_id]);
    }
}
?>
