<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

/**
 * \file        class/contactscategory.class.php
 * \ingroup     contactscategories
 * \brief       This file is a CRUD class file for ContactsCategory (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for ContactsCategory
 */
class ContactsCategory extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contactscategory';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'contactscategory';

	/**
	 * @var array  Does this field is linked to a thirdparty ?
	 */
	protected $isnolinkedbythird = 1;
	/**
	 * @var array  Does contactscategory support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;
	/**
	 * @var string String with name of icon for contactscategory
	 */
	public $picto = 'contactscategory';



	public $id;
	public $lat;
	public $lng;
	public $contact_id;

	public $contacts = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * 	Load contact info into memory from database
	 *
	 * 	@param		int			$id					Id of category
	 * 	@param		string		$element_type		contact, societe, etc...
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	public function fetch($contact_id = 0)
	{
		global $conf;

		// Check parameters
		if (empty($contact_id)) return -1;

		$sql = "SELECT rowid, lat, lng, contact_id";
		$sql.= " FROM ".MAIN_DB_PREFIX."contactscategory";
		$sql.= " WHERE contact_id = ".$contact_id;


		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_object($resql);

				$this->id			= $res->rowid;
				$this->lat			= $res->lat;
				$this->lng			= $res->lng;
				$this->contact_id	= $res->contact_id;

				$this->db->free($resql);

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Add category into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : SQL error
	 *          					-2 : new ID unknown
	 */
	function create($user)
	{
		global $conf, $langs;

		$error=0;

		dol_syslog(get_class($this).'::create', LOG_DEBUG);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contactscategory (lat, lng, contact_id) VALUES (";
		$sql.= $this->lat.",";
		$sql.= $this->lng.",";
		$sql.= $this->contact_id;
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."contactscategory");

			if ($id > 0)
			{
				$this->id = $id;

				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Update category
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 */
	function update($user = '')
	{
		global $conf, $langs;

		$error = 0;


		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contactscategory";
		$sql.= " SET lat = ".$this->lat.",";
		$sql.= " lng = ".$this->lng.",";
		$sql.= " contact_id = ".$this->contact_id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete a category from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
     *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int                 <0 KO >0 OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::delete");

		$this->db->begin();

		$sql = " DELETE FROM ".MAIN_DB_PREFIX."contactscategory";
		$sql.= " WHERE rowid = ".$this->id;

		if (!$this->db->query($sql))
		{
			$this->error = $this->db->lasterror();
			$error++;
		}
		

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Load contacts into memory from database
	 *
	 * 	@param		int		$id		Id of category
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	public function contacts($id)
	{
		global $langs;
		

		$this->contacts = array();

		$categorystatic = new Categorie($this->db);
		$contactscategory = new ContactsCategory($this->db);

		if ($categorystatic->fetch($id))
		{
			$contacts = $categorystatic->getObjectsInCateg('contact');

			if (sizeof($contacts))
			{
				foreach ($contacts as $contact)
				{

					if ($contactscategory->fetch($contact->id))
					{
						$lat = $contactscategory->lat;
						$lng = $contactscategory->lng;
						$center = $lat.','.$lng;
					}
					else
					{
						$center = '';
						$lat = '';
						$lng = '';						
					}

					$description = '<strong>'.htmlentities($contact->getFullName($langs)).'</strong>';
					if (!empty($contact->address))
					{
						$description.= '<br />';
						$description.= htmlentities(trim(preg_replace('/\s+/', ' ', $contact->address)));

						if (!empty($contact->zip) || !empty($contact->town))
						{
							$description.= '<br />';
							$description.= !empty($contact->zip) ? $contact->zip.' ' : '';
							$description.= !empty($contact->town) ? htmlentities($contact->town) : '';
						}
					}

					if (!empty($contact->phone_pro))
					{
						$description.= '<br />';
						$description.= htmlentities('Tél : ');
						$description.= $contact->phone_pro;
					}

					$contact->lat = $lat;
					$contact->lng = $lng;
					$contact->center = $center;
					$contact->description = $description;

					$this->contacts[] = $contact;
				}
			}

			return 1;
		}
		else
		{
			return 0;
		}
	}

}

