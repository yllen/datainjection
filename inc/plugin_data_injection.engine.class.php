<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org/
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/

// Original Author of file: Walid Nouh (walid.nouh@atosorigin.com)
// Purpose of file:
// ----------------------------------------------------------------------

class DataInjectionEngine
{
	//Model informations
	private $model;
	
	//Backend to read file to import
	private $backend;

	//Current entity
	private $entity;
		
	function DataInjectionEngine($model,$filename,$backend, $entity=0)
	{
		//Instanciate model
		$this->model = $model;
		
		//Load model and mappings informations
		$this->getModel()->loadAll($this->model->getModelID());
		
		$this->entity = $entity;
		
		$datas = new InjectionDatas;
		
		$this->backend = $backend;
	}
	
	/*
	 * Get datas imported read from the file
	 */
	function getDatas()
	{
		if (isset($this->backend))
			return $this->backend->getDatas();
		else
			return array();	
	}
	
	/*
	 * Return the number of lines of the file
	 * @return the number of line of the file
	 */
	function getNumberOfLines()
	{
		return $this->backend->getNumberOfLine();
	}
	
	/*
	 * Inject one line of datas
	 * @param line one line of data to import
	 */
	function injectLine($line,$infos = array())
	{
		$result = new DataInjectionResults;
	
		$line = reformatDatasBeforeCheck($this->getModel(),$line);
		
		$result = checkLine($this->getModel(),$line,$result);
		if (!$result->getStatus())
			return $result;
			
		//Array to store the fields to write to db
		$db_fields = array();
		$db_fields[COMMON_FIELDS] = array();
		$db_fields[$this->getModel()->getDeviceType()] = array();
		
		for ($i=0; $i < count($line);$i++)
		{
			$mapping = $this->getModel()->getMappingByRank($i);
			if ($mapping != null && $mapping->getValue() != NOT_MAPPED)
			{
				$mapping_definition = getMappingDefinitionByTypeAndName($mapping->getMappingType(),$mapping->getValue());
				if (!isset($db_fields[$mapping->getMappingType()]))
					$db_fields[$mapping->getMappingType()] = array();
				
				$db_fields[$mapping->getMappingType()] = getFieldValue(
						$mapping, 
						$mapping_definition,$line[$i],
						$this->getEntity(),
						$db_fields[$mapping->getMappingType()],
						$this->getModel()->getCanAddDropdown()
				);
			}
		}

		//Add informations filleds by the user
		$db_fields = addInfosFields($db_fields,$infos);

		$process = true;
		//----------------------------------------------------//
		//-------------Process primary type------------------//
		//--------------------------------------------------//
		
		//First, try to insert or update primary object
		$fields = $db_fields[$this->getModel()->getDeviceType()];

		$obj = getInstance($this->getModel()->getDeviceType());

		//Add some fields to the common fields BEFORE inserting the primary type (in order to save some fields)
		$db_fields[COMMON_FIELDS] = preAddCommonFields($db_fields[COMMON_FIELDS],$this->getModel()->getDeviceType(),$fields,$this->getEntity());
				
		//If necessary, add default fields which are mandatory to create the object
		addNecessaryFields($this->getModel(),$mapping,$mapping_definition,$this->getEntity(),$this->getModel()->getDeviceType(),$fields,$db_fields[COMMON_FIELDS]);

		//Check if the line already exists in database
		$fields_from_db = dataAlreadyInDB($this->getModel()->getDeviceType(),$fields,$mapping_definition,$this->getModel());

		$ID = $fields_from_db["ID"];
		if ($ID == ITEM_NOT_FOUND)
		{
			if ($this->getModel()->getBehaviorAdd())
			{
				$ID = $obj->add($fields);
				//Add the ID to the fields, so it can be reused after
				addCommonFields($db_fields[COMMON_FIELDS],$this->getModel()->getDeviceType(),$fields,$this->getEntity(),$ID);
				
				//Log in history
				logAddOrUpdate($this->getModel()->getDeviceType(),$ID,INJECTION_ADD);
				
				//Set status and messages
				$result->setStatus(true);

				$result->setInjectedId($ID);
				$result->setInjectionType(INJECTION_ADD);

				$result->setInjectionStatus(IMPORT_OK);
			}
			else
			{
				//Object doesn't exists, but adding is not allowed by the model
				$process = false;
				
				$result->setStatus(false);

				$result->setInjectedId(NOT_IMPORTED);
				$result->setInjectionType(INJECTION_ADD);
				
				$result->setInjectionStatus(NOT_IMPORTED);
				$result->addInjectionMessage(ERROR_CANNOT_IMPORT);
			}
		}	
		elseif ($this->getModel()->getBehaviorUpdate())
		{
			filterFields($fields,$fields_from_db,$this->getModel()->getCanOverwriteIfNotEmpty());
			$fields["ID"] = $ID;

			addCommonFields($db_fields[COMMON_FIELDS],$this->getModel()->getDeviceType(),$fields,$this->getEntity(),$ID);

			if (count($fields) > 1)
			{
				logAddOrUpdate($this->getModel()->getDeviceType(),$ID,INJECTION_UPDATE);
				$obj->update($fields);
			}
			$result->setStatus(true);

			$result->setInjectedId($ID);
			$result->setInjectionType(INJECTION_UPDATE);

			$result->setInjectionStatus(IMPORT_OK);
		}
		else
		{	
			//Object exists but update is not allowed by the model
			$process = false;

			$result->setStatus(false);

			$result->setInjectedId($ID);
			$result->setInjectionType(INJECTION_UPDATE);
			
			$result->setInjectionStatus(NOT_IMPORTED);
			$result->addInjectionMessage(ERROR_CANNOT_UPDATE);
		}
		if ($process)
		{
			//Post processing, if some actions need to be done
			processBeforeEnd($this->getModel(),$this->getModel()->getDeviceType(),$fields,$db_fields[COMMON_FIELDS]);
			
			//----------------------------------------------------//
			//-------------Process other types-------------------//
			//--------------------------------------------------//

			//Insert others objects in database
			foreach ($db_fields as $type => $fields)
			{
				//Browse the db_fields array : inject all the types execpt COMMON_FIELDS and the primary type
				if ($type != COMMON_FIELDS && $type != $this->getModel()->getDeviceType())
				{
					$obj = getInstance($type);

					// Add some fields to the common fields BEFORE inserting the secondary type (in order to save some fields)
					$db_fields[COMMON_FIELDS] = preAddCommonFields($db_fields[COMMON_FIELDS],$type,$fields,$this->getEntity());

					// If necessary, add default fields which are mandatory to create the object
					addNecessaryFields($this->getModel(),$mapping,$mapping_definition,$this->getEntity(),$type,$fields,$db_fields[COMMON_FIELDS]);
					
					//Check if the line already exists in database
					$fields_from_db = dataAlreadyInDB($type,$fields,$mapping_definition,$this->getModel());

					$ID = $fields_from_db["ID"];
					if ($ID == ITEM_NOT_FOUND)
					{
						//Not in DB -> add
						$ID = $obj->add($fields);
						//Add the ID to the fields, so it can be reused after
						addCommonFields($db_fields[COMMON_FIELDS],$this->getModel()->getDeviceType(),$fields,$this->getEntity(),$ID);
					}	
					else
					{
						$fields["ID"] = $ID;
						filterFields($fields,$fields_from_db,$this->getModel()->getCanOverwriteIfNotEmpty());
						//Item aleady in DB -> update
						addCommonFields($db_fields[COMMON_FIELDS],$type,$fields,$this->entity,$ID);
						$obj->update($fields);
					}

					//Post processing, if some actions need to be done
					processBeforeEnd($this->getModel(),$type,$fields,$db_fields[COMMON_FIELDS]);
				}
			}
		}
		return $result;			
	}

	function getModel()
	{
		return $this->model;
	}

	function getBackend()
	{
		return $this->backend;
	}

	function getEntity()
	{
		return $this->entity;
	}
}

?>