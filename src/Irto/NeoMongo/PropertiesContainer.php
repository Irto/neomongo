<?php
namespace Irto\NeoMongo;

trait PropertiesContainer {
	/**
	 * Setted properties
	 *
	 * @var Array
	 */
	private $properties = array();

	/**
	 * Original properties thats come from queries
	 *
	 * @var Array
	 */
	private $original_props = array();

	/**
	 * Set a value to a key
	 * 
	 * @param String $key
	 * @param Mixed $value
	 */
	public function setProperty($key, $value){
		// convert to number, float or int
		if(is_numeric($value)) $value = 0 + $value;

		$this->properties[$key] = $value;
	}

	/**
	 * Push a new value to an array property
	 *
	 * @param String $key
	 * @param Mixed $value
	 */
	public function pushProperty($key, $value){
		if(!is_array($this->getProperty($key))){
			if(is_array($this->properties[$key]))
				$this->properties[$key][] = $value;
			else
				$this->properties[$key] = [$value];

			return;
		}

		if(!isset($this->properties['$push']))
			$this->properties['$push'] = [];
		
		if(isset($this->properties['$push'][$key]))
			$this->properties['$push'][$key][] = $value;
		else
			$this->properties['$push'][$key] = [$value];
	}

	/**
	 * Get value by the key
	 *
	 * If original is set false, can return value from not saved updates.
	 *
	 * @param String $key
	 * @param Bool $original
	 *
	 * @return Mixed
	 */
	public function getProperty($key, $original = true, $default = null){
		if($this->hasProperty($key, false) && !$original){
			if(isset($this->properties['$push'][$key])) // verify if is a pushed property
				return array_merge(
						$this->getProperty($key, true, []),
						$this->properties['$push'][$key]);

			else return $this->properties[$key]; // if don't use default return 
		}

		if($this->hasProperty($key, true) && $original) // if want original
			return $this->original_props[$key];

		return $default;
	}

	/**
	 * Return all properties as array to use an update query
	 *
	 * @return Array
	 */
	public function getProperties(){
		$properties = $this->preparePushedAttrs($this->properties);
		return $properties;
	}

	/**
	 * Organize itens to be pushed on collection
	 *
	 * @param Array $properties
	 *
	 * @return Array
	 */
	private function preparePushedAttrs($properties){
		if(isset($properties['$push'])){
			foreach($properties['$push'] as $key => &$value){
				if(is_array($value) && count($value) == 1)
					$value = $value[0];
				else
					$value = ['$each' => $value];
			}
		}

		return $properties;
	}

	/**
	 * Verify if determined key exists.
	 * 
	 * If original is set false, can verify on not saved values
	 *
	 * @param String $key
	 * @param Bool $original
	 *
	 * @return Bool
	 */
	public function hasProperty($key, $original = true){
		switch(true){
		case (isset($this->properties[$key]) && !$original):
		case (isset($this->properties['$push'][$key]) && !$original):
		case (isset($this->original_props[$key]) && $original):
			return true;
		}

		return false;
	}

	/**
	 * Unset a property
	 *
	 * @param String $key
	 */
	public function unsetProperty($key){
		unset($this->properties[$key]);
	}

	/**
	 * Set a original property
	 *
	 * @param String $key
	 * @param Mixed $value
	 */
	protected function setOriginalProp($key, $value){
		$this->original_props[$key] = $value;
	}

	/**
	 * Toggle to modified properties to original properties
	 * and set empty modified properties
	 */
	protected function toggleProperties(){	
		$this->original_props = array_merge($this->original_props, $this->properties);
		$this->properties = array();

		if(isset($this->original_props['$push'])){
			foreach($this->original_props['$push'] as $key => $value)
				$this->original_props[$key] = array_merge($this->original_props[$key], $value);

			unset($this->original_props['$push']);
		}

		unset($this->original_props['$unset']);
	}
}