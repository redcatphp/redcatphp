<?php namespace Surikat\Validation; 
use Surikat\Crypto\PasswordHash;
//think to look http://www.php.net/manual/en/filter.filters.sanitize.php
abstract class Filter{
	const BASIC_TAGS = 'br,p,a,strong,b,i,em,img,blockquote,code,dd,dl,hr,h1,h2,h3,h4,h5,h6,label,ul,li,span,sub,sup';
	const ALL_TAGS = '!--,!DOCTYPE,a,abbr,acronym,address,applet,area,article,aside,audio,b,base,basefont,bdi,bdo,big,blockquote,body,br,button,canvas,caption,center,cite,code,col,colgroup,command,datalist,dd,del,details,dfn,dialog,dir,div,dl,dt,em,embed,fieldset,figcaption,figure,font,footer,form,frame,frameset,head,header,h1>-<h6,hr,html,i,iframe,img,input,ins,kbd,keygen,label,legend,li,link,map,mark,menu,meta,meter,nav,noframes,noscript,object,ol,optgroup,option,output,p,param,pre,progress,q,rp,rt,ruby,s,samp,script,section,select,small,source,span,strike,strong,style,sub,summary,sup,table,tbody,td,textarea,tfoot,th,thead,time,title,tr,track,tt,u,ul,var,video,wbr';
	static function trim($v){
		return trim($v);
	}
	static function rmpunctuation($v){
		return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $v);
	}
	static function sanitize_string($v){
		return filter_var($v, FILTER_SANITIZE_STRING);
	}
	static function url($v){
		return filter_var($v, FILTER_SANITIZE_URL);
	}
	static function urlencode($v){
		return filter_var($v, FILTER_SANITIZE_ENCODED);
	}
	static function htmlencode($v){
		return filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS);
	}
	static function sanitize_email($v){
		return filter_var($v, FILTER_SANITIZE_EMAIL);
	}
	static function sanitize_numbers($v){
		return filter_var($v, FILTER_SANITIZE_NUMBER_INT);
	}
	static function hash($v){
		return PasswordHash::make($v);
	}
	static function dpToDate($v){
		return self::dp_to_date($v);
	}

	/*
	$str = filter::strip_tags_basic('<p id="first"><b src="new-text" class=myclass><img src="test" width="120" height="100" /><test data-toto="ok" foo="bar">Hello <y>World</y></test></b></p>',
		array(
			'img'=>'src,width,height',
			'test'=>'data-*',
		)
	);
	*/
	static $basic_tags_map = [
		'img'=>'src,width,height,alt',
		'a'=>'href,title',
	];
	static $basic_attrs = [
		
	];
	static function strip_tags_basic($str,$map=null){
		$globals_attrs = [];
		$map = $map?array_merge($map,self::$basic_tags_map):self::$basic_tags_map;
		return self::strip_tags($str,explode(',',self::BASIC_TAGS),self::$basic_attrs,$map);
	}
	static function strip_tags($str,$tags,$globals_attrs=null,$map=null){
		$total = strlen($str);
		$state = 1;
		$nstr = '';
		if($tags&&is_string($tags))
			$tags = explode(',',$tags);
		if($globals_attrs&&is_string($globals_attrs))
			$globals_attrs = explode(',',$globals_attrs);
		if($map)
			$tags = $tags?array_merge($tags,array_keys($map)):array_keys($map);
		for($i=0;$i<$total;$i++){
			$c = $str{$i};
			if($c=='<'){
				$tag = '';
				while($c!='>'){
					$c = $str{$i};
					$tag .= $c;
					$i++;
					if($c=='='){
						$sep = '';
						while($sep!='"'&&$sep!="'"){
							$sep = $str{$i};
							if($sep!='"'&&$sep!="'"&&$sep!=' '){
								$sep = ' ';
								while($c!=$sep&&$c!='/'&&$c!='>'){
									$c = $str{$i};
									$tag .= $c;
									$i++;
								}
								break;
							}
							$i++;
						}
						if($sep!=' '){
							$tag .= $sep;
							while($c!=$sep){
								$c = $str{$i};
								$tag .= $c;
								$i++;
							}
							$i-=1;
						}
					}
				}
				$i-=1;
				$tag = substr($tag,1,-1);
				if(strpos($tag,'/')===0){
					if(in_array(substr($tag,1),$tags))
						$nstr .= "<$tag>";
				}
				else{
					$e = strrpos($tag,'/')===strlen($tag)-1?'/':'';
					if($e)
						$tag = substr($tag,0,-1);
					if(($pos=strpos($tag,' '))!==false){
						$attr = substr($tag,$pos+1);
						$tag = substr($tag,0,$pos);
					}
					else
						$attr = '';
					if(!in_array($tag,$tags))
						continue;
					$allowed = isset($map[$tag])?(is_string($map[$tag])?explode(',',(string)$map[$tag]):$map[$tag]):[];
					$x = explode(' ',$attr);
					$attr = '';
					foreach($x as $_x){
						@list($k,$v) = explode('=',$_x);
						$v = trim($v,'"');
						$v = trim($v,"'");
						if($v)
							$v = "=\"$v\"";
						$ok = false;
						if(($pos=strpos($k,'-'))!==false){
							$key = substr($k,0,$pos+1).'*';
							if(in_array($key,$allowed)||($globals_attrs&&in_array($key,$globals_attrs)))
								$ok = true;
						}
						if(in_array($k,$allowed)||($globals_attrs&&in_array($k,$globals_attrs)))
							$ok = true;
						if($ok)
							$attr .= ' '.$k.$v;
					}
					$nstr .= "<$tag$attr$e>";
				}
			}
			else
				$nstr .= $c;
		}
		return $nstr;
	}
	
	static function multi_bin($v){
		if(is_array($v)){
			$binary = 0;
			foreach($v as $bin)
				$binary |= (int)$bin;
			return $binary;
		}
		return (int)$v;
	}
}