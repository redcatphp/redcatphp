<?php namespace Surikat\Component\SyntaxedCSS;
/**
 * SCSS base formatter
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 */
class Formatter {
	public $indentChar = "  ";

	public $break = "\n";
	public $open = " {";
	public $close = "}";
	public $tagSeparator = ", ";
	public $assignSeparator = ": ";

	public function __construct() {
		$this->indentLevel = 0;
	}

	public function indentStr($n = 0) {
		return str_repeat($this->indentChar, max($this->indentLevel + $n, 0));
	}

	public function property($name, $value) {
		return $name . $this->assignSeparator . $value . ";";
	}

	protected function block($block) {
		if (empty($block->lines) && empty($block->children)) return;

		$inner = $pre = $this->indentStr();

		if (!empty($block->selectors)) {
			echo $pre .
				implode($this->tagSeparator, $block->selectors) .
				$this->open . $this->break;
			$this->indentLevel++;
			$inner = $this->indentStr();
		}

		if (!empty($block->lines)) {
			$glue = $this->break.$inner;
			echo $inner . implode($glue, $block->lines);
			if (!empty($block->children)) {
				echo $this->break;
			}
		}

		foreach ($block->children as $child) {
			$this->block($child);
		}

		if (!empty($block->selectors)) {
			$this->indentLevel--;
			if (empty($block->children)) echo $this->break;
			echo $pre . $this->close . $this->break;
		}
	}

	public function format($block) {
		ob_start();
		$this->block($block);
		$out = ob_get_clean();

		return $out;
	}
}