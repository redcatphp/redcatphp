<?php namespace Surikat\Component\SyntaxedCSS;
/**
 * SCSS parser
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 */
class Parser {
	static protected $precedence = [
		"or" => 0,
		"and" => 1,

		'==' => 2,
		'!=' => 2,
		'<=' => 2,
		'>=' => 2,
		'=' => 2,
		'<' => 3,
		'>' => 2,

		'+' => 3,
		'-' => 3,
		'*' => 4,
		'/' => 4,
		'%' => 4,
	];

	static protected $operators = ["+", "-", "*", "/", "%",
		"==", "!=", "<=", ">=", "<", ">", "and", "or"];

	static protected $operatorStr;
	static protected $whitePattern;
	static protected $commentMulti;

	static protected $commentSingle = "//";
	static protected $commentMultiLeft = "/*";
	static protected $commentMultiRight = "*/";
	protected $scssFile; // addon by surikat
	protected $buffer; //bugfix by surikat
	//public function __construct($sourceName = null, $rootParser = true) {
	public function __construct($sourceName = null, $rootParser = true,$scssFile=null) { //addon by surikat
		$this->sourceName = $sourceName;
		$this->rootParser = $rootParser;
		$this->scssFile = $scssFile;
		if (empty(self::$operatorStr)) {
			self::$operatorStr = $this->makeOperatorStr(self::$operators);

			$commentSingle = $this->preg_quote(self::$commentSingle);
			$commentMultiLeft = $this->preg_quote(self::$commentMultiLeft);
			$commentMultiRight = $this->preg_quote(self::$commentMultiRight);
			self::$commentMulti = $commentMultiLeft.'.*?'.$commentMultiRight;
			self::$whitePattern = '/'.$commentSingle.'[^\n]*\s*|('.self::$commentMulti.')\s*|\s+/Ais';
		}
	}

	static protected function makeOperatorStr($operators) {
		return '('.implode('|', array_map([__NAMESPACE__.'\\Parser','preg_quote'],
			$operators)).')';
	}

	
	
	public function parse($buffer) {
		$this->count = 0;
		$this->env = null;
		$this->inParens = false;
		$this->pushBlock(null); // root block
		$this->eatWhiteDefault = true;
		$this->insertComments = true;

		$buffer = $this->scssFile->phpScssSupport($buffer);//addon by surikat
		
		$this->buffer = $buffer;

		$this->whitespace();
		while (false !== $this->parseChunk());

		if ($this->count != strlen($this->buffer))
			$this->throwParseError();

		if (!empty($this->env->parent)) {
			$this->throwParseError("unclosed block");
		}

		$this->env->isRoot = true;
		return $this->env;
	}

	/**
	 * Parse a single chunk off the head of the buffer and append it to the
	 * current parse environment.
	 *
	 * Returns false when the buffer is empty, or when there is an error.
	 *
	 * This function is called repeatedly until the entire document is
	 * parsed.
	 *
	 * This parser is most similar to a recursive descent parser. Single
	 * functions represent discrete grammatical rules for the language, and
	 * they are able to capture the text that represents those rules.
	 *
	 * Consider the function scssc::keyword(). (All parse functions are
	 * structured the same.)
	 *
	 * The function takes a single reference argument. When calling the
	 * function it will attempt to match a keyword on the head of the buffer.
	 * If it is successful, it will place the keyword in the referenced
	 * argument, advance the position in the buffer, and return true. If it
	 * fails then it won't advance the buffer and it will return false.
	 *
	 * All of these parse functions are powered by scssc::match(), which behaves
	 * the same way, but takes a literal regular expression. Sometimes it is
	 * more convenient to use match instead of creating a new function.
	 *
	 * Because of the format of the functions, to parse an entire string of
	 * grammatical rules, you can chain them together using &&.
	 *
	 * But, if some of the rules in the chain succeed before one fails, then
	 * the buffer position will be left at an invalid state. In order to
	 * avoid this, scssc::seek() is used to remember and set buffer positions.
	 *
	 * Before parsing a chain, use $s = $this->seek() to remember the current
	 * position into $s. Then if a chain fails, use $this->seek($s) to
	 * go back where we started.
	 *
	 * @return boolean
	 */
	protected function parseChunk() {
		$s = $this->seek();

		// the directives
		if (isset($this->buffer[$this->count]) && $this->buffer[$this->count] == "@") {
			if ($this->literal("@media") && $this->mediaQueryList($mediaQueryList) && $this->literal("{")) {
				$media = $this->pushSpecialBlock("media");
				$media->queryList = $mediaQueryList[2];
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@mixin") &&
				$this->keyword($mixinName) &&
				($this->argumentDef($args) || true) &&
				$this->literal("{"))
			{
				$mixin = $this->pushSpecialBlock("mixin");
				$mixin->name = $mixinName;
				$mixin->args = $args;
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@include") &&
				$this->keyword($mixinName) &&
				($this->literal("(") &&
					($this->argValues($argValues) || true) &&
					$this->literal(")") || true) &&
				($this->end() ||
					$this->literal("{") && $hasBlock = true))
			{
				$child = ["include",
					$mixinName, isset($argValues) ? $argValues : null, null];

				if (!empty($hasBlock)) {
					$include = $this->pushSpecialBlock("include");
					$include->child = $child;
				} else {
					$this->append($child, $s);
				}

				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@import") &&
				$this->valueList($importPath) &&
				$this->end())
			{
				$this->append(["import", $importPath], $s);
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@extend") &&
				$this->selectors($selector) &&
				$this->end())
			{
				$this->append(["extend", $selector], $s);
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@function") &&
				$this->keyword($fnName) &&
				$this->argumentDef($args) &&
				$this->literal("{"))
			{
				$func = $this->pushSpecialBlock("function");
				$func->name = $fnName;
				$func->args = $args;
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@return") && $this->valueList($retVal) && $this->end()) {
				$this->append(["return", $retVal], $s);
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@each") &&
				$this->variable($varName) &&
				$this->literal("in") &&
				$this->valueList($list) &&
				$this->literal("{"))
			{
				$each = $this->pushSpecialBlock("each");
				$each->var = $varName[1];
				$each->list = $list;
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@while") &&
				$this->expression($cond) &&
				$this->literal("{"))
			{
				$while = $this->pushSpecialBlock("while");
				$while->cond = $cond;
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@for") &&
				$this->variable($varName) &&
				$this->literal("from") &&
				$this->expression($start) &&
				($this->literal("through") ||
					($forUntil = true && $this->literal("to"))) &&
				$this->expression($end) &&
				$this->literal("{"))
			{
				$for = $this->pushSpecialBlock("for");
				$for->var = $varName[1];
				$for->start = $start;
				$for->end = $end;
				$for->until = isset($forUntil);
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@if") && $this->valueList($cond) && $this->literal("{")) {
				$if = $this->pushSpecialBlock("if");
				$if->cond = $cond;
				$if->cases = [];
				return true;
			} else {
				$this->seek($s);
			}

			if (($this->literal("@debug") || $this->literal("@warn")) &&
				$this->valueList($value) &&
				$this->end()) {
				$this->append(["debug", $value, $s], $s);
				return true;
			} else {
				$this->seek($s);
			}

			if ($this->literal("@content") && $this->end()) {
				$this->append(["mixin_content"], $s);
				return true;
			} else {
				$this->seek($s);
			}

			$last = $this->last();
			if (!is_null($last) && $last[0] == "if") {
				list(, $if) = $last;
				if ($this->literal("@else")) {
					if ($this->literal("{")) {
						$else = $this->pushSpecialBlock("else");
					} elseif ($this->literal("if") && $this->valueList($cond) && $this->literal("{")) {
						$else = $this->pushSpecialBlock("elseif");
						$else->cond = $cond;
					}

					if (isset($else)) {
						$else->dontAppend = true;
						$if->cases[] = $else;
						return true;
					}
				}

				$this->seek($s);
			}

			if ($this->literal("@charset") &&
				$this->valueList($charset) && $this->end())
			{
				$this->append(["charset", $charset], $s);
				return true;
			} else {
				$this->seek($s);
			}

			// doesn't match built in directive, do generic one
			if ($this->literal("@", false) && $this->keyword($dirName) &&
				($this->openString("{", $dirValue) || true) &&
				$this->literal("{"))
			{
				$directive = $this->pushSpecialBlock("directive");
				$directive->name = $dirName;
				if (isset($dirValue)) $directive->value = $dirValue;
				return true;
			}

			$this->seek($s);
			return false;
		}

		// property shortcut
		// captures most properties before having to parse a selector
		if ($this->keyword($name, false) &&
			$this->literal(": ") &&
			$this->valueList($value) &&
			$this->end())
		{
			$name = ["string", "", [$name]];
			$this->append(["assign", $name, $value], $s);
			return true;
		} else {
			$this->seek($s);
		}

		// variable assigns
		if ($this->variable($name) &&
			$this->literal(":") &&
			$this->valueList($value) && $this->end())
		{
			// check for !default
			$defaultVar = $value[0] == "list" && $this->stripDefault($value);
			$this->append(["assign", $name, $value, $defaultVar], $s);
			return true;
		} else {
			$this->seek($s);
		}

		// misc
		if ($this->literal("-->")) {
			return true;
		}

		// opening css block
		$oldComments = $this->insertComments;
		$this->insertComments = false;
		if ($this->selectors($selectors) && $this->literal("{")) {
			$this->pushBlock($selectors);
			$this->insertComments = $oldComments;
			return true;
		} else {
			$this->seek($s);
		}
		$this->insertComments = $oldComments;

		// property assign, or nested assign
		if ($this->propertyName($name) && $this->literal(":")) {
			$foundSomething = false;
			if ($this->valueList($value)) {
				$this->append(["assign", $name, $value], $s);
				$foundSomething = true;
			}

			if ($this->literal("{")) {
				$propBlock = $this->pushSpecialBlock("nestedprop");
				$propBlock->prefix = $name;
				$foundSomething = true;
			} elseif ($foundSomething) {
				$foundSomething = $this->end();
			}

			if ($foundSomething) {
				return true;
			}

			$this->seek($s);
		} else {
			$this->seek($s);
		}

		// closing a block
		if ($this->literal("}")) {
			$block = $this->popBlock();
			if (isset($block->type) && $block->type == "include") {
				$include = $block->child;
				unset($block->child);
				$include[3] = $block;
				$this->append($include, $s);
			} elseif (empty($block->dontAppend)) {
				$type = isset($block->type) ? $block->type : "block";
				$this->append([$type, $block], $s);
			}
			return true;
		}

		// extra stuff
		if ($this->literal(";") ||
			$this->literal("<!--"))
		{
			return true;
		}

		return false;
	}

	protected function stripDefault(&$value) {
		$def = end($value[2]);
		if ($def[0] == "keyword" && $def[1] == "!default") {
			array_pop($value[2]);
			$value = $this->flattenList($value);
			return true;
		}

		if ($def[0] == "list") {
			return $this->stripDefault($value[2][count($value[2]) - 1]);
		}

		return false;
	}

	protected function literal($what, $eatWhitespace = null) {
		if (is_null($eatWhitespace)) $eatWhitespace = $this->eatWhiteDefault;

		// shortcut on single letter
		if (!isset($what[1]) && isset($this->buffer[$this->count])) {
			if ($this->buffer[$this->count] == $what) {
				if (!$eatWhitespace) {
					$this->count++;
					return true;
				}
				// goes below...
			} else {
				return false;
			}
		}

		return $this->match($this->preg_quote($what), $m, $eatWhitespace);
	}

	// tree builders

	protected function pushBlock($selectors) {
		$b = new stdClass;
		$b->parent = $this->env; // not sure if we need this yet

		$b->selectors = $selectors;
		$b->children = [];

		$this->env = $b;
		return $b;
	}

	protected function pushSpecialBlock($type) {
		$block = $this->pushBlock(null);
		$block->type = $type;
		return $block;
	}

	protected function popBlock() {
		if (empty($this->env->parent)) {
			$this->throwParseError("unexpected }");
		}

		$old = $this->env;
		$this->env = $this->env->parent;
		unset($old->parent);
		return $old;
	}

	protected function append($statement, $pos=null) {
		if ($pos !== null) {
			$statement[-1] = $pos;
			if (!$this->rootParser) $statement[-2] = $this;
		}
		$this->env->children[] = $statement;
	}

	// last child that was appended
	protected function last() {
		$i = count($this->env->children) - 1;
		if (isset($this->env->children[$i]))
			return $this->env->children[$i];
	}

	// high level parsers (they return parts of ast)

	protected function mediaQueryList(&$out) {
		return $this->genericList($out, "mediaQuery", ",", false);
	}

	protected function mediaQuery(&$out) {
		$s = $this->seek();

		$expressions = null;
		$parts = [];

		if (($this->literal("only") && ($only = true) || $this->literal("not") && ($not = true) || true) && $this->mixedKeyword($mediaType)) {
			$prop = ["mediaType"];
			if (isset($only)) $prop[] = ["keyword", "only"];
			if (isset($not)) $prop[] = ["keyword", "not"];
			$media = ["list", "", []];
			foreach ((array)$mediaType as $type) {
				if (is_array($type)) {
					$media[2][] = $type;
				} else {
					$media[2][] = ["keyword", $type];
				}
			}
			$prop[] = $media;
			$parts[] = $prop;
		}

		if (empty($parts) || $this->literal("and")) {
			$this->genericList($expressions, "mediaExpression", "and", false);
			if (is_array($expressions)) $parts = array_merge($parts, $expressions[2]);
		}

		$out = $parts;
		return true;
	}

	protected function mediaExpression(&$out) {
		$s = $this->seek();
		$value = null;
		if ($this->literal("(") &&
			$this->expression($feature) &&
			($this->literal(":") && $this->expression($value) || true) &&
			$this->literal(")"))
		{
			$out = ["mediaExp", $feature];
			if ($value) $out[] = $value;
			return true;
		}

		$this->seek($s);
		return false;
	}

	protected function argValues(&$out) {
		if ($this->genericList($list, "argValue", ",", false)) {
			$out = $list[2];
			return true;
		}
		return false;
	}

	protected function argValue(&$out) {
		$s = $this->seek();

		$keyword = null;
		if (!$this->variable($keyword) || !$this->literal(":")) {
			$this->seek($s);
			$keyword = null;
		}

		if ($this->genericList($value, "expression")) {
			$out = [$keyword, $value, false];
			$s = $this->seek();
			if ($this->literal("...")) {
				$out[2] = true;
			} else {
				$this->seek($s);
			}
			return true;
		}

		return false;
	}


	protected function valueList(&$out) {
		return $this->genericList($out, "spaceList", ",");
	}

	protected function spaceList(&$out) {
		return $this->genericList($out, "expression");
	}

	protected function genericList(&$out, $parseItem, $delim="", $flatten=true) {
		$s = $this->seek();
		$items = [];
		while ($this->$parseItem($value)) {
			$items[] = $value;
			if ($delim) {
				if (!$this->literal($delim)) break;
			}
		}

		if (count($items) == 0) {
			$this->seek($s);
			return false;
		}

		if ($flatten && count($items) == 1) {
			$out = $items[0];
		} else {
			$out = ["list", $delim, $items];
		}

		return true;
	}

	protected function expression(&$out) {
		$s = $this->seek();

		if ($this->literal("(")) {
			if ($this->literal(")")) {
				$out = ["list", "", []];
				return true;
			}

			if ($this->valueList($out) && $this->literal(')') && $out[0] == "list") {
				return true;
			}

			$this->seek($s);
		}

		if ($this->value($lhs)) {
			$out = $this->expHelper($lhs, 0);
			return true;
		}

		return false;
	}

	protected function expHelper($lhs, $minP) {
		$opstr = self::$operatorStr;

		$ss = $this->seek();
		$whiteBefore = isset($this->buffer[$this->count - 1]) &&
			ctype_space($this->buffer[$this->count - 1]);
		while ($this->match($opstr, $m) && self::$precedence[$m[1]] >= $minP) {
			$whiteAfter = isset($this->buffer[$this->count - 1]) &&
				ctype_space($this->buffer[$this->count - 1]);

			$op = $m[1];

			// don't turn negative numbers into expressions
			if ($op == "-" && $whiteBefore) {
				if (!$whiteAfter) break;
			}

			if (!$this->value($rhs)) break;

			// peek and see if rhs belongs to next operator
			if ($this->peek($opstr, $next) && self::$precedence[$next[1]] > self::$precedence[$op]) {
				$rhs = $this->expHelper($rhs, self::$precedence[$next[1]]);
			}

			$lhs = ["exp", $op, $lhs, $rhs, $this->inParens, $whiteBefore, $whiteAfter];
			$ss = $this->seek();
			$whiteBefore = isset($this->buffer[$this->count - 1]) &&
				ctype_space($this->buffer[$this->count - 1]);
		}

		$this->seek($ss);
		return $lhs;
	}

	protected function value(&$out) {
		$s = $this->seek();

		if ($this->literal("not", false) && $this->whitespace() && $this->value($inner)) {
			$out = ["unary", "not", $inner, $this->inParens];
			return true;
		} else {
			$this->seek($s);
		}

		if ($this->literal("+") && $this->value($inner)) {
			$out = ["unary", "+", $inner, $this->inParens];
			return true;
		} else {
			$this->seek($s);
		}

		// negation
		if ($this->literal("-", false) &&
			($this->variable($inner) ||
			$this->unit($inner) ||
			$this->parenValue($inner)))
		{
			$out = ["unary", "-", $inner, $this->inParens];
			return true;
		} else {
			$this->seek($s);
		}

		if ($this->parenValue($out)) return true;
		if ($this->interpolation($out)) return true;
		if ($this->variable($out)) return true;
		if ($this->color($out)) return true;
		if ($this->unit($out)) return true;
		if ($this->string($out)) return true;
		if ($this->func($out)) return true;
		if ($this->progid($out)) return true;

		if ($this->keyword($keyword)) {
			if ($keyword == "null") {
				$out = ["null"];
			} else {
				$out = ["keyword", $keyword];
			}
			return true;
		}

		return false;
	}

	// value wrappen in parentheses
	protected function parenValue(&$out) {
		$s = $this->seek();

		$inParens = $this->inParens;
		if ($this->literal("(") &&
			($this->inParens = true) && $this->expression($exp) &&
			$this->literal(")"))
		{
			$out = $exp;
			$this->inParens = $inParens;
			return true;
		} else {
			$this->inParens = $inParens;
			$this->seek($s);
		}

		return false;
	}

	protected function progid(&$out) {
		$s = $this->seek();
		if ($this->literal("progid:", false) &&
			$this->openString("(", $fn) &&
			$this->literal("("))
		{
			$this->openString(")", $args, "(");
			if ($this->literal(")")) {
				$out = ["string", "", [
					"progid:", $fn, "(", $args, ")"
				]];
				return true;
			}
		}

		$this->seek($s);
		return false;
	}

	protected function func(&$func) {
		$s = $this->seek();

		if ($this->keyword($name, false) &&
			$this->literal("("))
		{
			if ($name == "alpha" && $this->argumentList($args)) {
				$func = ["function", $name, ["string", "", $args]];
				return true;
			}

			if ($name != "expression" && !preg_match("/^(-[a-z]+-)?calc$/", $name)) {
				$ss = $this->seek();
				if ($this->argValues($args) && $this->literal(")")) {
					$func = ["fncall", $name, $args];
					return true;
				}
				$this->seek($ss);
			}

			if (($this->openString(")", $str, "(") || true ) &&
				$this->literal(")"))
			{
				$args = [];
				if (!empty($str)) {
					$args[] = [null, ["string", "", [$str]]];
				}

				$func = ["fncall", $name, $args];
				return true;
			}
		}

		$this->seek($s);
		return false;
	}

	protected function argumentList(&$out) {
		$s = $this->seek();
		$this->literal("(");

		$args = [];
		while ($this->keyword($var)) {
			$ss = $this->seek();

			if ($this->literal("=") && $this->expression($exp)) {
				$args[] = ["string", "", [$var."="]];
				$arg = $exp;
			} else {
				break;
			}

			$args[] = $arg;

			if (!$this->literal(",")) break;

			$args[] = ["string", "", [", "]];
		}

		if (!$this->literal(")") || !count($args)) {
			$this->seek($s);
			return false;
		}

		$out = $args;
		return true;
	}

	protected function argumentDef(&$out) {
		$s = $this->seek();
		$this->literal("(");

		$args = [];
		while ($this->variable($var)) {
			$arg = [$var[1], null, false];

			$ss = $this->seek();
			if ($this->literal(":") && $this->genericList($defaultVal, "expression")) {
				$arg[1] = $defaultVal;
			} else {
				$this->seek($ss);
			}

			$ss = $this->seek();
			if ($this->literal("...")) {
				$sss = $this->seek();
				if (!$this->literal(")")) {
					$this->throwParseError("... has to be after the final argument");
				}
				$arg[2] = true;
				$this->seek($sss);
			} else {
				$this->seek($ss);
			}

			$args[] = $arg;
			if (!$this->literal(",")) break;
		}

		if (!$this->literal(")")) {
			$this->seek($s);
			return false;
		}

		$out = $args;
		return true;
	}

	protected function color(&$out) {
		$color = ['color'];

		if ($this->match('(#([0-9a-f]{6})|#([0-9a-f]{3}))', $m)) {
			if (isset($m[3])) {
				$num = $m[3];
				$width = 16;
			} else {
				$num = $m[2];
				$width = 256;
			}

			$num = hexdec($num);
			foreach ([3,2,1] as $i) {
				$t = $num % $width;
				$num /= $width;

				$color[$i] = $t * (256/$width) + $t * floor(16/$width);
			}

			$out = $color;
			return true;
		}

		return false;
	}

	protected function unit(&$unit) {
		if ($this->match('([0-9]*(\.)?[0-9]+)([%a-zA-Z]+)?', $m)) {
			$unit = ["number", $m[1], empty($m[3]) ? "" : $m[3]];
			return true;
		}
		return false;
	}

	protected function string(&$out) {
		$s = $this->seek();
		if ($this->literal('"', false)) {
			$delim = '"';
		} elseif ($this->literal("'", false)) {
			$delim = "'";
		} else {
			return false;
		}

		$content = [];
		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = false;

		while ($this->matchString($m, $delim)) {
			$content[] = $m[1];
			if ($m[2] == "#{") {
				$this->count -= strlen($m[2]);
				if ($this->interpolation($inter, false)) {
					$content[] = $inter;
				} else {
					$this->count += strlen($m[2]);
					$content[] = "#{"; // ignore it
				}
			} elseif ($m[2] == '\\') {
				$content[] = $m[2];
				if ($this->literal($delim, false)) {
					$content[] = $delim;
				}
			} else {
				$this->count -= strlen($delim);
				break; // delim
			}
		}

		$this->eatWhiteDefault = $oldWhite;

		if ($this->literal($delim)) {
			$out = ["string", $delim, $content];
			return true;
		}

		$this->seek($s);
		return false;
	}

	protected function mixedKeyword(&$out) {
		$s = $this->seek();

		$parts = [];

		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = false;

		while (true) {
			if ($this->keyword($key)) {
				$parts[] = $key;
				continue;
			}

			if ($this->interpolation($inter)) {
				$parts[] = $inter;
				continue;
			}

			break;
		}

		$this->eatWhiteDefault = $oldWhite;

		if (count($parts) == 0) return false;

		if ($this->eatWhiteDefault) {
			$this->whitespace();
		}

		$out = $parts;
		return true;
	}

	// an unbounded string stopped by $end
	protected function openString($end, &$out, $nestingOpen=null) {
		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = false;

		$stop = ["'", '"', "#{", $end];
		$stop = array_map([$this, "preg_quote"], $stop);
		$stop[] = self::$commentMulti;

		$patt = '(.*?)('.implode("|", $stop).')';

		$nestingLevel = 0;

		$content = [];
		while ($this->match($patt, $m, false)) {
			if (isset($m[1]) && $m[1] !== '') {
				$content[] = $m[1];
				if ($nestingOpen) {
					$nestingLevel += substr_count($m[1], $nestingOpen);
				}
			}

			$tok = $m[2];

			$this->count-= strlen($tok);
			if ($tok == $end) {
				if ($nestingLevel == 0) {
					break;
				} else {
					$nestingLevel--;
				}
			}

			if (($tok == "'" || $tok == '"') && $this->string($str)) {
				$content[] = $str;
				continue;
			}

			if ($tok == "#{" && $this->interpolation($inter)) {
				$content[] = $inter;
				continue;
			}

			$content[] = $tok;
			$this->count+= strlen($tok);
		}

		$this->eatWhiteDefault = $oldWhite;

		if (count($content) == 0) return false;

		// trim the end
		if (is_string(end($content))) {
			$content[count($content) - 1] = rtrim(end($content));
		}

		$out = ["string", "", $content];
		return true;
	}

	// $lookWhite: save information about whitespace before and after
	protected function interpolation(&$out, $lookWhite=true) {
		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = true;

		$s = $this->seek();
		if ($this->literal("#{") && $this->valueList($value) && $this->literal("}", false)) {

			// TODO: don't error if out of bounds

			if ($lookWhite) {
				$left = preg_match('/\s/', $this->buffer[$s - 1]) ? " " : "";
				$right = preg_match('/\s/', $this->buffer[$this->count]) ? " ": "";
			} else {
				$left = $right = false;
			}

			$out = ["interpolate", $value, $left, $right];
			$this->eatWhiteDefault = $oldWhite;
			if ($this->eatWhiteDefault) $this->whitespace();
			return true;
		}

		$this->seek($s);
		$this->eatWhiteDefault = $oldWhite;
		return false;
	}

	// low level parsers

	// returns an array of parts or a string
	protected function propertyName(&$out) {
		$s = $this->seek();
		$parts = [];

		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = false;

		while (true) {
			if ($this->interpolation($inter)) {
				$parts[] = $inter;
			} elseif ($this->keyword($text)) {
				$parts[] = $text;
			} elseif (count($parts) == 0 && $this->match('[:.#]', $m, false)) {
				// css hacks
				$parts[] = $m[0];
			} else {
				break;
			}
		}

		$this->eatWhiteDefault = $oldWhite;
		if (count($parts) == 0) return false;

		// match comment hack
		if (preg_match(self::$whitePattern,
			$this->buffer, $m, null, $this->count))
		{
			if (!empty($m[0])) {
				$parts[] = $m[0];
				$this->count += strlen($m[0]);
			}
		}

		$this->whitespace(); // get any extra whitespace

		$out = ["string", "", $parts];
		return true;
	}

	// comma separated list of selectors
	protected function selectors(&$out) {
		$s = $this->seek();
		$selectors = [];
		while ($this->selector($sel)) {
			$selectors[] = $sel;
			if (!$this->literal(",")) break;
			while ($this->literal(",")); // ignore extra
		}

		if (count($selectors) == 0) {
			$this->seek($s);
			return false;
		}

		$out = $selectors;
		return true;
	}

	// whitespace separated list of selectorSingle
	protected function selector(&$out) {
		$selector = [];

		while (true) {
			if ($this->match('[>+~]+', $m)) {
				$selector[] = [$m[0]];
			} elseif ($this->selectorSingle($part)) {
				$selector[] = $part;
				$this->whitespace();
			} elseif ($this->match('\/[^\/]+\/', $m)) {
				$selector[] = [$m[0]];
			} else {
				break;
			}

		}

		if (count($selector) == 0) {
			return false;
		}

		$out = $selector;
		return true;
	}

	// the parts that make up
	// div[yes=no]#something.hello.world:nth-child(-2n+1)%placeholder
	protected function selectorSingle(&$out) {
		$oldWhite = $this->eatWhiteDefault;
		$this->eatWhiteDefault = false;

		$parts = [];

		if ($this->literal("*", false)) {
			$parts[] = "*";
		}

		while (true) {
			// see if we can stop early
			if ($this->match("\s*[{,]", $m)) {
				$this->count--;
				break;
			}

			$s = $this->seek();
			// self
			if ($this->literal("&", false)) {
				$parts[] = Scss::$selfSelector;
				continue;
			}

			if ($this->literal(".", false)) {
				$parts[] = ".";
				continue;
			}

			if ($this->literal("|", false)) {
				$parts[] = "|";
				continue;
			}

			// for keyframes
			if ($this->unit($unit)) {
				$parts[] = $unit;
				continue;
			}

			if ($this->keyword($name)) {
				$parts[] = $name;
				continue;
			}

			if ($this->interpolation($inter)) {
				$parts[] = $inter;
				continue;
			}

			if ($this->literal('%', false) && $this->placeholder($placeholder)) {
				$parts[] = '%';
				$parts[] = $placeholder;
				continue;
			}

			if ($this->literal("#", false)) {
				$parts[] = "#";
				continue;
			}

			// a pseudo selector
			if ($this->match("::?", $m) && $this->mixedKeyword($nameParts)) {
				$parts[] = $m[0];
				foreach ($nameParts as $sub) {
					$parts[] = $sub;
				}

				$ss = $this->seek();
				if ($this->literal("(") &&
					($this->openString(")", $str, "(") || true ) &&
					$this->literal(")"))
				{
					$parts[] = "(";
					if (!empty($str)) $parts[] = $str;
					$parts[] = ")";
				} else {
					$this->seek($ss);
				}

				continue;
			} else {
				$this->seek($s);
			}

			// attribute selector
			// TODO: replace with open string?
			if ($this->literal("[", false)) {
				$attrParts = ["["];
				// keyword, string, operator
				while (true) {
					if ($this->literal("]", false)) {
						$this->count--;
						break; // get out early
					}

					if ($this->match('\s+', $m)) {
						$attrParts[] = " ";
						continue;
					}
					if ($this->string($str)) {
						$attrParts[] = $str;
						continue;
					}

					if ($this->keyword($word)) {
						$attrParts[] = $word;
						continue;
					}

					if ($this->interpolation($inter, false)) {
						$attrParts[] = $inter;
						continue;
					}

					// operator, handles attr namespace too
					if ($this->match('[|-~\$\*\^=]+', $m)) {
						$attrParts[] = $m[0];
						continue;
					}

					break;
				}

				if ($this->literal("]", false)) {
					$attrParts[] = "]";
					foreach ($attrParts as $part) {
						$parts[] = $part;
					}
					continue;
				}
				$this->seek($s);
				// should just break here?
			}

			break;
		}

		$this->eatWhiteDefault = $oldWhite;

		if (count($parts) == 0) return false;

		$out = $parts;
		return true;
	}

	protected function variable(&$out) {
		$s = $this->seek();
		if ($this->literal("$", false) && $this->keyword($name)) {
			$out = ["var", $name];
			return true;
		}
		$this->seek($s);
		return false;
	}

	protected function keyword(&$word, $eatWhitespace = null) {
		if ($this->match('([\w_\-\*!"\'\\\\][\w\-_"\'\\\\]*)',
			$m, $eatWhitespace))
		{
			$word = $m[1];
			return true;
		}
		return false;
	}

	protected function placeholder(&$placeholder) {
		if ($this->match('([\w\-_]+)', $m)) {
			$placeholder = $m[1];
			return true;
		}
		return false;
	}

	// consume an end of statement delimiter
	protected function end() {
		if ($this->literal(';')) {
			return true;
		} elseif ($this->count == strlen($this->buffer) || $this->buffer[$this->count] == '}') {
			// if there is end of file or a closing block next then we don't need a ;
			return true;
		}
		return false;
	}

	// advance counter to next occurrence of $what
	// $until - don't include $what in advance
	// $allowNewline, if string, will be used as valid char set
	protected function to($what, &$out, $until = false, $allowNewline = false) {
		if (is_string($allowNewline)) {
			$validChars = $allowNewline;
		} else {
			$validChars = $allowNewline ? "." : "[^\n]";
		}
		if (!$this->match('('.$validChars.'*?)'.$this->preg_quote($what), $m, !$until)) return false;
		if ($until) $this->count -= strlen($what); // give back $what
		$out = $m[1];
		return true;
	}

	public function throwParseError($msg = "parse error", $count = null) {
		$count = is_null($count) ? $this->count : $count;

		$line = $this->getLineNo($count);

		if (!empty($this->sourceName)) {
			$loc = "$this->sourceName on line $line";
		} else {
			$loc = "line: $line";
		}

		if ($this->peek("(.*?)(\n|$)", $m, $count)) {
			throw new Exception("$msg: failed at `$m[1]` $loc");
		} else {
			throw new Exception("$msg: $loc");
		}
	}

	public function getLineNo($pos) {
		return 1 + substr_count(substr($this->buffer, 0, $pos), "\n");
	}

	/**
	 * Match string looking for either ending delim, escape, or string interpolation
	 *
	 * {@internal This is a workaround for preg_match's 250K string match limit. }}
	 *
	 * @param array  $m     Matches (passed by reference)
	 * @param string $delim Delimeter
	 *
	 * @return boolean True if match; false otherwise
	 */
	protected function matchString(&$m, $delim) {
		$token = null;

		$end = strpos($this->buffer, "\n", $this->count);
		if ($end === false) {
			$end = strlen($this->buffer);
		}

		// look for either ending delim, escape, or string interpolation
		foreach (['#{', '\\', $delim] as $lookahead) {
			$pos = strpos($this->buffer, $lookahead, $this->count);
			if ($pos !== false && $pos < $end) {
				$end = $pos;
				$token = $lookahead;
			}
		}

		if (!isset($token)) {
			return false;
		}

		$match = substr($this->buffer, $this->count, $end - $this->count);
		$m = [
			$match . $token,
			$match,
			$token
		];
		$this->count = $end + strlen($token);

		return true;
	}

	// try to match something on head of buffer
	protected function match($regex, &$out, $eatWhitespace = null) {
		if (is_null($eatWhitespace)) $eatWhitespace = $this->eatWhiteDefault;

		$r = '/'.$regex.'/Aisu'; //addon by surikat, u for large unicode support #http://php.net/manual/fr/function.preg-match.php
		if (preg_match($r, $this->buffer, $out, null, $this->count)) {
			$this->count += strlen($out[0]);
			if ($eatWhitespace) $this->whitespace();
			return true;
		}
		return false;
	}

	// match some whitespace
	protected function whitespace() {
		$gotWhite = false;
		while (preg_match(self::$whitePattern, $this->buffer, $m, null, $this->count)) {
			if ($this->insertComments) {
				if (isset($m[1]) && empty($this->commentsSeen[$this->count])) {
					$this->append(["comment", $m[1]]);
					$this->commentsSeen[$this->count] = true;
				}
			}
			$this->count += strlen($m[0]);
			$gotWhite = true;
		}
		return $gotWhite;
	}

	protected function peek($regex, &$out, $from=null) {
		if (is_null($from)) $from = $this->count;

		$r = '/'.$regex.'/Ais';
		$result = preg_match($r, $this->buffer, $out, null, $from);

		return $result;
	}

	protected function seek($where = null) {
		if ($where === null) return $this->count;
		else $this->count = $where;
		return true;
	}

	static function preg_quote($what) {
		return preg_quote($what, '/');
	}

	protected function show() {
		if ($this->peek("(.*?)(\n|$)", $m, $this->count)) {
			return $m[1];
		}
		return "";
	}

	// turn list of length 1 into value type
	protected function flattenList($value) {
		if ($value[0] == "list" && count($value[2]) == 1) {
			return $this->flattenList($value[2][0]);
		}
		return $value;
	}
}