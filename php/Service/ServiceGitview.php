<?php namespace Surikat\Service; #forked from: PHP-Git César D. Rodas <crodas@member.fsf.org> http://www.php.net/license/3_01.txt  PHP License 3.01 - http://cesar.la/git
use Suriakt\User\Auth;
use Surikat\Git\PhpGit\Git;
class ServiceGitview{
	static function surikat(){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		self::main(SURIKAT_SPATH);
	}
	static function method(){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		self::main(SURIKAT_PATH);
	}
	protected static function main($dir){
		$git = new Git($dir.'.git');
		/* commit file list */
		if (isset($_GET['commit'])){
			$commit = $_GET['commit'];
			$commit = $git->getCommit($commit); 
			$file_list = & $commit['Tree'];
		}
		elseif (isset($_GET['file'])){
			/* it is a file */
			$object = $git->getFile($_GET['file'], $type);
			if ($type == OBJ_TREE) {
				$file_list = & $object;
			}
			else{
				$content = & $object;
			}
		}
		elseif (isset($_GET['diff'])){
			include(SURIKAT_SPATH."php/Git/PhpGit/contrib.diff.php");
			$diff    = $git->getCommitDiff($_GET['diff']);
			$changes = $diff[0];
			foreach ($changes as $change) {
				$obj1 = $git->getFile($change[1]);
				$obj2 = "";
				if (isset($change[2])) {
					$obj2 = $git->getFile($change[2]);
				}
				$diff = phpdiff($obj1,$obj2);
				$diff = htmlentities($diff);
				echo ("<h1>{$change[0]}</h1>");
				echo ("<pre>$diff</pre>");
			}
		}
		if(isset($_GET['tag'])){
			$tag = $git->getTag($_GET['tag']);
			$file_list = & $tag['Tree'];
		}
		if(isset($_GET['history'])){
			$history = $git->getHistory($_GET['history'],200);
		}
		/* it is a branch  */
		if (!isset($content) && !isset($history) && !isset($file_list) && !isset($_GET['branch'])) {
			$_GET['branch'] = 'master';
		}
		if (isset($_GET['branch'])) {
			try {
				$history = $git->getHistory($_GET['branch'], 1);
			} catch(Exception $e) {
				$history = $git->getHistory('master', 1);
			}
			$commit    = $git->getCommit($history[0]["id"]);
			$file_list = $commit['Tree']; 
			unset($commit, $history);
		}
?>
<html>
<head>
    <title>PHPGit - a fast and ugly Git view</title>
    <script type="text/javascript">(function(){var x={};(function(){var c=["abstract bool break case catch char class const const_cast continue default delete deprecated dllexport dllimport do double dynamic_cast else enum explicit extern false float for friend goto if inline int long mutable naked namespace new noinline noreturn nothrow novtable operator private property protected public register reinterpret_cast return selectany short signed sizeof static static_cast struct switch template this thread throw true try typedef typeid typename union unsigned using declaration, directive uuid virtual void volatile while typeof",
"as base by byte checked decimal delegate descending event finally fixed foreach from group implicit in interface internal into is lock null object out override orderby params readonly ref sbyte sealed stackalloc string select uint ulong unchecked unsafe ushort var","package synchronized boolean implements import throws instanceof transient extends final strictfp native super","debugger export function with NaN Infinity","require sub unless until use elsif BEGIN END","and assert def del elif except exec global lambda not or pass print raise yield False True None",
"then end begin rescue ensure module when undef next redo retry alias defined","done fi"];for(var a=0;a<c.length;a++){var b=c[a].split(" ");for(var d=0;d<b.length;d++){if(b[d]){x[b[d]]=true}}}}).call(this);function J(c){return c>="a"&&c<="z"||c>="A"&&c<="Z"}function q(c,a,b,d){c.unshift(b,d||0);try{a.splice.apply(a,c)}finally{c.splice(0,2)}}var R=(function(){var c=["!","!=","!==","#","%","%=","&","&&","&&=","&=","(","*","*=","+=",",","-=","->","/","/=",":","::",";","<","<<","<<=","<=","=","==","===",
">",">=",">>",">>=",">>>",">>>=","?","@","[","^","^=","^^","^^=","{","|","|=","||","||=","~","break","case","continue","delete","do","else","finally","instanceof","return","throw","try","typeof"],a="(?:(?:(?:^|[^0-9.])\\.{1,3})|(?:(?:^|[^\\+])\\+)|(?:(?:^|[^\\-])-)";for(var b=0;b<c.length;++b){var d=c[b];if(J(d.charAt(0))){a+="|\\b"+d}else{a+="|"+d.replace(/([^=<>:&])/g,"\\$1")}}a+="|^)\\s*$";return new RegExp(a)})(),y=/&/g,A=/</g,z=/>/g,$=/\"/g;function v(c){return c.replace(y,"&amp;").replace(A,
"&lt;").replace(z,"&gt;")}var Z=/&lt;/g,Y=/&gt;/g,T=/&apos;/g,aa=/&quot;/g,S=/&amp;/g;function I(c){var a=c.indexOf("&");if(a<0){return c}for(--a;(a=c.indexOf("&#",a+1))>=0;){var b=c.indexOf(";",a);if(b>=0){var d=c.substring(a+3,b),g=10;if(d&&d.charAt(0)=="x"){d=d.substring(1);g=16}var e=parseInt(d,g);if(!isNaN(e)){c=c.substring(0,a)+String.fromCharCode(e)+c.substring(b+1)}}}return c.replace(Z,"<").replace(Y,">").replace(T,"'").replace(aa,'"').replace(S,"&")}function w(c){return"XMP"==c.tagName}var t=
null;function H(c){if(null===t){var a=document.createElement("PRE");a.appendChild(document.createTextNode('<!DOCTYPE foo PUBLIC "foo bar">\n<foo />'));t=!/</.test(a.innerHTML)}if(t){var b=c.innerHTML;if(w(c)){b=v(b)}return b}var d=[];for(var g=c.firstChild;g;g=g.nextSibling){u(g,d)}return d.join("")}function u(c,a){switch(c.nodeType){case 1:var b=c.tagName.toLowerCase();a.push("<",b);for(var d=0;d<c.attributes.length;++d){var g=c.attributes[d];if(!g.specified){continue}a.push(" ");u(g,a)}a.push(">");
for(var e=c.firstChild;e;e=e.nextSibling){u(e,a)}if(c.firstChild||!/^(?:br|link|img)$/.test(b)){a.push("</",b,">")}break;case 2:a.push(c.name.toLowerCase(),'="',c.value.replace(y,"&amp;").replace(A,"&lt;").replace(z,"&gt;").replace($,"&quot;"),'"');break;case 3:case 4:a.push(v(c.nodeValue));break}}function P(c){var a=0;return function(b){var d=null,g=0;for(var e=0,i=b.length;e<i;++e){var f=b.charAt(e);switch(f){case "\t":if(!d){d=[]}d.push(b.substring(g,e));var h=c-a%c;a+=h;for(;h>=0;h-="                ".length){d.push("                ".substring(0,
h))}g=e+1;break;case "\n":a=0;break;default:++a}}if(!d){return b}d.push(b.substring(g));return d.join("")}}var W=/(?:[^<]+|<!--[\s\S]*?--\>|<!\[CDATA\[([\s\S]*?)\]\]>|<\/?[a-zA-Z][^>]*>|<)/g,X=/^<!--/,V=/^<\[CDATA\[/,U=/^<br\b/i;function G(c){var a=c.match(W),b=[],d=0,g=[];if(a){for(var e=0,i=a.length;e<i;++e){var f=a[e];if(f.length>1&&f.charAt(0)==="<"){if(X.test(f)){continue}if(V.test(f)){b.push(f.substring(9,f.length-3));d+=f.length-12}else if(U.test(f)){b.push("\n");d+=1}else{g.push(d,f)}}else{var h=
I(f);b.push(h);d+=h.length}}}return{source:b.join(""),tags:g}}function r(c,a){var b={};(function(){var g=c.concat(a);for(var e=g.length;--e>=0;){var i=g[e],f=i[3];if(f){for(var h=f.length;--h>=0;){b[f.charAt(h)]=i}}}})();var d=a.length;return function(g,e){e=e||0;var i=[e,"pln"],f="",h=0,n=g;while(n.length){var j,k=null,m=b[n.charAt(0)];if(m){var l=n.match(m[1]);k=l[0];j=m[0]}else{for(var o=0;o<d;++o){m=a[o];var p=m[2];if(p&&!p.test(f)){continue}var l=n.match(m[1]);if(l){k=l[0];j=m[0];break}}if(!k){j=
"pln";k=n.substring(0,1)}}i.push(e+h,j);h+=k.length;n=n.substring(k.length);if(j!=="com"&&/\S/.test(k)){f=k}}return i}}var C=r([["str",/^\'(?:[^\\\']|\\[\s\S])*(?:\'|$)/,null,"'"],["str",/^\"(?:[^\\\"]|\\[\s\S])*(?:\"|$)/,null,'"'],["str",/^\`(?:[^\\\`]|\\[\s\S])*(?:\`|$)/,null,"`"]],[["pln",/^(?:[^\'\"\`\/\#]+)/,null," \r\n"],["com",/^#[^\r\n]*/,null,"#"],["com",/^\/\/[^\r\n]*/,null],["str",/^\/(?:[^\\\*\/]|\\[\s\S])+(?:\/|$)/,R],["com",/^\/\*[\s\S]*?(?:\*\/|$)/,null]]);var B=r([],[["pln",/^\s+/,
null," \r\n"],["pln",/^[a-z_$@][a-z_$@0-9]*/i,null],["lit",/^0x[a-f0-9]+[a-z]/i,null],["lit",/^(?:\d(?:_\d+)*\d*(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?[a-z]*/i,null,"123456789"],["pun",/^[^\s\w\.$@]+/,null]]);function L(c,a){for(var b=0;b<a.length;b+=2){var d=a[b+1];if(d==="pln"){var g=a[b],e=b+2<a.length?a[b+2]:c.length,i=c.substring(g,e),f=B(i,g);for(var h=0,n=f.length;h<n;h+=2){var j=f[h+1];if(j==="pln"){var k=f[h],m=h+2<n?f[h+2]:i.length,l=c.substring(k,m);if(l=="."){f[h+1]="pun"}else if(l in x){f[h+
1]="kwd"}else if(/^@?[A-Z][A-Z$]*[a-z][A-Za-z$]*$/.test(l)){f[h+1]=l.charAt(0)=="@"?"lit":"typ"}}}q(f,a,b,2);b+=f.length-2}}return a}var D=r([],[["pln",/^[^<]+/,null],["dec",/^<!\w[^>]*(?:>|$)/,null],["com",/^<!--[\s\S]*?(?:--\>|$)/,null],["src",/^<\?[\s\S]*?(?:\?>|$)/,null],["src",/^<%[\s\S]*?(?:%>|$)/,null],["src",/^<(script|style|xmp)\b[^>]*>[\s\S]*?<\/\1\b[^>]*>/i,null],["tag",/^<\/?\w[^<>]*>/,null]]);function Q(c){var a=D(c);for(var b=0;b<a.length;b+=2){if(a[b+1]==="src"){var d=a[b],g=b+2<a.length?
a[b+2]:c.length,e=c.substring(d,g),i=e.match(/^(<[^>]*>)([\s\S]*)(<\/[^>]*>)$/);if(i){a.splice(b,2,d,"tag",d+i[1].length,"src",d+i[1].length+(i[2]||"").length,"tag")}}}return a}var E=r([["atv",/^\'[^\']*(?:\'|$)/,null,"'"],["atv",/^\"[^\"]*(?:\"|$)/,null,'"'],["pun",/^[<>\/=]+/,null,"<>/="]],[["tag",/^[\w-]+/,/^</],["atv",/^[\w-]+/,/^=/],["atn",/^[\w-]+/,null],["pln",/^\s+/,null," \r\n"]]);function O(c,a){for(var b=0;b<a.length;b+=2){var d=a[b+1];if(d==="tag"){var g=a[b],e=b+2<a.length?a[b+2]:c.length,
i=c.substring(g,e),f=E(i,g);q(f,a,b,2);b+=f.length-2}}return a}function N(c,a){for(var b=0;b<a.length;b+=2){var d=a[b+1];if(d=="src"){var g=a[b],e=b+2<a.length?a[b+2]:c.length,i=s(c.substring(g,e));for(var f=0,h=i.length;f<h;f+=2){i[f]+=g}q(i,a,b,2);b+=i.length-2}}return a}function M(c,a){var b=false;for(var d=0;d<a.length;d+=2){var g=a[d+1];if(g==="atn"){var e=a[d],i=d+2<a.length?a[d+2]:c.length;b=/^on|^style$/i.test(c.substring(e,i))}else if(g=="atv"){if(b){var e=a[d],i=d+2<a.length?a[d+2]:c.length,
f=c.substring(e,i),h=f.length,n=h>=2&&/^[\"\']/.test(f)&&f.charAt(0)===f.charAt(h-1),j,k,m;if(n){k=e+1;m=i-1;j=f}else{k=e+1;m=i-1;j=f.substring(1,f.length-1)}var l=s(j);for(var o=0,p=l.length;o<p;o+=2){l[o]+=k}if(n){l.push(m,"atv");q(l,a,d+2,0)}else{q(l,a,d,2)}}b=false}}return a}function s(c){var a=C(c);a=L(c,a);return a}function F(c){var a=Q(c);a=O(c,a);a=N(c,a);a=M(c,a);return a}function K(c,a,b){var d=[],g=0,e=null,i=null,f=0,h=0,n=P(8);function j(m){if(m>g){if(e&&e!==i){d.push("</span>");e=null}if(!e&&
i){e=i;d.push('<span class="',e,'">')}var l=v(n(c.substring(g,m))).replace(/(\r\n?|\n| ) /g,"$1&nbsp;").replace(/\r\n?|\n/g,"<br>");d.push(l);g=m}}while(true){var k;if(f<a.length){if(h<b.length){k=a[f]<=b[h]}else{k=true}}else{k=false}if(k){j(a[f]);if(e){d.push("</span>");e=null}d.push(a[f+1]);f+=2}else if(h<b.length){j(b[h]);i=b[h+1];h+=2}else{break}}j(c.length);if(e){d.push("</span>")}return d.join("")}function ca(c){try{var a=G(c),b=a.source,d=a.tags,g=/^\s*</.test(b)&&/>\s*$/.test(b),e=g?F(b):
s(b);return K(b,d,e)}catch(i){if("console"in window){console.log(i);console.trace()}return c}}function ba(c){var a=[document.getElementsByTagName("pre"),document.getElementsByTagName("div"),document.getElementsByTagName("xmp")],b=[];for(var d=0;d<a.length;++d){for(var g=0;g<a[d].length;++g){b.push(a[d][g])}}a=null;var e=0;function i(){var f=(new Date).getTime()+250;for(;e<b.length&&(new Date).getTime()<f;e++){var h=b[e];if(h.className&&h.className.indexOf("prettyprint")>=0){var n=false;for(var j=
h.parentNode;j!=null;j=j.parentNode){if((j.tagName=="pre"||j.tagName=="div"||j.tagName=="xmp")&&j.className&&j.className.indexOf("prettyprint")>=0){n=true;break}}if(!n){var k=H(h);k=k.replace(/(?:\r\n?|\n)$/,"");var m=ca(k);if(!w(h)){h.innerHTML=m}else{var l=document.createElement("PRE");for(var o=0;o<h.attributes.length;++o){var p=h.attributes[o];if(p.specified){l.setAttribute(p.name,p.value)}}l.innerHTML=m;h.parentNode.replaceChild(l,h)}}}}if(e<b.length){setTimeout(i,250)}else if(c){c()}}i()};this.prettyPrint=ba})();
</script>
    <style type="text/css" media="screen">
    .str{color:#080} 
	.kwd{color:#008} 
	.com{color:#800} 
	.typ{color:#606} 
	.lit{color:#066} 
	.pun{color:#660} 
	.pln{color:#000} 
	.tag{color:#008} 
	.atn{color:#606} 
	.atv{color:#080} 
	.dec{color:#606
	.prettyprint{padding:2px;border:1px solid #888, overflow:auto} 

	@media print{.str{color:#060} 
	.kwd{color:#006;font-weight:bold} 
	.com{color:#600;font-style:italic}
	.typ{color:#404;font-weight:bold}
	.lit{color:#044}
	.pun{color:#440}
	.pln{color:#000}
	.tag{color:#006;font-weight:bold} 
	.atn{color:#404} .atv{color:#060} }
    </style>
</head>
<body>
<table>
<tr>
    <th>Branches</th>
    <th>Tags</th>
</tr>
<tr>
    <td>
    <ul>
<?php 
foreach ($git->getBranches() as $branch):
?>
    <li><a href="?branch=<?php echo $branch?>"><?php echo $branch?></a> | <a href="?history=<?php echo $branch?>">history</a> </li>
<?php
endforeach;
?>
    </ul>
    </td>
    <td>
    <ul>
<?php 
foreach ($git->getTags() as $id => $tag):
?>
    <li><a href="?tag=<?php echo $id?>"><?php echo $tag?></a></li>
<?php
endforeach;
?>
    </ul>
    </td>
</tr>
</table>


<?php 
if (isset($history)) :
?>
<table>
<tr>
    <th>Author</th>
    <th>Commit ID</th>
    <th>Comment</th>
    <th>Date</th>
</tr>
<?php
foreach($history as $commit):
?>
<tr>
    <td><?php echo $commit['author']?></td>
    <td><a href="?commit=<?php echo $commit['id']?>"><?php echo $commit['id']?></a></td>
    <td><?php echo $commit['comment']?></td>
    <td><?php echo $commit['time']?></td>
</tr>
<?php
endforeach;
?>
</table>
<?php 
endif;
?>

<?php
if (isset($commit)) {
    echo "<h2>Commit by {$commit['author']} </h2>";
    echo "<img src='http://www.gravatar.com/avatar/".md5($commit['email'])."'/>";
    echo "<pre>{$commit['comment']}</pre>";
}
?>

<?php 
if (isset($file_list)) :
?>
<table>
<tr>
    <th>Permission</th>
    <th>Filename</th>
</tr>
<?php
foreach($file_list as $file):
?>
<tr>
    <td></td>
    <td><a href="?file=<?php echo $file->id?>"><?php echo $file->name?><?php echo $file->is_dir ? "/" : "" ?></a></td>
</tr>
<?php
endforeach;
?>
</table>
<?php 
endif;
?>


<?php
if (isset($content)) :
?>
<pre class="prettyprint">
<?php echo htmlentities($content);?>
</pre>
<script>prettyPrint();</script>

<?php
endif;
?>

</body>
</html><?php


	}
	
}