<?php
namespace Surikat\Component\SyntaxedCSS;
use stdClass;
use Exception;
use Surikat\Component\DependencyInjection\MutatorMagicTrait;
//+surikat addon for php Mixin/Include special support
//		sass with superpower of php for complex and plainly maitrised advanced syntax function
/**
 * SCSS compiler written in PHP
 *
 * @copyright 2012-2013 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/gpl-license GPL-3.0
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://leafo.net/scssphp
 */

/**
 * The scss compiler and parser.
 *
 * Converting SCSS to CSS is a three stage process. The incoming file is parsed
 * by `scssc_parser` into a syntax tree, then it is compiled into another tree
 * representing the CSS structure by `scssc`. The CSS tree is fed into a
 * formatter, like `scssc_formatter` which then outputs CSS as a string.
 *
 * During the first compile, all values are *reduced*, which means that their
 * types are brought to the lowest form before being dump as strings. This
 * handles math equations, variable dereferences, and the like.
 *
 * The `parse` function of `scssc` is the entry point.
 *
 * In summary:
 *
 * The `scssc` class creates an instance of the parser, feeds it SCSS code,
 * then transforms the resulting tree to a CSS tree. This class also holds the
 * evaluation context, such as all available mixins and variables at any given
 * time.
 *
 * The `scssc_parser` class is only concerned with parsing its input.
 *
 * The `scssc_formatter` takes a CSS tree, and dumps it to a formatted string,
 * handling things like indentation.
 */

/**
 * SCSS compiler
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 * + addons by surikat
 */
//addons by surikat

class Scss {
	use MutatorMagicTrait;
	
	public $VERSION = "v0.0.9.surikat";

	static protected $operatorNames = [
		'+' => "add",
		'-' => "sub",
		'*' => "mul",
		'/' => "div",
		'%' => "mod",

		'==' => "eq",
		'!=' => "neq",
		'<' => "lt",
		'>' => "gt",

		'<=' => "lte",
		'>=' => "gte",
	];

	static protected $namespaces = [
		"special" => "%",
		"mixin" => "@",
		"function" => "^",
	];

	static protected $unitTable = [
		"in" => [
			"in" => 1,
			"pt" => 72,
			"pc" => 6,
			"cm" => 2.54,
			"mm" => 25.4,
			"px" => 96,
		]
	];

	static public $true = ["keyword", "true"];
	static public $false = ["keyword", "false"];
	static public $null = ["null"];

	static public $defaultValue = ["keyword", ""];
	static public $selfSelector = ["self"];
	
	protected $importPaths = [""];
	protected $importCache = [];

	protected $userFunctions = [];
	
	protected $parsedFiles = [];
	protected $extends = [];
	protected $extendsMap = [];
	protected $commentsSeen = [];

	protected $numberPrecision = 5;
	protected $formatter;
	var $sourcePos;
	
	public function compile($code, $name=null) {
		//addon by surikat
		$GLOBALS['scss_origin'] = &$this;
		
		$this->indentLevel = -1;
		$this->commentsSeen = [];
		$this->extends = [];
		$this->extendsMap = [];

		$locale = setlocale(LC_NUMERIC, 0);
		setlocale(LC_NUMERIC, "C");

		$this->parsedFiles = [];
		//$this->parser = new Parser($name);
		$this->parser = new Parser($name,true,$this); //addon by surikat
		$tree = $this->parser->parse($code);

		$this->formatter = new FormatterNested();

		$this->env = null;
		$this->scope = null;

		$this->compileRoot($tree);

		$out = $this->formatter->format($this->scope);

		setlocale(LC_NUMERIC, $locale);
		return $out;
	}

	protected function isSelfExtend($target, $origin) {
		foreach ($origin as $sel) {
			if (in_array($target, $sel)) {
				return true;
			}
		}

		return false;
	}

	protected function pushExtends($target, $origin) {
		if ($this->isSelfExtend($target, $origin)) {
			return;
		}

		$i = count($this->extends);
		$this->extends[] = [$target, $origin];

		foreach ($target as $part) {
			if (isset($this->extendsMap[$part])) {
				$this->extendsMap[$part][] = $i;
			} else {
				$this->extendsMap[$part] = [$i];
			}
		}
	}

	protected function makeOutputBlock($type, $selectors = null) {
		$out = new stdClass;
		$out->type = $type;
		$out->lines = [];
		$out->children = [];
		$out->parent = $this->scope;
		$out->selectors = $selectors;
		$out->depth = $this->env->depth;

		return $out;
	}

	protected function matchExtendsSingle($single, &$outOrigin) {
		$counts = [];
		foreach ($single as $part) {
			if (!is_string($part)) return false; // hmm

			if (isset($this->extendsMap[$part])) {
				foreach ($this->extendsMap[$part] as $idx) {
					$counts[$idx] =
						isset($counts[$idx]) ? $counts[$idx] + 1 : 1;
				}
			}
		}

		$outOrigin = [];
		$found = false;

		foreach ($counts as $idx => $count) {
			list($target, $origin) = $this->extends[$idx];

			// check count
			if ($count != count($target)) continue;

			// check if target is subset of single
			if (array_diff(array_intersect($single, $target), $target)) continue;

			$rem = array_diff($single, $target);

			foreach ($origin as $j => $new) {
				// prevent infinite loop when target extends itself
				foreach ($new as $new_selector) {
					if (!array_diff($single, $new_selector)) {
						continue 2;
					}
				}

				$origin[$j][count($origin[$j]) - 1] = $this->combineSelectorSingle(end($new), $rem);
			}

			$outOrigin = array_merge($outOrigin, $origin);

			$found = true;
		}
		return $found;
	}

	protected function combineSelectorSingle($base, $other) {
		$tag = null;
		$out = [];

		foreach ([$base, $other] as $single) {
			foreach ($single as $part) {
				if (preg_match('/^[^\[.#:]/', $part)) {
					$tag = $part;
				} else {
					$out[] = $part;
				}
			}
		}

		if ($tag) {
			array_unshift($out, $tag);
		}

		return $out;
	}

	protected function matchExtends($selector, &$out, $from = 0, $initial=true) {
		foreach ($selector as $i => $part) {
			if ($i < $from) continue;

			if ($this->matchExtendsSingle($part, $origin)) {
				$before = array_slice($selector, 0, $i);
				$after = array_slice($selector, $i + 1);

				foreach ($origin as $new) {
					$k = 0;

					// remove shared parts
					if ($initial) {
						foreach ($before as $k => $val) {
							if (!isset($new[$k]) || $val != $new[$k]) {
								break;
							}
						}
					}

					$result = array_merge(
						$before,
						$k > 0 ? array_slice($new, $k) : $new,
						$after);


					if ($result == $selector) continue;
					$out[] = $result;

					// recursively check for more matches
					$this->matchExtends($result, $out, $i, false);

					// selector sequence merging
					if (!empty($before) && count($new) > 1) {
						$result2 = array_merge(
							array_slice($new, 0, -1),
							$k > 0 ? array_slice($before, $k) : $before,
							array_slice($new, -1),
							$after);

						$out[] = $result2;
					}
				}
			}
		}
	}

	protected function flattenSelectors($block, $parentKey = null) {
		if ($block->selectors) {
			$selectors = [];
			foreach ($block->selectors as $s) {
				$selectors[] = $s;
				if (!is_array($s)) continue;
				// check extends
				if (!empty($this->extendsMap)) {
					$this->matchExtends($s, $selectors);
				}
			}

			$block->selectors = [];
			$placeholderSelector = false;
			foreach ($selectors as $selector) {
				if ($this->hasSelectorPlaceholder($selector)) {
					$placeholderSelector = true;
					continue;
				}
				$block->selectors[] = $this->compileSelector($selector);
			}

			if ($placeholderSelector && 0 == count($block->selectors) && null !== $parentKey) {
				unset($block->parent->children[$parentKey]);
				return;
			}
		}

		foreach ($block->children as $key => $child) {
			$this->flattenSelectors($child, $key);
		}
	}

	protected function compileRoot($rootBlock) {
		$this->pushEnv($rootBlock);
		$this->scope = $this->makeOutputBlock("root");

		$this->compileChildren($rootBlock->children, $this->scope);
		$this->flattenSelectors($this->scope);

		$this->popEnv();
	}

	protected function compileMedia($media) {
		$this->pushEnv($media);
		$parentScope = $this->mediaParent($this->scope);

		$this->scope = $this->makeOutputBlock("media", [
			$this->compileMediaQuery($this->multiplyMedia($this->env))]
		);

		$parentScope->children[] = $this->scope;

		// top level properties in a media cause it to be wrapped
		$needsWrap = false;
		foreach ($media->children as $child) {
			$type = $child[0];
			if ($type !== 'block' && $type !== 'media' && $type !== 'directive') {
				$needsWrap = true;
				break;
			}
		}

		if ($needsWrap) {
			$wrapped = (object)[
				"selectors" => [],
				"children" => $media->children
			];
			$media->children = [["block", $wrapped]];
		}

		$this->compileChildren($media->children, $this->scope);

		$this->scope = $this->scope->parent;
		$this->popEnv();
	}

	protected function mediaParent($scope) {
		while (!empty($scope->parent)) {
			if (!empty($scope->type) && $scope->type != "media") {
				break;
			}
			$scope = $scope->parent;
		}

		return $scope;
	}

	// TODO refactor compileNestedBlock and compileMedia into same thing
	protected function compileNestedBlock($block, $selectors) {
		$this->pushEnv($block);

		$this->scope = $this->makeOutputBlock($block->type, $selectors);
		$this->scope->parent->children[] = $this->scope;
		$this->compileChildren($block->children, $this->scope);

		$this->scope = $this->scope->parent;
		$this->popEnv();
	}

	/**
	 * Recursively compiles a block.
	 *
	 * A block is analogous to a CSS block in most cases. A single SCSS document
	 * is encapsulated in a block when parsed, but it does not have parent tags
	 * so all of its children appear on the root level when compiled.
	 *
	 * Blocks are made up of selectors and children.
	 *
	 * The children of a block are just all the blocks that are defined within.
	 *
	 * Compiling the block involves pushing a fresh environment on the stack,
	 * and iterating through the props, compiling each one.
	 *
	 * @see scss::compileChild()
	 *
	 * @param \StdClass $block
	 */
	protected function compileBlock($block) {
		$env = $this->pushEnv($block);

		$env->selectors =
			array_map([$this, "evalSelector"], $block->selectors);

		$out = $this->makeOutputBlock(null, $this->multiplySelectors($env));
		$this->scope->children[] = $out;
		$this->compileChildren($block->children, $out);

		$this->popEnv();
	}

	// joins together .classes and #ids
	protected function flattenSelectorSingle($single) {
		$joined = [];
		foreach ($single as $part) {
			if (empty($joined) ||
				!is_string($part) ||
				preg_match('/[\[.:#%]/', $part))
			{
				$joined[] = $part;
				continue;
			}

			if (is_array(end($joined))) {
				$joined[] = $part;
			} else {
				$joined[count($joined) - 1] .= $part;
			}
		}

		return $joined;
	}

	// replaces all the interpolates
	protected function evalSelector($selector) {
		return array_map([$this, "evalSelectorPart"], $selector);
	}

	protected function evalSelectorPart($piece) {
		foreach ($piece as &$p) {
			if (!is_array($p)) continue;

			switch ($p[0]) {
			case "interpolate":
				$p = $this->compileValue($p);
				break;
			case "string":
				$p = $this->compileValue($p);
				break;
			}
		}

		return $this->flattenSelectorSingle($piece);
	}

	// compiles to string
	// self(&) should have been replaced by now
	protected function compileSelector($selector) {
		if (!is_array($selector)) return $selector; // media and the like

		return implode(" ", array_map(
			[$this, "compileSelectorPart"], $selector));
	}

	protected function compileSelectorPart($piece) {
		foreach ($piece as &$p) {
			if (!is_array($p)) continue;

			switch ($p[0]) {
			case "self":
				$p = "&";
				break;
			default:
				$p = $this->compileValue($p);
				break;
			}
		}

		return implode($piece);
	}

	protected function hasSelectorPlaceholder($selector)
	{
		if (!is_array($selector)) return false;

		foreach ($selector as $parts) {
			foreach ($parts as $part) {
				if ('%' == $part[0]) {
					return true;
				}
			}
		}

		return false;
	}

	protected function compileChildren($stms, $out) {
		foreach ($stms as $stm) {
			$ret = $this->compileChild($stm, $out);
			if (!is_null($ret)) return $ret;
		}
	}

	protected function compileMediaQuery($queryList) {
		$out = "@media";
		$first = true;
		foreach ($queryList as $query){
			$parts = [];
			foreach ($query as $q) {
				switch ($q[0]) {
					case "mediaType":
						$parts[] = implode(" ", array_map([$this, "compileValue"], array_slice($q, 1)));
						break;
					case "mediaExp":
						if (isset($q[2])) {
							$parts[] = "(". $this->compileValue($q[1]) . $this->formatter->assignSeparator . $this->compileValue($q[2]) . ")";
						} else {
							$parts[] = "(" . $this->compileValue($q[1]) . ")";
						}
						break;
				}
			}
			if (!empty($parts)) {
				if ($first) {
					$first = false;
					$out .= " ";
				} else {
					$out .= $this->formatter->tagSeparator;
				}
				$out .= implode(" and ", $parts);
			}
		}
		return $out;
	}

	// returns true if the value was something that could be imported
	protected function compileImport($rawPath, $out) {
		if ($rawPath[0] == "string") {
			$path = $this->compileStringContent($rawPath);
			if ($path = $this->findImport($path)) {
				$this->importFile($path, $out);
				return true;
			}
			return false;
		}
		if ($rawPath[0] == "list") {
			// handle a list of strings
			if (count($rawPath[2]) == 0) return false;
			foreach ($rawPath[2] as $path) {
				if ($path[0] != "string") return false;
			}

			foreach ($rawPath[2] as $path) {
				$this->compileImport($path, $out);
			}

			return true;
		}

		return false;
	}

	// return a value to halt execution
	protected function compileChild($child, $out) {
		$this->sourcePos = isset($child[-1]) ? $child[-1] : -1;
		$this->sourceParser = isset($child[-2]) ? $child[-2] : $this->parser;

		switch ($child[0]) {
		case "import":
			list(,$rawPath) = $child;
			$rawPath = $this->reduce($rawPath);
			//if (!$this->compileImport($rawPath, $out)) {
			if (!$this->compileImport($rawPath, $out)&&(pathinfo($this->compileValue($rawPath),PATHINFO_EXTENSION)=='css')) { //addon by surikat
				$out->lines[] = "@import " . $this->compileValue($rawPath) . ";";
			}
			break;
		case "directive":
			list(, $directive) = $child;
			$s = "@" . $directive->name;
			if (!empty($directive->value)) {
				$s .= " " . $this->compileValue($directive->value);
			}
			$this->compileNestedBlock($directive, [$s]);
			break;
		case "media":
			$this->compileMedia($child[1]);
			break;
		case "block":
			$this->compileBlock($child[1]);
			break;
		case "charset":
			$out->lines[] = "@charset ".$this->compileValue($child[1]).";";
			break;
		case "assign":
			list(,$name, $value) = $child;
			if ($name[0] == "var") {
				$isDefault = !empty($child[3]);

				if ($isDefault) {
					$existingValue = $this->get($name[1], true);
					$shouldSet = $existingValue === true || $existingValue == self::$null;
				}

				if (!$isDefault || $shouldSet) {
					$this->set($name[1], $this->reduce($value));
				}
				break;
			}

			// if the value reduces to null from something else then
			// the property should be discarded
			if ($value[0] != "null") {
				$value = $this->reduce($value);
				if ($value[0] == "null") {
					break;
				}
			}

			$compiledValue = $this->compileValue($value);
			$out->lines[] = $this->formatter->property(
				$this->compileValue($name),
				$compiledValue);
			break;
		case "comment":
			$out->lines[] = $child[1];
			break;
		case "mixin":
		case "function":
			list(,$block) = $child;
			$this->set(self::$namespaces[$block->type] . $block->name, $block);
			break;
		case "extend":
			list(, $selectors) = $child;
			foreach ($selectors as $sel) {
				// only use the first one
				$sel = current($this->evalSelector($sel));

				//surikat addon for autoload
				//if((strpos($strExt=implode('',$sel),'%')===0)&&(($pathF=$this->findImport('extend/'.substr(str_replace('%','.',$strExt),1)))&&!in_array($pathF,$this->parsedFiles))&&($realPath=realpath($pathF))&&!isset($this->importCache[$realPath])){
					//$this->importFile($pathF,$out);
					//$this->pushExtends($sel, array(array(array(''))));
				//}
				//else
					$this->pushExtends($sel, $out->selectors);
			}
			break;
		case "if":
			list(, $if) = $child;
			if ($this->isTruthy($this->reduce($if->cond, true))) {
				return $this->compileChildren($if->children, $out);
			} else {
				foreach ($if->cases as $case) {
					if ($case->type == "else" ||
						$case->type == "elseif" && $this->isTruthy($this->reduce($case->cond)))
					{
						return $this->compileChildren($case->children, $out);
					}
				}
			}
			break;
		case "return":
			return $this->reduce($child[1], true);
		case "each":
			list(,$each) = $child;
			$list = $this->coerceList($this->reduce($each->list));
			foreach ($list[2] as $item) {
				$this->pushEnv();
				$this->set($each->var, $item);
				// TODO: allow return from here
				$this->compileChildren($each->children, $out);
				$this->popEnv();
			}
			break;
		case "while":
			list(,$while) = $child;
			while ($this->isTruthy($this->reduce($while->cond, true))) {
				$ret = $this->compileChildren($while->children, $out);
				if ($ret) return $ret;
			}
			break;
		case "for":
			list(,$for) = $child;
			$start = $this->reduce($for->start, true);
			$start = $start[1];
			$end = $this->reduce($for->end, true);
			$end = $end[1];
			$d = $start < $end ? 1 : -1;

			while (true) {
				if ((!$for->until && $start - $d == $end) ||
					($for->until && $start == $end))
				{
					break;
				}

				$this->set($for->var, ["number", $start, ""]);
				$start += $d;

				$ret = $this->compileChildren($for->children, $out);
				if ($ret) return $ret;
			}

			break;
		case "nestedprop":
			list(,$prop) = $child;
			$prefixed = [];
			$prefix = $this->compileValue($prop->prefix) . "-";
			foreach ($prop->children as $child) {
				if ($child[0] == "assign") {
					array_unshift($child[1][2], $prefix);
				}
				if ($child[0] == "nestedprop") {
					array_unshift($child[1]->prefix[2], $prefix);
				}
				$prefixed[] = $child;
			}
			$this->compileChildren($prefixed, $out);
			break;
		case "include": // including a mixin
			list(,$name, $argValues, $content) = $child;
			$mixin = $this->get(self::$namespaces["mixin"] . $name, false);
			//autoload include - surikat addon
			//if (!$mixin&&($path=$this->findImport('include/'.$name))){
				//$this->importFile($path,$out);
				//$mixin = $this->get(self::$namespaces["mixin"] . $name, false);
			//}
			if (!$mixin)
				$this->throwError("Undefined mixin $name");

			$callingScope = $this->env;

			// push scope, apply args
			$this->pushEnv();
			if ($this->env->depth > 0) {
				$this->env->depth--;
			}

			if (!is_null($content)) {
				$content->scope = $callingScope;
				$this->setRaw(self::$namespaces["special"] . "content", $content);
			}

			if (!is_null($mixin->args)) {
				$this->applyArguments($mixin->args, $argValues);
			}

			foreach ($mixin->children as $child) {
				$this->compileChild($child, $out);
			}

			$this->popEnv();

			break;
		case "mixin_content":
			$content = $this->get(self::$namespaces["special"] . "content");
			if (is_null($content)) {
				$this->throwError("Expected @content inside of mixin");
			}

			$strongTypes = ['include', 'block', 'for', 'while'];
			foreach ($content->children as $child) {
				$this->storeEnv = (in_array($child[0], $strongTypes))
					? null
					: $content->scope;

				$this->compileChild($child, $out);
			}

			unset($this->storeEnv);
			break;
		case "debug":
			list(,$value, $pos) = $child;
			$line = $this->parser->getLineNo($pos);
			$value = $this->compileValue($this->reduce($value, true));
			fwrite(STDERR, "Line $line DEBUG: $value\n");
			break;
		default:
			$this->throwError("unknown child type: $child[0]");
		}
	}

	protected function expToString($exp) {
		list(, $op, $left, $right, $inParens, $whiteLeft, $whiteRight) = $exp;
		$content = [$this->reduce($left)];
		if ($whiteLeft) $content[] = " ";
		$content[] = $op;
		if ($whiteRight) $content[] = " ";
		$content[] = $this->reduce($right);
		return ["string", "", $content];
	}

	protected function isTruthy($value) {
		return $value != self::$false && $value != self::$null;
	}

	// should $value cause its operand to eval
	protected function shouldEval($value) {
		switch ($value[0]) {
		case "exp":
			if ($value[1] == "/") {
				return $this->shouldEval($value[2], $value[3]);
			}
		case "var":
		case "fncall":
			return true;
		}
		return false;
	}

	protected function reduce($value, $inExp = false) {
		list($type) = $value;
		switch ($type) {
			case "exp":
				list(, $op, $left, $right, $inParens) = $value;
				$opName = isset(self::$operatorNames[$op]) ? self::$operatorNames[$op] : $op;

				$inExp = $inExp || $this->shouldEval($left) || $this->shouldEval($right);

				$left = $this->reduce($left, true);
				$right = $this->reduce($right, true);

				// only do division in special cases
				if ($opName == "div" && !$inParens && !$inExp) {
					if ($left[0] != "color" && $right[0] != "color") {
						return $this->expToString($value);
					}
				}

				$left = $this->coerceForExpression($left);
				$right = $this->coerceForExpression($right);

				$ltype = $left[0];
				$rtype = $right[0];

				// this tries:
				// 1. op_[op name]_[left type]_[right type]
				// 2. op_[left type]_[right type] (passing the op as first arg
				// 3. op_[op name]
				$fn = "op_${opName}_${ltype}_${rtype}";
				if (method_exists($this, $fn) ||
					(($fn = "op_${ltype}_${rtype}") &&
						method_exists($this, $fn) &&
						$passOp = true) ||
					(($fn = "op_${opName}") &&
						method_exists($this, $fn) &&
						$genOp = true))
				{
					$unitChange = false;
					if (!isset($genOp) &&
						$left[0] == "number" && $right[0] == "number")
					{
						if ($opName == "mod" && $right[2] != "") {
							$this->throwError("Cannot modulo by a number with units: $right[1]$right[2].");
						}

						$unitChange = true;
						$emptyUnit = $left[2] == "" || $right[2] == "";
						$targetUnit = "" != $left[2] ? $left[2] : $right[2];

						if ($opName != "mul") {
							$left[2] = "" != $left[2] ? $left[2] : $targetUnit;
							$right[2] = "" != $right[2] ? $right[2] : $targetUnit;
						}

						if ($opName != "mod") {
							$left = $this->normalizeNumber($left);
							$right = $this->normalizeNumber($right);
						}

						if ($opName == "div" && !$emptyUnit && $left[2] == $right[2]) {
							$targetUnit = "";
						}

						if ($opName == "mul") {
							$left[2] = "" != $left[2] ? $left[2] : $right[2];
							$right[2] = "" != $right[2] ? $right[2] : $left[2];
						} elseif ($opName == "div" && $left[2] == $right[2]) {
							$left[2] = "";
							$right[2] = "";
						}
					}

					$shouldEval = $inParens || $inExp;
					if (isset($passOp)) {
						$out = $this->$fn($op, $left, $right, $shouldEval);
					} else {
						$out = $this->$fn($left, $right, $shouldEval);
					}

					if (!is_null($out)) {
						if ($unitChange && $out[0] == "number") {
							$out = $this->coerceUnit($out, $targetUnit);
						}
						return $out;
					}
				}

				return $this->expToString($value);
			case "unary":
				list(, $op, $exp, $inParens) = $value;
				$inExp = $inExp || $this->shouldEval($exp);

				$exp = $this->reduce($exp);
				if ($exp[0] == "number") {
					switch ($op) {
					case "+":
						return $exp;
					case "-":
						$exp[1] *= -1;
						return $exp;
					}
				}

				if ($op == "not") {
					if ($inExp || $inParens) {
						if ($exp == self::$false) {
							return self::$true;
						} else {
							return self::$false;
						}
					} else {
						$op = $op . " ";
					}
				}

				return ["string", "", [$op, $exp]];
			case "var":
				list(, $name) = $value;
				return $this->reduce($this->get($name));
			case "list":
				foreach ($value[2] as &$item) {
					$item = $this->reduce($item);
				}
				return $value;
			case "string":
				foreach ($value[2] as &$item) {
					if (is_array($item)) {
						$item = $this->reduce($item);
					}
				}
				return $value;
			case "interpolate":
				$value[1] = $this->reduce($value[1]);
				return $value;
			case "fncall":
				list(,$name, $argValues) = $value;

				// user defined function?
				$func = $this->get(self::$namespaces["function"] . $name, false);
				if ($func) {
					$this->pushEnv();

					// set the args
					if (isset($func->args)) {
						$this->applyArguments($func->args, $argValues);
					}

					// throw away lines and children
					$tmp = (object)[
						"lines" => [],
						"children" => []
					];
					$ret = $this->compileChildren($func->children, $tmp);
					$this->popEnv();

					return is_null($ret) ? self::$defaultValue : $ret;
				}

				// built in function
				if ($this->callBuiltin($name, $argValues, $returnValue)) {
					return $returnValue;
				}

				// need to flatten the arguments into a list
				$listArgs = [];
				foreach ((array)$argValues as $arg) {
					if (empty($arg[0])) {
						$listArgs[] = $this->reduce($arg[1]);
					}
				}
				return ["function", $name, ["list", ",", $listArgs]];
			default:
				return $value;
		}
	}

	public function normalizeValue($value) {
		$value = $this->coerceForExpression($this->reduce($value));
		list($type) = $value;

		switch ($type) {
		case "list":
			$value = $this->extractInterpolation($value);
			if ($value[0] != "list") {
				return ["keyword", $this->compileValue($value)];
			}
			foreach ($value[2] as $key => $item) {
				$value[2][$key] = $this->normalizeValue($item);
			}
			return $value;
		case "number":
			return $this->normalizeNumber($value);
		default:
			return $value;
		}
	}

	// just does physical lengths for now
	protected function normalizeNumber($number) {
		list(, $value, $unit) = $number;
		if (isset(self::$unitTable["in"][$unit])) {
			$conv = self::$unitTable["in"][$unit];
			return ["number", $value / $conv, "in"];
		}
		return $number;
	}

	// $number should be normalized
	protected function coerceUnit($number, $unit) {
		list(, $value, $baseUnit) = $number;
		if (isset(self::$unitTable[$baseUnit][$unit])) {
			$value = $value * self::$unitTable[$baseUnit][$unit];
		}

		return ["number", $value, $unit];
	}

	protected function op_add_number_number($left, $right) {
		return ["number", $left[1] + $right[1], $left[2]];
	}

	protected function op_mul_number_number($left, $right) {
		return ["number", $left[1] * $right[1], $left[2]];
	}

	protected function op_sub_number_number($left, $right) {
		return ["number", $left[1] - $right[1], $left[2]];
	}

	protected function op_div_number_number($left, $right) {
		return ["number", $left[1] / $right[1], $left[2]];
	}

	protected function op_mod_number_number($left, $right) {
		return ["number", $left[1] % $right[1], $left[2]];
	}

	// adding strings
	protected function op_add($left, $right) {
		if ($strLeft = $this->coerceString($left)) {
			if ($right[0] == "string") {
				$right[1] = "";
			}
			$strLeft[2][] = $right;
			return $strLeft;
		}

		if ($strRight = $this->coerceString($right)) {
			if ($left[0] == "string") {
				$left[1] = "";
			}
			array_unshift($strRight[2], $left);
			return $strRight;
		}
	}

	protected function op_and($left, $right, $shouldEval) {
		if (!$shouldEval) return;
		if ($left != self::$false) return $right;
		return $left;
	}

	protected function op_or($left, $right, $shouldEval) {
		if (!$shouldEval) return;
		if ($left != self::$false) return $left;
		return $right;
	}

	protected function op_color_color($op, $left, $right) {
		$out = ['color'];
		foreach (range(1, 3) as $i) {
			$lval = isset($left[$i]) ? $left[$i] : 0;
			$rval = isset($right[$i]) ? $right[$i] : 0;
			switch ($op) {
			case '+':
				$out[] = $lval + $rval;
				break;
			case '-':
				$out[] = $lval - $rval;
				break;
			case '*':
				$out[] = $lval * $rval;
				break;
			case '%':
				$out[] = $lval % $rval;
				break;
			case '/':
				if ($rval == 0) {
					$this->throwError("color: Can't divide by zero");
				}
				$out[] = $lval / $rval;
				break;
			case "==":
				return $this->op_eq($left, $right);
			case "!=":
				return $this->op_neq($left, $right);
			default:
				$this->throwError("color: unknown op $op");
			}
		}

		if (isset($left[4])) $out[4] = $left[4];
		elseif (isset($right[4])) $out[4] = $right[4];

		return $this->fixColor($out);
	}

	protected function op_color_number($op, $left, $right) {
		$value = $right[1];
		return $this->op_color_color($op, $left,
			["color", $value, $value, $value]);
	}

	protected function op_number_color($op, $left, $right) {
		$value = $left[1];
		return $this->op_color_color($op,
			["color", $value, $value, $value], $right);
	}

	protected function op_eq($left, $right) {
		if (($lStr = $this->coerceString($left)) && ($rStr = $this->coerceString($right))) {
			$lStr[1] = "";
			$rStr[1] = "";
			return $this->toBool($this->compileValue($lStr) == $this->compileValue($rStr));
		}

		return $this->toBool($left == $right);
	}

	protected function op_neq($left, $right) {
		return $this->toBool($left != $right);
	}

	protected function op_gte_number_number($left, $right) {
		return $this->toBool($left[1] >= $right[1]);
	}

	protected function op_gt_number_number($left, $right) {
		return $this->toBool($left[1] > $right[1]);
	}

	protected function op_lte_number_number($left, $right) {
		return $this->toBool($left[1] <= $right[1]);
	}

	protected function op_lt_number_number($left, $right) {
		return $this->toBool($left[1] < $right[1]);
	}

	public function toBool($thing) {
		return $thing ? self::$true : self::$false;
	}

	/**
	 * Compiles a primitive value into a CSS property value.
	 *
	 * Values in scssphp are typed by being wrapped in arrays, their format is
	 * typically:
	 *
	 *     array(type, contents [, additional_contents]*)
	 *
	 * The input is expected to be reduced. This function will not work on
	 * things like expressions and variables.
	 *
	 * @param array $value
	 */
	protected function compileValue($value) {
		$value = $this->reduce($value);

		list($type) = $value;
		switch ($type) {
		case "keyword":
			return $value[1];
		case "color":
			// [1] - red component (either number for a %)
			// [2] - green component
			// [3] - blue component
			// [4] - optional alpha component
			list(, $r, $g, $b) = $value;

			$r = round($r);
			$g = round($g);
			$b = round($b);

			if (count($value) == 5 && $value[4] != 1) { // rgba
				return 'rgba('.$r.', '.$g.', '.$b.', '.$value[4].')';
			}

			$h = sprintf("#%02x%02x%02x", $r, $g, $b);

			// Converting hex color to short notation (e.g. #003399 to #039)
			if ($h[1] === $h[2] && $h[3] === $h[4] && $h[5] === $h[6]) {
				$h = '#' . $h[1] . $h[3] . $h[5];
			}

			return $h;
		case "number":
			return round($value[1], $this->numberPrecision) . $value[2];
		case "string":
			return $value[1] . $this->compileStringContent($value) . $value[1];
		case "function":
			$args = !empty($value[2]) ? $this->compileValue($value[2]) : "";
			return "$value[1]($args)";
		case "list":
			$value = $this->extractInterpolation($value);
			if ($value[0] != "list") return $this->compileValue($value);

			list(, $delim, $items) = $value;

			$filtered = [];
			foreach ($items as $item) {
				if ($item[0] == "null") continue;
				$filtered[] = $this->compileValue($item);
			}

			return implode("$delim ", $filtered);
		case "interpolated": # node created by extractInterpolation
			list(, $interpolate, $left, $right) = $value;
			list(,, $whiteLeft, $whiteRight) = $interpolate;

			$left = count($left[2]) > 0 ?
				$this->compileValue($left).$whiteLeft : "";

			$right = count($right[2]) > 0 ?
				$whiteRight.$this->compileValue($right) : "";

			return $left.$this->compileValue($interpolate).$right;

		case "interpolate": # raw parse node
			list(, $exp) = $value;

			// strip quotes if it's a string
			$reduced = $this->reduce($exp);
			switch ($reduced[0]) {
				case "string":
					$reduced = ["keyword",
						$this->compileStringContent($reduced)];
					break;
				case "null":
					$reduced = ["keyword", ""];
			}

			return $this->compileValue($reduced);
		case "null":
			return "null";
		default:
			$this->throwError("unknown value type: $type");
		}
	}

	protected function compileStringContent($string) {
		$parts = [];
		foreach ($string[2] as $part) {
			if (is_array($part)) {
				$parts[] = $this->compileValue($part);
			} else {
				$parts[] = $part;
			}
		}

		return implode($parts);
	}

	// doesn't need to be recursive, compileValue will handle that
	protected function extractInterpolation($list) {
		$items = $list[2];
		foreach ($items as $i => $item) {
			if ($item[0] == "interpolate") {
				$before = ["list", $list[1], array_slice($items, 0, $i)];
				$after = ["list", $list[1], array_slice($items, $i + 1)];
				return ["interpolated", $item, $before, $after];
			}
		}
		return $list;
	}

	// find the final set of selectors
	protected function multiplySelectors($env) {
		$envs = [];
		while (null !== $env) {
			if (!empty($env->selectors)) {
				$envs[] = $env;
			}
			$env = $env->parent;
		};

		$selectors = [];
		$parentSelectors = [[]];
		while ($env = array_pop($envs)) {
			$selectors = [];
			foreach ($env->selectors as $selector) {
				foreach ($parentSelectors as $parent) {
					$selectors[] = $this->joinSelectors($parent, $selector);
				}
			}
			$parentSelectors = $selectors;
		}

		return $selectors;
	}

	// looks for & to replace, or append parent before child
	protected function joinSelectors($parent, $child) {
		$setSelf = false;
		$out = [];
		foreach ($child as $part) {
			$newPart = [];
			foreach ($part as $p) {
				if ($p == self::$selfSelector) {
					$setSelf = true;
					foreach ($parent as $i => $parentPart) {
						if ($i > 0) {
							$out[] = $newPart;
							$newPart = [];
						}

						foreach ($parentPart as $pp) {
							$newPart[] = $pp;
						}
					}
				} else {
					$newPart[] = $p;
				}
			}

			$out[] = $newPart;
		}

		return $setSelf ? $out : array_merge($parent, $child);
	}

	protected function multiplyMedia($env, $childQueries = null) {
		if (is_null($env) ||
			!empty($env->block->type) && $env->block->type != "media")
		{
			return $childQueries;
		}

		// plain old block, skip
		if (empty($env->block->type)) {
			return $this->multiplyMedia($env->parent, $childQueries);
		}

		$parentQueries = $env->block->queryList;
		if ($childQueries == null) {
			$childQueries = $parentQueries;
		} else {
			$originalQueries = $childQueries;
			$childQueries = [];

			foreach ($parentQueries as $parentQuery){
				foreach ($originalQueries as $childQuery) {
					$childQueries []= array_merge($parentQuery, $childQuery);
				}
			}
		}

		return $this->multiplyMedia($env->parent, $childQueries);
	}

	// convert something to list
	protected function coerceList($item, $delim = ",") {
		if (!is_null($item) && $item[0] == "list") {
			return $item;
		}

		return ["list", $delim, is_null($item) ? []: [$item]];
	}

	protected function applyArguments($argDef, $argValues) {
		$hasVariable = false;
		$args = [];
		foreach ($argDef as $i => $arg) {
			list($name, $default, $isVariable) = $argDef[$i];
			$args[$name] = [$i, $name, $default, $isVariable];
			$hasVariable |= $isVariable;
		}

		$keywordArgs = [];
		$deferredKeywordArgs = [];
		$remaining = [];
		// assign the keyword args
		foreach ((array) $argValues as $arg) {
			if (!empty($arg[0])) {
				if (!isset($args[$arg[0][1]])) {
					if ($hasVariable) {
						$deferredKeywordArgs[$arg[0][1]] = $arg[1];
					} else {
						$this->throwError("Mixin or function doesn't have an argument named $%s.", $arg[0][1]);
					}
				} elseif ($args[$arg[0][1]][0] < count($remaining)) {
					$this->throwError("The argument $%s was passed both by position and by name.", $arg[0][1]);
				} else {
					$keywordArgs[$arg[0][1]] = $arg[1];
				}
			} elseif (count($keywordArgs)) {
				$this->throwError('Positional arguments must come before keyword arguments.');
			} elseif ($arg[2] == true) {
				$val = $this->reduce($arg[1], true);
				if ($val[0] == "list") {
					foreach ($val[2] as $name => $item) {
						if (!is_numeric($name)) {
							$keywordArgs[$name] = $item;
						} else {
							$remaining[] = $item;
						}
					}
				} else {
					$remaining[] = $val;
				}
			} else {
				$remaining[] = $arg[1];
			}
		}

		foreach ($args as $arg) {
			list($i, $name, $default, $isVariable) = $arg;
			if ($isVariable) {
				$val = ["list", ",", []];
				for ($count = count($remaining); $i < $count; $i++) {
					$val[2][] = $remaining[$i];
				}
				foreach ($deferredKeywordArgs as $itemName => $item) {
					$val[2][$itemName] = $item;
				}
			} elseif (isset($remaining[$i])) {
				$val = $remaining[$i];
			} elseif (isset($keywordArgs[$name])) {
				$val = $keywordArgs[$name];
			} elseif (!empty($default)) {
				$val = $default;
			} else {
				$this->throwError("Missing argument $name");
			}

			$this->set($name, $this->reduce($val, true), true);
		}
	}

	protected function pushEnv($block=null) {
		$env = new stdClass;
		$env->parent = $this->env;
		$env->store = [];
		$env->block = $block;
		$env->depth = isset($this->env->depth) ? $this->env->depth + 1 : 0;

		$this->env = $env;
		return $env;
	}

	protected function normalizeName($name) {
		return str_replace("-", "_", $name);
	}

	protected function getStoreEnv() {
		return isset($this->storeEnv) ? $this->storeEnv : $this->env;
	}

	protected function set($name, $value, $shadow=false) {
		$name = $this->normalizeName($name);

		if ($shadow) {
			$this->setRaw($name, $value);
		} else {
			$this->setExisting($name, $value);
		}
	}

	protected function setExisting($name, $value, $env = null) {
		if (is_null($env)) $env = $this->getStoreEnv();

		if (isset($env->store[$name]) || is_null($env->parent)) {
			$env->store[$name] = $value;
		} else {
			$this->setExisting($name, $value, $env->parent);
		}
	}

	protected function setRaw($name, $value) {
		$env = $this->getStoreEnv();
		$env->store[$name] = $value;
	}

	public function get($name, $defaultValue = null, $env = null) {
		$name = $this->normalizeName($name);

		if (is_null($env)) $env = $this->getStoreEnv();
		if (is_null($defaultValue)) $defaultValue = self::$defaultValue;

		if (isset($env->store[$name])) {
			return $env->store[$name];
		} elseif (isset($env->parent)) {
			return $this->get($name, $defaultValue, $env->parent);
		}

		return $defaultValue; // found nothing
	}

	protected function popEnv() {
		$env = $this->env;
		$this->env = $this->env->parent;
		return $env;
	}

	public function getParsedFiles() {
		return $this->parsedFiles;
	}

	public function addImportPath($path) {
		$this->importPaths[] = $path;
	}

	public function setImportPaths($path) {
		$this->importPaths = (array)$path;
	}

	public function setNumberPrecision($numberPrecision) {
		$this->numberPrecision = $numberPrecision;
	}

	public function setFormatter($formatterName) {
		$this->formatter = $formatterName;
	}

	public function registerFunction($name, $func) {
		$this->userFunctions[$this->normalizeName($name)] = $func;
	}

	public function unregisterFunction($name) {
		unset($this->userFunctions[$this->normalizeName($name)]);
	}

	//protected function importFile($path, $out) {
	function importFile($path, $out) { //addon by surikat
		// see if tree is cached
		$realPath = realpath($path);
		if (isset($this->importCache[$realPath])) {
			$tree = $this->importCache[$realPath];
		} else {
			$code = file_get_contents($path);
			//$parser = new Parser($path, false);
			$parser = new Parser($path, false,$this); //addon by surikat
			$tree = $parser->parse($code);
			$this->parsedFiles[] = $path;

			$this->importCache[$realPath] = $tree;
		}

		$pi = pathinfo($path);
		array_unshift($this->importPaths, $pi['dirname']);
		$this->compileChildren($tree->children, $out);
		array_shift($this->importPaths);
	}
	public $allowImportCSS;//addon by surikat
	public $allowImportRemote;//addon by surikat
	// results the file path for an import url if it exists
	public function findImport($url) {
		$urls = [];

		// for "normal" scss imports (ignore vanilla css and external requests)
		$reg = '';
		if(!$this->allowImportCSS)
			$reg .= '\.css';
		if(!$this->allowImportRemote)
			$reg .= ($reg?'|':'').'^http:\/\/$';
		$reg = '/'.$reg.'/';
		if (preg_match($reg, $url)) {
			// try both normal and the _partial filename
			$urls = [$url, preg_replace('/[^\/]+$/', '_\0', $url)];
		}
		foreach ($this->importPaths as $dir) {
			if (is_string($dir)) {
				// check urls for normal import paths
				foreach ($urls as $full) {
					$full = $dir .
						(!empty($dir) && substr($dir, -1) != '/' ? '/' : '') .
						$full;
					if ($this->fileExists($file = $full.'.scss') ||
						$this->fileExists($file = $full)
						|| $this->fileExists($file = $full.'.css')
					) //addon by surikat
					{
						return $file;
					}
				}
			} else {
				// check custom callback for import path
				$file = call_user_func($dir,$url,$this);
				if ($file !== null) {
					return $file;
				}
			}
		}

		return null;
	}

	protected function fileExists($name) {
		return is_file($name);
	}

	protected function callBuiltin($name, $args, &$returnValue) {
		// try a lib function
		$name = $this->normalizeName($name);
		$libName = "lib_".$name;
		$f = [$this, $libName];
		$prototype = isset(self::$$libName) ? self::$$libName : null;

		if (method_exists($this,$libName)) {
			$sorted = $this->sortArgs($prototype, $args);
			foreach ($sorted as &$val) {
				$val = $this->reduce($val, true);
			}
			$returnValue = call_user_func($f, $sorted, $this);
		} elseif (isset($this->userFunctions[$name])) {
			// see if we can find a user function
			$fn = $this->userFunctions[$name];

			foreach ($args as &$val) {
				$val = $this->reduce($val[1], true);
			}

			$returnValue = call_user_func($fn, $args, $this);
		}

		if (isset($returnValue)) {
			// coerce a php value into a scss one
			if (is_numeric($returnValue)) {
				$returnValue = ['number', $returnValue, ""];
			} elseif (is_bool($returnValue)) {
				$returnValue = $returnValue ? self::$true : self::$false;
			} elseif (!is_array($returnValue)) {
				$returnValue = ['keyword', $returnValue];
			}

			return true;
		}

		return false;
	}

	// sorts any keyword arguments
	// TODO: merge with apply arguments
	protected function sortArgs($prototype, $args) {
		$keyArgs = [];
		$posArgs = [];

		foreach ($args as $arg) {
			list($key, $value) = $arg;
			$key = $key[1];
			if (empty($key)) {
				$posArgs[] = $value;
			} else {
				$keyArgs[$key] = $value;
			}
		}

		if (is_null($prototype)) return $posArgs;

		$finalArgs = [];
		foreach ($prototype as $i => $names) {
			if (isset($posArgs[$i])) {
				$finalArgs[] = $posArgs[$i];
				continue;
			}

			$set = false;
			foreach ((array)$names as $name) {
				if (isset($keyArgs[$name])) {
					$finalArgs[] = $keyArgs[$name];
					$set = true;
					break;
				}
			}

			if (!$set) {
				$finalArgs[] = null;
			}
		}

		return $finalArgs;
	}

	protected function coerceForExpression($value) {
		if ($color = $this->coerceColor($value)) {
			return $color;
		}

		return $value;
	}

	protected function coerceColor($value) {
		switch ($value[0]) {
		case "color": return $value;
		case "keyword":
			$name = $value[1];
			if (isset(self::$cssColors[$name])) {
				@list($r, $g, $b, $a) = explode(',', self::$cssColors[$name]);
				return isset($a)
					? ['color', (int) $r, (int) $g, (int) $b, (int) $a]
					: ['color', (int) $r, (int) $g, (int) $b];
			}
			return null;
		}

		return null;
	}

	protected function coerceString($value) {
		switch ($value[0]) {
		case "string":
			return $value;
		case "keyword":
			return ["string", "", [$value[1]]];
		}
		return null;
	}

	public function assertList($value) {
		if ($value[0] != "list")
			$this->throwError("expecting list");
		return $value;
	}

	public function assertColor($value) {
		if ($color = $this->coerceColor($value)) return $color;
		$this->throwError("expecting color");
	}

	public function assertNumber($value) {
		if ($value[0] != "number")
			$this->throwError("expecting number");
		return $value[1];
	}

	protected function coercePercent($value) {
		if ($value[0] == "number") {
			if ($value[2] == "%") {
				return $value[1] / 100;
			}
			return $value[1];
		}
		return 0;
	}

	// make sure a color's components don't go out of bounds
	protected function fixColor($c) {
		foreach (range(1, 3) as $i) {
			if ($c[$i] < 0) $c[$i] = 0;
			if ($c[$i] > 255) $c[$i] = 255;
		}

		return $c;
	}

	public function toHSL($red, $green, $blue) {
		$r = $red / 255;
		$g = $green / 255;
		$b = $blue / 255;

		$min = min($r, $g, $b);
		$max = max($r, $g, $b);
		$d = $max - $min;
		$l = ($min + $max) / 2;

		if ($min == $max) {
			$s = $h = 0;
		} else {
			if ($l < 0.5)
				$s = $d / (2 * $l);
			else
				$s = $d / (2 - 2 * $l);

			if ($r == $max)
				$h = 60 * ($g - $b) / $d;
			elseif ($g == $max)
				$h = 60 * ($b - $r) / $d + 120;
			elseif ($b == $max)
				$h = 60 * ($r - $g) / $d + 240;
		}

		return ['hsl', fmod($h, 360), $s * 100, $l * 100];
	}

	public function hueToRGB($m1, $m2, $h) {
		if ($h < 0)
			$h += 1;
		elseif ($h > 1)
			$h -= 1;

		if ($h * 6 < 1)
			return $m1 + ($m2 - $m1) * $h * 6;

		if ($h * 2 < 1)
			return $m2;

		if ($h * 3 < 2)
			return $m1 + ($m2 - $m1) * (2/3 - $h) * 6;

		return $m1;
	}

	// H from 0 to 360, S and L from 0 to 100
	public function toRGB($hue, $saturation, $lightness) {
		if ($hue < 0) {
			$hue += 360;
		}

		$h = $hue / 360;
		$s = min(100, max(0, $saturation)) / 100;
		$l = min(100, max(0, $lightness)) / 100;

		$m2 = $l <= 0.5 ? $l * ($s + 1) : $l + $s - $l * $s;
		$m1 = $l * 2 - $m2;

		$r = $this->hueToRGB($m1, $m2, $h + 1/3) * 255;
		$g = $this->hueToRGB($m1, $m2, $h) * 255;
		$b = $this->hueToRGB($m1, $m2, $h - 1/3) * 255;

		$out = ['color', $r, $g, $b];
		return $out;
	}

	// Built in functions

	protected static $lib_if = ["condition", "if-true", "if-false"];
	protected function lib_if($args) {
		list($cond,$t, $f) = $args;
		if ($cond == self::$false) return $f;
		return $t;
	}

	protected static $lib_index = ["list", "value"];
	protected function lib_index($args) {
		list($list, $value) = $args;
		$list = $this->assertList($list);

		$values = [];
		foreach ($list[2] as $item) {
			$values[] = $this->normalizeValue($item);
		}
		$key = array_search($this->normalizeValue($value), $values);

		return false === $key ? false : $key + 1;
	}

	protected static $lib_rgb = ["red", "green", "blue"];
	protected function lib_rgb($args) {
		list($r,$g,$b) = $args;
		return ["color", $r[1], $g[1], $b[1]];
	}

	protected static $lib_rgba = [
		["red", "color"],
		"green", "blue", "alpha"];
	protected function lib_rgba($args) {
		if ($color = $this->coerceColor($args[0])) {
			$num = is_null($args[1]) ? $args[3] : $args[1];
			$alpha = $this->assertNumber($num);
			$color[4] = $alpha;
			return $color;
		}

		list($r,$g,$b, $a) = $args;
		return ["color", $r[1], $g[1], $b[1], $a[1]];
	}

	// helper function for adjust_color, change_color, and scale_color
	protected function alter_color($args, $fn) {
		$color = $this->assertColor($args[0]);

		foreach ([1,2,3,7] as $i) {
			if (!is_null($args[$i])) {
				$val = $this->assertNumber($args[$i]);
				$ii = $i == 7 ? 4 : $i; // alpha
				$color[$ii] =
					$this->$fn(isset($color[$ii]) ? $color[$ii] : 0, $val, $i);
			}
		}

		if (!is_null($args[4]) || !is_null($args[5]) || !is_null($args[6])) {
			$hsl = $this->toHSL($color[1], $color[2], $color[3]);
			foreach ([4,5,6] as $i) {
				if (!is_null($args[$i])) {
					$val = $this->assertNumber($args[$i]);
					$hsl[$i - 3] = $this->$fn($hsl[$i - 3], $val, $i);
				}
			}

			$rgb = $this->toRGB($hsl[1], $hsl[2], $hsl[3]);
			if (isset($color[4])) $rgb[4] = $color[4];
			$color = $rgb;
		}

		return $color;
	}

	protected static $lib_adjust_color = [
		"color", "red", "green", "blue",
		"hue", "saturation", "lightness", "alpha"
	];
	protected function adjust_color_helper($base, $alter, $i) {
		return $base += $alter;
	}
	protected function lib_adjust_color($args) {
		return $this->alter_color($args, "adjust_color_helper");
	}

	protected static $lib_change_color = [
		"color", "red", "green", "blue",
		"hue", "saturation", "lightness", "alpha"
	];
	protected function change_color_helper($base, $alter, $i) {
		return $alter;
	}
	protected function lib_change_color($args) {
		return $this->alter_color($args, "change_color_helper");
	}

	protected static $lib_scale_color = [
		"color", "red", "green", "blue",
		"hue", "saturation", "lightness", "alpha"
	];
	protected function scale_color_helper($base, $scale, $i) {
		// 1,2,3 - rgb
		// 4, 5, 6 - hsl
		// 7 - a
		switch ($i) {
		case 1:
		case 2:
		case 3:
			$max = 255; break;
		case 4:
			$max = 360; break;
		case 7:
			$max = 1; break;
		default:
			$max = 100;
		}

		$scale = $scale / 100;
		if ($scale < 0) {
			return $base * $scale + $base;
		} else {
			return ($max - $base) * $scale + $base;
		}
	}
	protected function lib_scale_color($args) {
		return $this->alter_color($args, "scale_color_helper");
	}

	protected static $lib_ie_hex_str = ["color"];
	protected function lib_ie_hex_str($args) {
		$color = $this->coerceColor($args[0]);
		$color[4] = isset($color[4]) ? round(255*$color[4]) : 255;

		return sprintf('#%02X%02X%02X%02X', $color[4], $color[1], $color[2], $color[3]);
	}

	protected static $lib_red = ["color"];
	protected function lib_red($args) {
		$color = $this->coerceColor($args[0]);
		return $color[1];
	}

	protected static $lib_green = ["color"];
	protected function lib_green($args) {
		$color = $this->coerceColor($args[0]);
		return $color[2];
	}

	protected static $lib_blue = ["color"];
	protected function lib_blue($args) {
		$color = $this->coerceColor($args[0]);
		return $color[3];
	}

	protected static $lib_alpha = ["color"];
	protected function lib_alpha($args) {
		if ($color = $this->coerceColor($args[0])) {
			return isset($color[4]) ? $color[4] : 1;
		}

		// this might be the IE function, so return value unchanged
		return null;
	}

	protected static $lib_opacity = ["color"];
	protected function lib_opacity($args) {
		$value = $args[0];
		if ($value[0] === 'number') return null;
		return $this->lib_alpha($args);
	}

	// mix two colors
	protected static $lib_mix = ["color-1", "color-2", "weight"];
	protected function lib_mix($args) {
		list($first, $second, $weight) = $args;
		$first = $this->assertColor($first);
		$second = $this->assertColor($second);

		if (is_null($weight)) {
			$weight = 0.5;
		} else {
			$weight = $this->coercePercent($weight);
		}

		$firstAlpha = isset($first[4]) ? $first[4] : 1;
		$secondAlpha = isset($second[4]) ? $second[4] : 1;

		$w = $weight * 2 - 1;
		$a = $firstAlpha - $secondAlpha;

		$w1 = (($w * $a == -1 ? $w : ($w + $a)/(1 + $w * $a)) + 1) / 2.0;
		$w2 = 1.0 - $w1;

		$new = ['color',
			$w1 * $first[1] + $w2 * $second[1],
			$w1 * $first[2] + $w2 * $second[2],
			$w1 * $first[3] + $w2 * $second[3],
		];

		if ($firstAlpha != 1.0 || $secondAlpha != 1.0) {
			$new[] = $firstAlpha * $weight + $secondAlpha * ($weight - 1);
		}

		return $this->fixColor($new);
	}

	protected static $lib_hsl = ["hue", "saturation", "lightness"];
	protected function lib_hsl($args) {
		list($h, $s, $l) = $args;
		return $this->toRGB($h[1], $s[1], $l[1]);
	}

	protected static $lib_hsla = ["hue", "saturation",
		"lightness", "alpha"];
	protected function lib_hsla($args) {
		list($h, $s, $l, $a) = $args;
		$color = $this->toRGB($h[1], $s[1], $l[1]);
		$color[4] = $a[1];
		return $color;
	}

	protected static $lib_hue = ["color"];
	protected function lib_hue($args) {
		$color = $this->assertColor($args[0]);
		$hsl = $this->toHSL($color[1], $color[2], $color[3]);
		return ["number", $hsl[1], "deg"];
	}

	protected static $lib_saturation = ["color"];
	protected function lib_saturation($args) {
		$color = $this->assertColor($args[0]);
		$hsl = $this->toHSL($color[1], $color[2], $color[3]);
		return ["number", $hsl[2], "%"];
	}

	protected static $lib_lightness = ["color"];
	protected function lib_lightness($args) {
		$color = $this->assertColor($args[0]);
		$hsl = $this->toHSL($color[1], $color[2], $color[3]);
		return ["number", $hsl[3], "%"];
	}

	protected function adjustHsl($color, $idx, $amount) {
		$hsl = $this->toHSL($color[1], $color[2], $color[3]);
		$hsl[$idx] += $amount;
		$out = $this->toRGB($hsl[1], $hsl[2], $hsl[3]);
		if (isset($color[4])) $out[4] = $color[4];
		return $out;
	}

	protected static $lib_adjust_hue = ["color", "degrees"];
	protected function lib_adjust_hue($args) {
		$color = $this->assertColor($args[0]);
		$degrees = $this->assertNumber($args[1]);
		return $this->adjustHsl($color, 1, $degrees);
	}

	protected static $lib_lighten = ["color", "amount"];
	protected function lib_lighten($args) {
		$color = $this->assertColor($args[0]);
		$amount = 100*$this->coercePercent($args[1]);
		return $this->adjustHsl($color, 3, $amount);
	}

	protected static $lib_darken = ["color", "amount"];
	protected function lib_darken($args) {
		$color = $this->assertColor($args[0]);
		$amount = 100*$this->coercePercent($args[1]);
		return $this->adjustHsl($color, 3, -$amount);
	}

	protected static $lib_saturate = ["color", "amount"];
	protected function lib_saturate($args) {
		$value = $args[0];
		if ($value[0] === 'number') return null;
		$color = $this->assertColor($value);
		$amount = 100*$this->coercePercent($args[1]);
		return $this->adjustHsl($color, 2, $amount);
	}

	protected static $lib_desaturate = ["color", "amount"];
	protected function lib_desaturate($args) {
		$color = $this->assertColor($args[0]);
		$amount = 100*$this->coercePercent($args[1]);
		return $this->adjustHsl($color, 2, -$amount);
	}

	protected static $lib_grayscale = ["color"];
	protected function lib_grayscale($args) {
		$value = $args[0];
		if ($value[0] === 'number') return null;
		return $this->adjustHsl($this->assertColor($value), 2, -100);
	}

	protected static $lib_complement = ["color"];
	protected function lib_complement($args) {
		return $this->adjustHsl($this->assertColor($args[0]), 1, 180);
	}

	protected static $lib_invert = ["color"];
	protected function lib_invert($args) {
		$value = $args[0];
		if ($value[0] === 'number') return null;
		$color = $this->assertColor($value);
		$color[1] = 255 - $color[1];
		$color[2] = 255 - $color[2];
		$color[3] = 255 - $color[3];
		return $color;
	}

	// increases opacity by amount
	protected static $lib_opacify = ["color", "amount"];
	protected function lib_opacify($args) {
		$color = $this->assertColor($args[0]);
		$amount = $this->coercePercent($args[1]);

		$color[4] = (isset($color[4]) ? $color[4] : 1) + $amount;
		$color[4] = min(1, max(0, $color[4]));
		return $color;
	}

	protected static $lib_fade_in = ["color", "amount"];
	protected function lib_fade_in($args) {
		return $this->lib_opacify($args);
	}

	// decreases opacity by amount
	protected static $lib_transparentize = ["color", "amount"];
	protected function lib_transparentize($args) {
		$color = $this->assertColor($args[0]);
		$amount = $this->coercePercent($args[1]);

		$color[4] = (isset($color[4]) ? $color[4] : 1) - $amount;
		$color[4] = min(1, max(0, $color[4]));
		return $color;
	}

	protected static $lib_fade_out = ["color", "amount"];
	protected function lib_fade_out($args) {
		return $this->lib_transparentize($args);
	}

	protected static $lib_unquote = ["string"];
	protected function lib_unquote($args) {
		$str = $args[0];
		if ($str[0] == "string") $str[1] = "";
		return $str;
	}

	protected static $lib_quote = ["string"];
	protected function lib_quote($args) {
		$value = $args[0];
		if ($value[0] == "string" && !empty($value[1]))
			return $value;
		return ["string", '"', [$value]];
	}

	protected static $lib_percentage = ["value"];
	protected function lib_percentage($args) {
		return ["number",
			$this->coercePercent($args[0]) * 100,
			"%"];
	}

	protected static $lib_round = ["value"];
	protected function lib_round($args) {
		$num = $args[0];
		$num[1] = round($num[1]);
		return $num;
	}

	protected static $lib_floor = ["value"];
	protected function lib_floor($args) {
		$num = $args[0];
		$num[1] = floor($num[1]);
		return $num;
	}

	protected static $lib_ceil = ["value"];
	protected function lib_ceil($args) {
		$num = $args[0];
		$num[1] = ceil($num[1]);
		return $num;
	}

	protected static $lib_abs = ["value"];
	protected function lib_abs($args) {
		$num = $args[0];
		$num[1] = abs($num[1]);
		return $num;
	}

	protected function lib_min($args) {
		$numbers = $this->getNormalizedNumbers($args);
		$min = null;
		foreach ($numbers as $key => $number) {
			if (null === $min || $number[1] <= $min[1]) {
				$min = [$key, $number[1]];
			}
		}

		return $args[$min[0]];
	}

	protected function lib_max($args) {
		$numbers = $this->getNormalizedNumbers($args);
		$max = null;
		foreach ($numbers as $key => $number) {
			if (null === $max || $number[1] >= $max[1]) {
				$max = [$key, $number[1]];
			}
		}

		return $args[$max[0]];
	}

	protected function getNormalizedNumbers($args) {
		$unit = null;
		$originalUnit = null;
		$numbers = [];
		foreach ($args as $key => $item) {
			if ('number' != $item[0]) {
				$this->throwError("%s is not a number", $item[0]);
			}
			$number = $this->normalizeNumber($item);

			if (null === $unit) {
				$unit = $number[2];
				$originalUnit = $item[2];
			} elseif ($unit !== $number[2]) {
				$this->throwError('Incompatible units: "%s" and "%s".', $originalUnit, $item[2]);
			}

			$numbers[$key] = $number;
		}

		return $numbers;
	}

	protected static $lib_length = ["list"];
	protected function lib_length($args) {
		$list = $this->coerceList($args[0]);
		return count($list[2]);
	}

	protected static $lib_nth = ["list", "n"];
	protected function lib_nth($args) {
		$list = $this->coerceList($args[0]);
		$n = $this->assertNumber($args[1]) - 1;
		return isset($list[2][$n]) ? $list[2][$n] : self::$defaultValue;
	}

	protected function listSeparatorForJoin($list1, $sep) {
		if (is_null($sep)) return $list1[1];
		switch ($this->compileValue($sep)) {
		case "comma":
			return ",";
		case "space":
			return "";
		default:
			return $list1[1];
		}
	}

	protected static $lib_join = ["list1", "list2", "separator"];
	protected function lib_join($args) {
		list($list1, $list2, $sep) = $args;
		$list1 = $this->coerceList($list1, " ");
		$list2 = $this->coerceList($list2, " ");
		$sep = $this->listSeparatorForJoin($list1, $sep);
		return ["list", $sep, array_merge($list1[2], $list2[2])];
	}

	protected static $lib_append = ["list", "val", "separator"];
	protected function lib_append($args) {
		list($list1, $value, $sep) = $args;
		$list1 = $this->coerceList($list1, " ");
		$sep = $this->listSeparatorForJoin($list1, $sep);
		return ["list", $sep, array_merge($list1[2], [$value])];
	}

	protected function lib_zip($args) {
		foreach ($args as $arg) {
			$this->assertList($arg);
		}

		$lists = [];
		$firstList = array_shift($args);
		foreach ($firstList[2] as $key => $item) {
			$list = ["list", "", [$item]];
			foreach ($args as $arg) {
				if (isset($arg[2][$key])) {
					$list[2][] = $arg[2][$key];
				} else {
					break 2;
				}
			}
			$lists[] = $list;
		}

		return ["list", ",", $lists];
	}

	protected static $lib_type_of = ["value"];
	protected function lib_type_of($args) {
		$value = $args[0];
		switch ($value[0]) {
		case "keyword":
			if ($value == self::$true || $value == self::$false) {
				return "bool";
			}

			if ($this->coerceColor($value)) {
				return "color";
			}

			return "string";
		default:
			return $value[0];
		}
	}

	protected static $lib_unit = ["number"];
	protected function lib_unit($args) {
		$num = $args[0];
		if ($num[0] == "number") {
			return ["string", '"', [$num[2]]];
		}
		return "";
	}

	protected static $lib_unitless = ["number"];
	protected function lib_unitless($args) {
		$value = $args[0];
		return $value[0] == "number" && empty($value[2]);
	}

	protected static $lib_comparable = ["number-1", "number-2"];
	protected function lib_comparable($args) {
		list($number1, $number2) = $args;
		if (!isset($number1[0]) || $number1[0] != "number" || !isset($number2[0]) || $number2[0] != "number") {
			$this->throwError('Invalid argument(s) for "comparable"');
		}

		$number1 = $this->normalizeNumber($number1);
		$number2 = $this->normalizeNumber($number2);

		return $number1[2] == $number2[2] || $number1[2] == "" || $number2[2] == "";
	}

	/**
	 * Workaround IE7's content counter bug.
	 *
	 * @param array $args
	 */
	protected function lib_counter($args) {
		$list = array_map([$this, 'compileValue'], $args);
		return ['string', '', ['counter(' . implode(',', $list) . ')']];
	}

	public function throwError($msg = null) {
		if (func_num_args() > 1) {
			$msg = call_user_func_array("sprintf", func_get_args());
		}
		if ($this->sourcePos >= 0 && isset($this->sourceParser)) {
			$this->sourceParser->throwParseError($msg, $this->sourcePos);
		}
		throw new Exception($msg);
	}

	/**
	 * CSS Colors
	 *
	 * @see http://www.w3.org/TR/css3-color
	 */
	static protected $cssColors = [
		'aliceblue' => '240,248,255',
		'antiquewhite' => '250,235,215',
		'aqua' => '0,255,255',
		'aquamarine' => '127,255,212',
		'azure' => '240,255,255',
		'beige' => '245,245,220',
		'bisque' => '255,228,196',
		'black' => '0,0,0',
		'blanchedalmond' => '255,235,205',
		'blue' => '0,0,255',
		'blueviolet' => '138,43,226',
		'brown' => '165,42,42',
		'burlywood' => '222,184,135',
		'cadetblue' => '95,158,160',
		'chartreuse' => '127,255,0',
		'chocolate' => '210,105,30',
		'coral' => '255,127,80',
		'cornflowerblue' => '100,149,237',
		'cornsilk' => '255,248,220',
		'crimson' => '220,20,60',
		'cyan' => '0,255,255',
		'darkblue' => '0,0,139',
		'darkcyan' => '0,139,139',
		'darkgoldenrod' => '184,134,11',
		'darkgray' => '169,169,169',
		'darkgreen' => '0,100,0',
		'darkgrey' => '169,169,169',
		'darkkhaki' => '189,183,107',
		'darkmagenta' => '139,0,139',
		'darkolivegreen' => '85,107,47',
		'darkorange' => '255,140,0',
		'darkorchid' => '153,50,204',
		'darkred' => '139,0,0',
		'darksalmon' => '233,150,122',
		'darkseagreen' => '143,188,143',
		'darkslateblue' => '72,61,139',
		'darkslategray' => '47,79,79',
		'darkslategrey' => '47,79,79',
		'darkturquoise' => '0,206,209',
		'darkviolet' => '148,0,211',
		'deeppink' => '255,20,147',
		'deepskyblue' => '0,191,255',
		'dimgray' => '105,105,105',
		'dimgrey' => '105,105,105',
		'dodgerblue' => '30,144,255',
		'firebrick' => '178,34,34',
		'floralwhite' => '255,250,240',
		'forestgreen' => '34,139,34',
		'fuchsia' => '255,0,255',
		'gainsboro' => '220,220,220',
		'ghostwhite' => '248,248,255',
		'gold' => '255,215,0',
		'goldenrod' => '218,165,32',
		'gray' => '128,128,128',
		'green' => '0,128,0',
		'greenyellow' => '173,255,47',
		'grey' => '128,128,128',
		'honeydew' => '240,255,240',
		'hotpink' => '255,105,180',
		'indianred' => '205,92,92',
		'indigo' => '75,0,130',
		'ivory' => '255,255,240',
		'khaki' => '240,230,140',
		'lavender' => '230,230,250',
		'lavenderblush' => '255,240,245',
		'lawngreen' => '124,252,0',
		'lemonchiffon' => '255,250,205',
		'lightblue' => '173,216,230',
		'lightcoral' => '240,128,128',
		'lightcyan' => '224,255,255',
		'lightgoldenrodyellow' => '250,250,210',
		'lightgray' => '211,211,211',
		'lightgreen' => '144,238,144',
		'lightgrey' => '211,211,211',
		'lightpink' => '255,182,193',
		'lightsalmon' => '255,160,122',
		'lightseagreen' => '32,178,170',
		'lightskyblue' => '135,206,250',
		'lightslategray' => '119,136,153',
		'lightslategrey' => '119,136,153',
		'lightsteelblue' => '176,196,222',
		'lightyellow' => '255,255,224',
		'lime' => '0,255,0',
		'limegreen' => '50,205,50',
		'linen' => '250,240,230',
		'magenta' => '255,0,255',
		'maroon' => '128,0,0',
		'mediumaquamarine' => '102,205,170',
		'mediumblue' => '0,0,205',
		'mediumorchid' => '186,85,211',
		'mediumpurple' => '147,112,219',
		'mediumseagreen' => '60,179,113',
		'mediumslateblue' => '123,104,238',
		'mediumspringgreen' => '0,250,154',
		'mediumturquoise' => '72,209,204',
		'mediumvioletred' => '199,21,133',
		'midnightblue' => '25,25,112',
		'mintcream' => '245,255,250',
		'mistyrose' => '255,228,225',
		'moccasin' => '255,228,181',
		'navajowhite' => '255,222,173',
		'navy' => '0,0,128',
		'oldlace' => '253,245,230',
		'olive' => '128,128,0',
		'olivedrab' => '107,142,35',
		'orange' => '255,165,0',
		'orangered' => '255,69,0',
		'orchid' => '218,112,214',
		'palegoldenrod' => '238,232,170',
		'palegreen' => '152,251,152',
		'paleturquoise' => '175,238,238',
		'palevioletred' => '219,112,147',
		'papayawhip' => '255,239,213',
		'peachpuff' => '255,218,185',
		'peru' => '205,133,63',
		'pink' => '255,192,203',
		'plum' => '221,160,221',
		'powderblue' => '176,224,230',
		'purple' => '128,0,128',
		'red' => '255,0,0',
		'rosybrown' => '188,143,143',
		'royalblue' => '65,105,225',
		'saddlebrown' => '139,69,19',
		'salmon' => '250,128,114',
		'sandybrown' => '244,164,96',
		'seagreen' => '46,139,87',
		'seashell' => '255,245,238',
		'sienna' => '160,82,45',
		'silver' => '192,192,192',
		'skyblue' => '135,206,235',
		'slateblue' => '106,90,205',
		'slategray' => '112,128,144',
		'slategrey' => '112,128,144',
		'snow' => '255,250,250',
		'springgreen' => '0,255,127',
		'steelblue' => '70,130,180',
		'tan' => '210,180,140',
		'teal' => '0,128,128',
		'thistle' => '216,191,216',
		'tomato' => '255,99,71',
		'transparent' => '0,0,0,0',
		'turquoise' => '64,224,208',
		'violet' => '238,130,238',
		'wheat' => '245,222,179',
		'white' => '255,255,255',
		'whitesmoke' => '245,245,245',
		'yellow' => '255,255,0',
		'yellowgreen' => '154,205,50'
	];
}