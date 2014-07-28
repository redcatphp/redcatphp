<?php namespace surikat\model;
use surikat\model;
use surikat\control;
use surikat\control\Config;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use surikat\model\RedBeanPHP\RedException;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
use surikat\model\Query4D;
class R extends RedBeanPHP\Facade{
	private static $camelsSnakeCase = false;
	static function toSnake($camel){
		if(!self::$camelsSnakeCase||self::getWriter()->caseSupport===false)
			return $camel;
		return strtolower(preg_replace('/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '-$1$2', $camel ));
	}
	static function toCamel($snake){
		if(!self::$camelsSnakeCase||self::getWriter()->caseSupport===false)
			return $snake;
		$snake = explode('-',$snake);
		foreach($snake as &$v)
			$v = ucfirst($v);
		$snake = lcfirst(implode('',$snake));
		return $snake;
	}
	static function camelsSnakeCase(){
		if(func_num_args())
			self::$camelsSnakeCase = func_get_arg(0);
		else
			return self::$camelsSnakeCase;
	}
	static function initialize(){
		extract(Config::model());
		if(!isset($frozen))
			$frozen = !control::devHas(control::dev_model);
		$port = isset($port)&&$port?';port='.$port:'';
		self::setup("$type:host=$host$port;dbname=$name",$user,$password,$frozen);
		if(control::devHas(control::dev_model_redbean))
			self::debug(true,2);
	}
	static function getModelClass($type){
		$type = self::toCamel($type);
		return class_exists($c='\\model\\Table_'.ucfirst($type))?$c:'\\model\\Table';
	}
	static function getClassModel($c){
		return lcfirst(ltrim(substr(ltrim($c,'\\'),11),'_'));
	}
	static function getTableColumnDef($t,$col,$key=null){
		$c = self::getModelClass($t);
		return $c::getColumnDef($col,$key);
	}
	function __call($f,$args){
		if(substr($f,-4)=='Call'&&method_exists($method=substr($f,0,-4)))
			return call_user_func_array(array($this,$method),array(array_shift($args),$args));
		return call_user_func_array(array('parent',$f),$args);
	}
	static function loadRow($type,$sql,$binds=array()){
		$b = R::convertToBeans($type,array(self::getRow($type,$sql,$binds)));
		return $b[0];
	}
	static function getRowX(){
		$a = func_get_args();
		$sql = array_shift($a);
		
	}
	static function findOrNewOne($type,$params=array(),$insert=null){
		$query = array();
		$bind = array();
		foreach($params as $k=>$v){
			$query[] = $k.'=?';
			$bind[] = $v;
		}
		$query = implode(' AND ',$query);
		$type = (array)$type;
		foreach($type as $t)
			if($bean = R::findOne($t,$query,$bind))
				break;
		if(!$bean){
			if(is_array($insert))
				$params = array_merge($params,$insert);
			$bean = R::newOne(array_pop($type),$params);
		}
		return $bean;
	}
	static function newOne($type,$params=array()){
		$bean = self::dispense($type);
		if(is_string($params))
			$params = array('label'=>$params);
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	static function queryWrapArg(&$query,$wrap,$arg){
		$x = explode('?',$query);
		$s = '';
		for($i=0;$i<count($x)-1;$i++)
			$s .= $x[$i].($arg===$i?$wrap:'?');
		$s .= array_pop($x);
		$query = $s;
		return $query;
	}
	static function storeMultiArray(array $a){
		foreach($a as $v)
			self::storeArray($v);
	}
	static function storeArray(array $a){
		$dataO = self::dispense($a['type']);
		foreach($a as $k=>$v){
			if($k=='type')
				continue;
			if(stripos($k,'own')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,3));
					$own = self::dispense($type);
					foreach((array)$v2 as $k3=>$v3)
						if($k3!='type')
							$own->$k3 = $v3;
					$dataO->{$k}[] = $own;
				}
			}
			elseif(stripos($k,'shared')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,6));
					if(!is_integer(filter_var($v2, FILTER_VALIDATE_INT)))
						$v2 = self::__callStatic('cell',array($type,array('select'=>'id','where'=>'label=?'),array($v2)));
					if($v2)
						$dataO->{$k}[] = self::load($type,$v2);
				}
			}
			else
				$dataO->$k = $v;
		}
		return self::store($dataO);
	}
	static function getAll4D(){
		return Query::explodeAggTable(call_user_func_array(array('self','getAll'),func_get_args()));
	}
	static function getRow4D(){
		return Query::explodeAgg(call_user_func_array(array('self','getRow'),func_get_args()));
	}
	static function __callStatic($f,$args){
		if(strpos($f,'loadUniq')===0&&ctype_upper(substr($f,8,1)))
			return self::loadUniq(array_shift($args),array_shift($args),lcfirst(substr($f,8)));
		return parent::__callStatic($f,$args);
	}
	static function loadUniq($table,$id,$column=null){
		if(is_array($table)){
			foreach($table as $tb)
				if($r = self::loadUniq($tb,$id,$column))
					return $r;
		}
		else{
			$table = self::toSnake($table);
			$c = R::getModelClass($table);
			if(!$column)
				$column = $c::getLoaderUniq($column);
			if(is_array($column)){
				foreach($column as $col)
					if($r = self::loadUniq($table,$id,$col))
						return $r;
			}
			else{
				return R::findOne($table,'WHERE '.$column.'=?',array($c::loadUniqFilter($id)));
			}
		}
	}
	static function load($type,$id,$column=null){
		if(is_array($type)){
			foreach($type as $tb)
				if($r = self::load($tb,$id,$column))
					return $r;
		}
		else{
			if(is_string($id)||$column)
				return self::loadUniq($type,$id,$column);
			return parent::load(self::toSnake($type),$id);
		}
	}
	static function dispense($typeOrBeanArray,$num=1,$alwaysReturnArray=false){
		if(is_array($typeOrBeanArray)){
			if (!isset( $typeOrBeanArray['_type'] ) )
				throw new RedException('Missing _type field.');
			$import = $typeOrBeanArray;
			$type = $import['_type'];
			unset( $import['_type'] );
		}else
			$type = $typeOrBeanArray;
		if(!ctype_alnum($type))
			throw new RedException('Invalid type: '.$type);
		$type = self::toSnake($type);
		$beanOrBeans = self::getRedBean()->dispense( $type, $num, $alwaysReturnArray );
		if (isset($import))
			$beanOrBeans->import( $import );
		return $beanOrBeans;
	}
	static function inspect($type=null){
		return parent::inspect($type?self::toSnake($type):null);
	}
	static function loadMulti($types,$id){
		if ( is_string( $types ) )
			$types = explode( ',', $types );
		if ( !is_array( $types ) )
			return array();
		foreach ( $types as $k => $typeItem )
			$types[$k] = self::load( $typeItem, $id );
		return $types;
	}
	static function dispenseAll($order,$onlyArrays=false){
		$list = array();
		foreach( explode( ',', $order ) as $order ) {
			if ( strpos( $order, '*' ) !== false ) {
				list( $type, $amount ) = explode( '*', $order );
			}
			else {
				$type   = $order;
				$amount = 1;
			}
			$list[] = self::dispense( $type, $amount, $onlyArrays );
		}
		return $list;
	}
	static function findOrDispense($type,$sql=NULL,$bindings=array()){
		return parent::findOrDispense( self::toSnake($type), $sql, $bindings );
	}
	static function find( $type, $sql = NULL, $bindings = array() ){
		return parent::find( self::toSnake($type), $sql, $bindings );
	}
	static function findAll( $type, $sql = NULL, $bindings = array() ){
		return parent::findAll( self::toSnake($type), $sql, $bindings );
	}
	static function findAndExport( $type, $sql = NULL, $bindings = array() ){
		return parent::findAndExport( self::toSnake($type), $sql, $bindings );
	}
	static function findOne( $type, $sql = NULL, $bindings = array() ){
		return parent::findOne( self::toSnake($type), $sql, $bindings );
	}
	static function findLast( $type, $sql = NULL, $bindings = array() ){
		return parent::findLast( self::toSnake($type), $sql, $bindings );
	}
	static function batch( $type, $ids ){
		return parent::batch( self::toSnake($type), $ids );
	}
	static function loadAll( $type, $ids ){
		return parent::loadAll( self::toSnake($type), $ids );
	}
	static function convertToBeans( $type, $rows ){
		return parent::convertToBeans( self::toSnake($type), $rows );
	}
	static function taggedAll( $beanType, $tagList ){
		return parent::taggedAll( self::toSnake($beanType), $tagList );
	}
	static function wipe( $beanType ){
		return parent::wipe( self::toSnake($beanType) );
	}
	static function countSQL( $type, $addSQL = '', $bindings = array() ){
		return parent::count( self::toSnake($type), $addSQL, $bindings );
	}

	static function queryObject( $type, $compo = array(), $composer='select', $writer=null ){
		$type = self::toSnake($type);
		$q = new Query4D($type,$method,$writer);
		foreach($compo as $method=>$args)
			call_user_func_array(array($q,$method),$args);
		return $q;
	}

	static function _uniqSetter($type,$values){
		if(is_string($values)){
			$c = self::getModelClass($type);
			$values = array($c::getLoadUniq()=>$values);
		}
		return $values;
	}

	static function setUniqCheck($b=null){
		Table::_checkUniq($b);
	}
	
	static function counter( $type, $compo = array() ){
		return self::queryObject($type, $compo)->count();
	}
	static function readerAll($type,$compo){
		$models = array();
		foreach(self::convertToBeans(self::queryObject($type, $compo)->table()) as $bean)
			$models[$bean->id] = $bean->box();
		return $models;
	}
	static function updater($type,$compo){
		return self::queryObject($type, $compo, 'update')->exec();
	}
	static function reader($type,$compo){
		$bean = self::convertToBeans(array(self::queryObject($type, $compo)->row()));
		$bean = array_shift($bean);
		return $bean->box();
	}

	static function create($type,$values=array()){
		return self::newOne($type,self::_uniqSetter($type,$values))->box();
	}
	static function read($mix){
		if(func_num_args()>1){
			$type = $mix;
			$id = func_get_arg(1);
		}
		else
			list($type,$id) = explode(':',$mix);
		return self::load($type,$id)->box();
	}
	static function update($mix,$values){
		$model = self::read($mix);
		foreach($values as $k=>$v)
			$model->$k = $v;
		return $model;
	}
	static function delete($mix){
		return self::trash(self::read($mix));
	}
	
}
R::initialize();