<?php
namespace Irto\NeoMongo;

use Iterator;

Class OdmCursor implements Iterator {
    /**
     * Model class that will be returned when iterate
     *
     * @var string
     */
    protected $model;

    /**
     * The MongoCursor used to interact with db
     *
     * @var MongoCursor
     */
	protected $cursor;

	/**
	 * Iterator position (to be used with foreach)
	 *
	 * @var integer
	 */
	private $position = 0;

	/**
	 * OdmCursor constructor. The mongo cursor and the
	 * model should be provided
	 *
	 * @param $cursor MongoCursor
	 * @param $model string
	 * @return void
	 */
    public function __construct( $cursor, $model ){
		$this->cursor = $cursor;
		$this->cursor->rewind();

		if(is_string($model))
			$this->model = new $model;
		elseif(is_object($model))
			$this->model = $model;
		else
			trigger_error("Wrong type of \$model, String or Object expected, \'" . gettype($model) . "\' given.");

		$this->position = 0;
    }

	/**
	 * Calls the MongoCursor method if it exists.
	 * This makes possible to run methods like limit, skip,
	 * orts, and others.
	 *
	 * @param $name string
	 * @param $args array
	 */
	public function __call($name, $args){
		if( method_exists( $this->cursor, $name) ) {
			// Calls the method in MongoCursor
			$result = call_user_func_array( array($this->cursor, $name), $args);

			// In case of sort, limit and other methods of the cursor
			// that return itself (for chained method calls), should
			// return $this (OdmCursor object) instead of MongoCursor.
			if(is_object($result) && get_class($result) == 'MongoCursor') {
				return $this;
			} else {
				return $result;
			}
		} else {
		    trigger_error('Method '.$name.' does not exist in OdmCursor nor in MongoCursor.');
		}
    }

	/**
	 * Returns the MongoCursor object
	 *
	 * @return MongoCursor
	 */
	public function getCursor(){
		return $this->cursor;
	}

	/**
	 * Iterator interface rewind (used in foreach)
	 *
	 */
	public function rewind(){
		$this->cursor->rewind();
		$this->position = 0;
	}

	/**
	 * Iterator interface current. Return a model object
	 * with cursor document. (used in foreach)
	 *
	 * @return mixed
	 */
	public function current(){
		$model = clone $this->model;

		$document = $this->cursor->current();

		if( $model->parseDocument( $document ) )
			return $model;


		return false;
	}

	/**
	 * Convert the cursor instance to an array.
	 *
	 * @return array
	 */
	public function toArray( $documentsToArray = true, $limit = false ){
		$result = array();

		foreach($this as $document) {
			if( $documentsToArray ) {
				$result[] = $document->getAttributes();
			} else {
				$result[] = $document;
			}
		}

		return $result;
	}

	/**
	 * Returns the first element of the cursor
	 *
	 * @return mixed
	 */
	public function first(){
		$this->rewind();
		return $this->current();
	}

	/**
	 * Iterator key method (used in foreach)
	 *
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Iterator next method (used in foreach)
	 *
	 */
	public function next() {
		++$this->position;
		$this->cursor->next();
	}

	/**
	 * Iterator valid method (used in foreach)
	 *
	 */
	public function valid() {
		return $this->cursor->valid();
	}

	/**
	 * Iterator count method
	 *
	 */
	public function count() {
		return $this->cursor->count();
	}

    public function sort( $fields ){
		if($this->count() > 1) {
			$this->cursor->sort( $fields );
		}

		return $this;
    }

	/**
	 * Convert the cursor to its string representation.
	 *
	 * @return string
	 */
    public function __toString(){
		$result = '';

		$this->limit(20);

		foreach($this as $document) {
			$result .= (string) $document;
		}

		$result = '['.$result.']';

		return $result;
    }

	/**
	 * Convert the cursor to its JSON representation.
	 *
	 * @return string
	 */
	public function toJson($options = 0){
		$result = '';

		foreach($this as $document) {
			$result .= $document->toJson($options);
		}

		$result = '['.$result.']';

		return $result;
    }
}