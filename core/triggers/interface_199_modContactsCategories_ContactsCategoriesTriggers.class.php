<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modContactsCategories_ContactsCategoriesTriggers.class.php
 * \ingroup contactscategories
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modContactsCategories_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */


require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/contactscategories/class/contactscategory.class.php');


/**
 *  Class of triggers for ContactsCategories module
 */
class InterfaceContactsCategoriesTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "ContactsCategories triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'contactscategories@contactscategories';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		
        if (empty($conf->contactscategories->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		
		$contactscategory = new ContactsCategory($this->db);
		$category = new Categorie($this->db);

        switch ($action) {
		        // Contacts
		    case 'CONTACT_CREATE':
		    case 'CONTACT_MODIFY':
		    case 'CONTACT_DELETE':

	    		if ($action == 'CONTACT_MODIFY' || $action == 'CONTACT_DELETE')
	    		{
	    			$contactscategory->fetch($object->id);
	    		}

	    		if ($action == 'CONTACT_DELETE')
	    		{
	    			$contactscategory->delete($user);
	    		}
	    		else
	    		{
	    			list($lat, $lng) = $this->getLatLng($object);

	    			if (!empty($lat) && !empty($lng))
	    			{
						$contactscategory->lat = $lat;
						$contactscategory->lng = $lng;
						$contactscategory->contact_id = $object->id;

						if ($action == 'CONTACT_MODIFY' && $contactscategory->id > 0)
						{
							$contactscategory->update($user);
						}
						else
						{
							$contactscategory->create($user);
						}	    				
	    			}
		
	    		}

		    	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id, LOG_DEBUG);
				break;

		        // Categories
		    case 'CATEGORY_MODIFY':
	    		$category->fetch($object->id);
	    		$contacts = $category->getObjectsInCateg('contact');


    			if (sizeof($contacts))
    			{
    				foreach ($contacts as $contact)
    				{
    					$contactscategory = new ContactsCategory($this->db);
    					$contactscategory->fetch($contact->id);
		    			list($lat, $lng) = $this->getLatLng($contact);


		    			if (!empty($lat) && !empty($lng))
		    			{
							$contactscategory->lat = $lat;
							$contactscategory->lng = $lng;
							$contactscategory->contact_id = $contact->id;

							if ($contactscategory->id > 0)
							{
								$contactscategory->update($user);
							}
							else
							{
								$contactscategory->create($user);
							}		    				
		    			}	    				
		    		}
    			}
	    		

		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id, LOG_DEBUG);
		        break;

		}

		return 0;
	}

	private function getLatLng($object)
	{
		global $conf;

		$lat = '';
		$lng = '';

		$address			= $object->address;
		$zip				= $object->zip;
		$town				= $object->town;
		$country			= $object->country;	

		if (!empty($address) && !empty($town))
		{
			$addressfull = $address.', '.$zip.' '.$town.', '.$country;
			$key = $conf->global->CONTACTSCATEGORIES_GOOGLE_MAPS_KEY;

			$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($addressfull).'&key='.$key;

			$request = file_get_contents($url);


			if (!empty($request))
			{
				
				$results = json_decode($request, true);
				//var_dump($results);

				if ($results['status'] == 'OK')
				{

					$result = $results['results'][0];


					$geometry = $result['geometry'];

					$lat = $geometry['location']['lat'];
					$lng = $geometry['location']['lng'];
				}
			}

		}  


		return array($lat, $lng); 		
	}
}
