<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPv2.1 (or at your option, any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */


/**
 * LogEvent
 *
 * @package Zikula_System_Modules
 * @subpackage SecurityCenter
 */
class SecurityCenter_DBObject_LogEvent extends DBObject
{
    function __construct($init = null, $key = 0, $field = null)
    {

        $this->_objType       = 'sc_logevent';
        $this->_objField      = 'id';
        $this->_objPath       = 'logevent';

        $this->_objJoin[]     = array ('join_table'          =>  'users',
                                       'join_field'          =>  'uname',
                                       'object_field_name'   =>  'username',
                                       'compare_field_table' =>  'uid',
                                       'compare_field_join'  =>  'uid');

        $this->_init($init, $key);
    }
}
