<?php
namespace Irto\NeoMongo\Relashionship;

use Irto\NeoMongo\Relashionship\Instance as Relashionship;
use MongoId;

trait Model {
	/**
	 * If it's a new relationship
	 *
	 * @var Bool
	 */
	private $new = false;

	/**
	 * Relationship start document
	 *
	 * @var Object
	 */
	protected $start_doc = null;

	/**
	 * Relationship end document
	 *
	 * @var Object
	 */
	protected $end_doc = null;

	/**
	 * Make new relation from $startDoc to $endDoc of type $type.
	 *
	 * @param Object $startDoc
	 * @param Object $endDoc
	 * @param String $type [null]
	 *
	 * @return instanced this
	 */
	public static function make($startDoc, $endDoc, $type = null){
		if(is_null($type))
			$type = get_called_class();

		$instance = new static($startDoc, $endDoc);
		$instance->setType($type);
		$instance->isNew(true);

		return $instance;
	}

	/**
	 * This relationship type
	 *
	 * @var String
	 */
	protected $type = null;

	/**
	 *
	 *
	 */
	public function __construct($startDoc, $endDoc){
		$this->start_doc = $startDoc;

		if(is_object($endDoc) && !($endDoc instanceof MongoId))
			$this->end_doc = $endDoc->getProperty('_id');
		else
			$this->end_doc = $endDoc;

		if(! $this->end_doc instanceof MongoId)
			$this->end_doc = new MongoId($this->end_doc);
	}

	/**
	 * Set type of relationship
	 *
	 * @param String $type
	 */
	public function setType($type){
		$this->type = $type;
	}

	/**
	 * Return type of relationship
	 *
	 * @return String
	 */
	public function getType(){
		return strtolower($this->type);
	}

	/**
	 *
	 *
	 *
	 */
	public function isNew($set = null){
		if(!is_null($set))
			$this->new = (bool) $set;

		return $this->new;
	}

	/**
	 * Save this relationship
	 */
	public function save(){
		if($this->isNew())
			return $this->insert();

		return $this->update();
	}

	/**
	 * Perform update on start document to insert this relationship
	 */
	protected function insert(){
		$property = Relashionship::REL_PREFIX . $this->getType();

		$properties = $this->getProperties();
		$properties['_id'] = $this->end_doc];

		$update = [ '$push' => [ $property => $properties ] ];

		$result = $this->start_doc->update($criteria, $update);

		if($result)
			$this->isNew(false);

		return $result;
	}

	/**
	 * Perform update on start document to save this relationship
	 */
	protected function update(){
		$property = Relashionship::REL_PREFIX . $this->getType();

		// add id of end document and each properties for update set.
		$properties = [$property . '.$._id' => $this->end_doc];
		foreach($this->getProperties() as $key => $value){
			$properties[$property . '.$.' . $key] => $value;
		}

		// Select de relationship by end document id
		// It's have to be unique per relation type
		$criteria = [$property . '._id' => $this->end_doc]
		$update = [ '$set' => $properties ];

		return $this->start_doc->update($criteria, $update);
	}

	/**
	 * Parse data from database
	 *
	 * @param Object $startDoc
	 * @param Array $dbrelData
	 */
	public function parseDocument($startDoc, Array $dbrelData){
		if(!(is_object($dbrelData['_id']) && $dbrelData['_id'] instanceof MongoId )
			&& Client::idMongoId($dbrelData['_id']))
			$dbrelData['_id'] = new MongoId($dbrelData['_id']);

		$this->end_doc = $dbrelData['_id'];
		unset($dbrelData['_id']);

		foreach($dbrelData as $key => $value)
			$this->setOriginalProp($key, $value);

		$this->isNew(false);
	}
}