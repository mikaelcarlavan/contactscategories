<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
* Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/contactscategories/class/contactscategory.class.php');
dol_include_once('/contactscategories/class/societescategory.class.php');


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
		// 
		
		
		if($object->element == 'contact'){
			$elementcategory = new ContactsCategory($this->db);
		}elseif($object->element == 'societe'){
			$elementcategory = new SocietesCategory($this->db);
		}

		$category = new Categorie($this->db);

        switch ($action) {
		        // Contacts
		    case 'CONTACT_CREATE':
		    case 'CONTACT_MODIFY':
		    case 'CONTACT_DELETE':
		    case 'COMPANY_CREATE':
		    case 'COMPANY_MODIFY':
		    case 'COMPANY_DELETE':    

	    		if ($action == 'CONTACT_MODIFY' || $action == 'CONTACT_DELETE' || $action == 'COMPANY_MODIFY' || $action == 'COMPANY_DELETE')
	    		{
	    			$elementcategory->fetch($object->id);
	    				    				    			
	    		}

	    		if ($action == 'CONTACT_DELETE' || $action == 'COMPANY_DELETE')
	    		{
	    			$elementcategory->delete($user);
	    		}
	    		else
	    		{
	    			list($lat, $lng) = $this->getLatLng($object);

	    			if (!empty($lat) && !empty($lng))
	    			{
						$elementcategory->lat = $lat;
						$elementcategory->lng = $lng;
						$elementcategory->{$object->element.'_id'} = $object->id;
						
						if (($action == 'CONTACT_MODIFY' || $action == 'COMPANY_MODIFY') && $elementcategory->id > 0)
						{
							$elementcategory->update($user);
						}
						else
						{
							$elementcategory->create($user);
						}	    				
	    			}
		
	    		}

		    	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id, LOG_DEBUG);
				break;

		        // Categories
		    case 'CATEGORY_MODIFY':
	    		$category->fetch($object->id);
	    		
	    		if($category->type==2){//societe
	    			$elements = $category->getObjectsInCateg('customer');
	    		}elseif($category->type==4){//contact
	    			$elements = $category->getObjectsInCateg('contact');
	    		}

    			if (sizeof($elements))
    			{
    				foreach ($elements as $element)
    				{
    					if($category->type==2){
    						$elementcategory = new SocietesCategory($this->db);
    						$type='customer';
    					}elseif($category->type=4){
    						$elementcategory = new ContactsCategory($this->db);
    						$type='contact';
    					}
    					
    					$elementcategory->fetch($element->id);
		    			list($lat, $lng) = $this->getLatLng($element);


		    			if (!empty($lat) && !empty($lng))
		    			{
							$elementcategory->lat = $lat;
							$elementcategory->lng = $lng;
							$elementcategory->{$type.'_id'} = $element->id;

							if ($elementcategory->id > 0)
							{
								$elementcategory->update($user);
							}
							else
							{
								$elementcategory->create($user);
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
			//$key = $conf->global->CONTACTSCATEGORIES_GOOGLE_MAPS_KEY;

			if($object->country_id!=1){
				$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($addressfull).'&key='.$key;
				$request = file_get_contents($url);
				if (!empty($request))
				{
					$results = json_decode($request, true);
					if ($results['status'] == 'OK')
					{
						$result = $results['results'][0];
						$geometry = $result['geometry'];
						$lat = $geometry['location']['lat'];
						$lng = $geometry['location']['lng'];
					}
				}

			}else{
				$url = 'https://api-adresse.data.gouv.fr/search/?q='.urlencode($addressfull).'&type=housenumber&autocomplete=0';
				$request = file_get_contents($url);
				if (!empty($request))
				{
					$results = json_decode($request, true);
					if (!empty($results['features']))
					{
						$result = $results['features'][0];
	                    $geometry = $result['geometry'];
	                    $lat = $geometry['coordinates'][1];
						$lng = $geometry['coordinates'][0];
					}
				}
			}

		}  

		return array($lat, $lng); 		
	}
}
