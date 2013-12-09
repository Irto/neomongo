<?php
namespace Irto\NeoMongo\Relationship;

use Irto\NeoMongo\Relationship\Instance as Relationship;
use Irto\NeoMongo\Relationship\Query\Instance as RelQuery;
use MongoId;

trait Model {

	/**
	 * Save status of this relationship, if is saved (not new) or not (new).
	 *
	 * @var Bool
	 */
	private $new;

	/**
	 * Relationship start document, its public read-only (see this->__get)
	 *
	 * @var Object
	 */
	protected $start_doc = null;

	/**
	 * Relationship end document, its public read-only (see this->__get)
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
	 * Shortcut to Static::first
	 */
	public static function findOne(){
		return forward_static_call_array('first', func_get_args());
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public static function first(){
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public static function find(){
	}

	/**
	 *
	 *
	 *
	 *
	 */
	public static function where($criteria, Array $projection){
		$relationship = new static();
		if(!empty($relationship->getType()))
			$criteria['$type'] = $relationship->getType();

		$query = New RelQuery($criteria);
		$document = $query->execute($projection)->current();

		$relationship->setType($criteria['$type']);

		$data = $document->getProperty(Relationship::REL_PREFIX . $criteria['$type']);
		$relationship->parseData($data[0], $document);

		return $relationship;
	}

	/**
	 * Start a new Relationship instance
	 *
	 */
	private function __construct($startDoc = null, $endDoc = null){
		if(!is_null($startDoc))
			$this->start_doc = $startDoc;

		if(!is_null($endDoc))
			$this->setEndDocument($endDoc);

		$this->indexes[] = [['_id', '_collection'], 'unique'];
		$this->indexes['_id'] = 'none';
	}

	/**
	 * Send relationship end document.
	 * Can't use it to update end document, 
	 * 
	 * @param Document $endDoc
	 */
	protected function setEndDocument($endDoc){
		if(is_object($endDoc)){
			$this->setProperty('_id', $endDoc->getProperty('_id'));
			$this->setProperty('_collection', $endDoc::getCollection());
			$this->setProperty('_class', get_class($endDoc));
			$this->end_doc = $endDoc; // save for future get
		} else {
			$this->setProperty('_id', $endDoc[0]);
			$this->setProperty('_collection', $endDoc[1]);
			$this->setProperty('_class', $endDoc[2]);
		}
	}

	/** 
	 * Get cached end document, or find on collection using projection.
	 * It's only use first query when have $projection, else will return array
	 * with relationship fields.
	 * Use true to $projection for not get cached object and return
	 * relationship array.
	 *
	 * @param Array|Bool $projection [null]
	 *
	 * @return Object|Array|Null
	 */
	public function getEndDocument($projection = null){
		if(isset($this->end_doc) && is_object($this->end_doc) && is_null($projection))
			return $this->end_doc;

		if($this->hasProperty('_class') && is_array($projection)){
			extract($this->getProperties(['_id', '_class']));

			$instance = $_class::first($_id, $projection);
			$this->end_doc = $instance;
			return $instance;
		}

		return $this->getProperties($projection);
	}

	/**
	 *
	 *
	 * @param Bool $set
	 */
	public function isNew($set = null){
		if(!is_null($set))
			$this->new = $set;

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
		$property = Relationship::REL_PREFIX . $this->getType();

		$properties = $this->getProperties(false);

		$update = [ '$push' => [ $property => $properties ] ];

		$result = $this->start_doc->update([], $update);

		if($result === true){
			$this->isNew(false);
			$this->toggleProperties();
		}

		return $result;
	}

	/**
	 * Perform update on start document to save this relationship
	 */
	protected function update(){
		if(!$this->hasUniqueIndex() || $this->isNew()) return false;

		$property = Relationship::REL_PREFIX . $this->getType();

		// add id of end document and each properties for update set.
		foreach($this->getProperties(false) as $key => $value){
			$properties[$property . '.$.' . $key] = $value;
		}

		// Select de relationship by end document id and collection name
		// It's have to be unique per relation type
		$criteria = $this->getUniqueIndex();

		unset($properties['_id']);
		unset($properties['_collection']);
		unset($properties['_class']);
		$update = [ '$set' => $properties ];

		$result = $this->start_doc->update($criteria, $update);

		if($result === true)
			$this->toggleProperties();

		return $result;
	}

	/**
	 * Parse data from database
	 *
	 * @param Object $startDoc
	 * @param Array $dbrelData
	 */
	public function parseData(Array $dbrelData, $startDoc = null){
		if(!is_null($startDoc))
			$this->start_doc = $startDoc;

		// if it's a mongoid string
		if(!(is_object($dbrelData['_id']) && $dbrelData['_id'] instanceof MongoId )
			&& Client::idMongoId($dbrelData['_id']))
			$dbrelData['_id'] = new MongoId($dbrelData['_id']);

		$this->setEndDocument([$dbrelData['_id'], $dbrelData['_collection'], $dbrelData['_class']]);
		unset($dbrelData['_id']);
		unset($dbrelData['_collection']);
		unset($dbrelData['_class']);

		foreach($dbrelData as $key => $value)
			$this->setOriginalProp($key, $value);

		$this->isNew(false);
	}

	/**
	 * Its for add prefixes to determined $key,
	 *
	 * @param String $key
	 */
	public function prefixProperty($key){
		$property = Relationship::REL_PREFIX . $this->getType() . '.';
		return $property . $key;
	}

	/**
	 *
	 */
	public function __get($key){
		switch($key){
			case 'start_doc':
				return $this->start_doc;
			case 'end_doc':
				return $this->getEndDocument(null);
		}

		if(method_exists(parent, '__get'))
			return parent::__get($key);

		return null;
	}

	/**
	 *
	 */
	public function __call($name, $args){
		switch($name){
			case 'setEndDocument':
				if(!$this->isNew()) return call_user_method_array('setEndDocument', $this, $args);
		}

		trigger_error('The method ' . $name . ' in trait Irto\NeoMongo\Relationship\Model not exists, or access are not allowed.');
	}
}