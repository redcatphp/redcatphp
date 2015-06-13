<?php 
namespace RedBase\DataMapper;
interface DataSource {
	public function getName();
	public function getPrimaryKey();
	public function findById($id);
	public function findByField(array $fields, $options);
	public function findAggregate($function, $field, $group = null, array $criteria = [], array $options = []);
	public function deleteById($id);
	public function deleteByField(array $fields);
	public function save($data,$relations);
	public function getErrors();
}