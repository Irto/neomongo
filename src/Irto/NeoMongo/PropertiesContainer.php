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
		$this->properties[$key] = $value;
	}

	/**
	 * Push a new value to an array property
	 *
	 * @param String $key
	 * @param Mixed $value
	 */
	public function pushProperty($key, $value){
		if(!isset($this->properties[$key]) || !is_array($this->properties[$key]))
			$this->properties[$key] = [];
		
		$this->properties[$key][] = $value;
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
		if($this->hasProperty($key, $original))
			return $this->properties[$key];

		if($this->hasProperty($key, $original))
			return $this->original_props[$key];

		return $default;
	}

	/**
	 * Return al properties as array
	 *
	 * @return Array
	 */
	public function getProperties(){
		return $this->properties;
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
		if(isset($this->properties[$key]) && !$original)
			return true;

		if(isset($this->original_props[$key]))
			return true;

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
	}
}