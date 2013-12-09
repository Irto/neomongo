<?php 
namespace Irto\NeoMongo;

use MongoCollection;

Class Collection extends MongoCollection {
	/**
	 * Change visibility of toIndexString through overloading
	 */
	public static function toIndexString($a){
        return parent::toIndexString($a);
    }
}