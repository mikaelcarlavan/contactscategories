<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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
 * \file    class/api_societescategory.class.php
 * \ingroup contactscategories
 * \brief   File for API management of societescategory.
 */
dol_include_once('/contactscategories/class/societescategory.class.php');

/**
 * API class for contactscategories societescategory
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SocietesCategories extends DolibarrApi
{


    /**
     * @var societescategory $societescategory {@type societescategory}
     */
    public $societescategory;

    /**
     * Constructor
     *
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->societescategory = new SocietesCategory($this->db);
    }


    /**
     * Get properties of a societescategory object
     *
     * Return an array with societescategory informations
     *
     * @param 	int 	$id ID of societescategory
     * @return 	array|mixed data without useless information
	 *
     * @throws 	RestException
     */
    function get($id)
    {

		if(! DolibarrApiAccess::$user->rights->contactscategories->read) {
			throw new RestException(401);
		}

        
        $result = $this->societescategory->societes($id);
        if( ! $result ) {
            throw new RestException(404, 'societescategory not found');
        }

        $societes = $this->societescategory->societes;
        /*if( ! DolibarrApi::_checkAccessToResource('societescategory', $this->societescategory->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }*/
        $data = array();

        if (sizeof($societes))
        {
            foreach ($societes as $societe)
            {
                $data[] = $this->_cleanObjectDatas($societe);
            }
        }

        
		return $data;
    }

  
}
