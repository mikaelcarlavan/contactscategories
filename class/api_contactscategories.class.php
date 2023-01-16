<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

/**
 * \file    class/api_contactscategory.class.php
 * \ingroup contactscategories
 * \brief   File for API management of contactscategory.
 */
dol_include_once('/contactscategories/class/contactscategory.class.php');

/**
 * API class for contactscategories contactscategory
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ContactsCategories extends DolibarrApi
{


    /**
     * @var ContactsCategory $contactscategory {@type ContactsCategory}
     */
    public $contactscategory;

    /**
     * Constructor
     *
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->contactscategory = new ContactsCategory($this->db);
    }


    /**
     * Get properties of a contactscategory object
     *
     * Return an array with contactscategory informations
     *
     * @param 	int 	$id ID of contactscategory
     * @return 	array|mixed data without useless information
	 *
     * @throws 	RestException
     */
    function get($id)
    {

		if(! DolibarrApiAccess::$user->rights->contactscategories->read) {
			throw new RestException(401);
		}

        
        $result = $this->contactscategory->contacts($id);
        if( ! $result ) {
            throw new RestException(404, 'ContactsCategory not found');
        }

        $contacts = $this->contactscategory->contacts;
        /*if( ! DolibarrApi::_checkAccessToResource('contactscategory', $this->contactscategory->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }*/
        $data = array();

        if (sizeof($contacts))
        {
            foreach ($contacts as $contact)
            {
                $data[] = $this->_cleanObjectDatas($contact);
            }
        }

        
		return $data;
    }

  
}
