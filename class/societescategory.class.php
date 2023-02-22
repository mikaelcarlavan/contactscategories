<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

/**
 * \file        class/societescategory.class.php
 * \ingroup     contactscategories
 * \brief       This file is a CRUD class file for societescategory (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for societescategory
 */
class SocietesCategory extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'societescategory';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'societescategory';

	/**
	 * @var array  Does this field is linked to a thirdparty ?
	 */
	protected $isnolinkedbythird = 1;
	/**
	 * @var array  Does societescategory support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	protected $ismultientitymanaged = 0;
	/**
	 * @var string String with name of icon for societescategory
	 */
	public $picto = 'societescategory';



	public $id;
	public $lat;
	public $lng;
	public $societe_id;

	public $societes = array();

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
	 * 	@param		int		$id		Id of category
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	public function fetch($societe_id = 0)
	{
		global $conf;

		// Check parameters
		if (empty($societe_id)) return -1;

		$sql = "SELECT scat.rowid, scat.lat, scat.lng, scat.societe_id";
		$sql.= " FROM ".MAIN_DB_PREFIX."societescategory as scat";
		$sql.= " WHERE societe_id = ".$societe_id;
		


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
				$this->societe_id	= $res->societe_id;

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

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societescategory (lat, lng, societe_id) VALUES (";
		$sql.= $this->lat.",";
		$sql.= $this->lng.",";
		$sql.= $this->societe_id;
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."societescategory");

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

		$sql = "UPDATE ".MAIN_DB_PREFIX."societescategory";
		$sql.= " SET lat = ".$this->lat.",";
		$sql.= " lng = ".$this->lng.",";
		$sql.= " societe_id = ".$this->societe_id;
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

		$sql = " DELETE FROM ".MAIN_DB_PREFIX."societescategory";
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
	 * 	Load societes into memory from database
	 *
	 * 	@param		int		$id		Id of category
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	public function societes($id)
	{
		global $langs, $conf;
		

		$this->societes = array();

		$categorystatic = new Categorie($this->db);
		$societescategory = new SocietesCategory($this->db);

		if ($categorystatic->fetch($id))
		{
			$societes = $categorystatic->getObjectsInCateg('customer');

			if (sizeof($societes))
			{
				foreach ($societes as $societe)
				{

					if ($societescategory->fetch($societe->id))
					{
						$lat = $societescategory->lat;
						$lng = $societescategory->lng;
						$center = $lat.','.$lng;
					}
					else
					{
						$center = '';
						$lat = '';
						$lng = '';						
					}

					$description ='';

					// if (!empty($societe->logo)) // logo n'est pas accessible deepuis l'extérieur de dolibarr
					// {
					// $description .= '<img src="'.dol_buildpath('/viewimage.php?modulepart=societe&entity='.$conf->entity.'&file='.$societe->id.'%2Flogos%2F'.$societe->logo.'&cache=0',2).'">';
					// }

					if(!empty($societe->name_alias)){
						$description_name= '<strong>'.htmlentities($societe->name_alias).'</strong>';
					}else{
						$description_name= '<strong>'.htmlentities($societe->getFullName($langs)).'</strong>';
					}
					$description.=$description_name;
					
					
					if (!empty($societe->address))
					{
						$societe->address = strtolower($societe->address);
						$description_address= '<br /><span class="address">';
						$description_address.= htmlentities(trim(preg_replace('/\s+/', ' ', $societe->address)));

						if (!empty($societe->zip) || !empty($societe->town))
						{
							$description_address.= '<br />';
							$description_address.= !empty($societe->zip) ? $societe->zip.' ' : '';
							$description_address.= !empty($societe->town) ? htmlentities(ucfirst(strtolower($societe->town))) : ''.'</span>';
							$description.=$description_address;
						}
					}

					if (!empty($societe->phone))
					{
						$description_phone= '<br /><span class="phone">';
						$description_phone.= htmlentities('☎');				
						$description_phone.= dol_print_phone($societe->phone,$societe->country_code).'</span>';
						$description.=$description_phone;
					}

					if (!empty($societe->email))
					{
						$description_mail= '<br />';
						$description_mail.= htmlentities('📧');
						$description_mail.= $societe->email;
						$description.= $description_mail;
					}

					if (!empty($societe->url))
					{
						$description_website= '<br /><span class="website">';
						$description_website.= htmlentities('🔗');
						$description_website.= '<a href="'.$societe->url.'" target="_blank">'.$societe->url.'</a></span>';
						$description.= $description_website;
					}

					if(!empty($societe->address) && !empty($societe->zip) && !empty($societe->town)){
						$description_goto= '<br /><span class="goto">';
						$description_goto.= htmlentities('🌍');
						$sep='+';
						$link = 'https://www.google.fr/maps/dir//'.$societe->nom.','.$sep.$societe->address.','.$sep.$societe->zip.$sep.$societe->town;
						$description_goto.= '<a href="'.$link.'" target="_blank">Itinéraire</a></span>';
						$description.= $description_goto;
					}

					$societe->lat = $lat;
					$societe->lng = $lng;
					$societe->center = $center;
					$societe->description = $description;
					$societe->description_name = $description_name;
					$societe->description_address = $description_address;
					$societe->description_phone = $description_phone;
					$societe->description_mail = $description_mail;
					$societe->description_website = $description_website;
					$societe->description_goto = $description_goto;

					$this->societes[] = $societe;
				}
			}
			//tri par ordre alphabétique sur le nom
			$nom = array_column($this->societes, 'nom');
			array_multisort($nom, SORT_ASC, $this->societes);
			return 1;
		}
		else
		{
			return 0;
		}
	}

}

