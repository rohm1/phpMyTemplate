phpMyTemplate
=============

phpMyTemplate is a PHP template engine. It will allow you to separate the PHP code and the HTML so that you can more easily share the work in a team while keeping your code flexible and progressive. phpMyTemplate is capable of template inheritance, doing maths, it has the major structure controls, you can write your own functions, and it processes the content of the variables to check for template tags inside of them. Templates are compiled to PHP and cached. You can also cache the result of a display so that templates are not processed anymore.

##Basic setup##

say_hello.php
```php
require 'class.tpl.php';
$tpl = new tpl();
$tpl->assign('say_hello', 'Hello World!');
$tpl->display('say_hello.tpl');
```

say_hello.tpl
```html
<!Doctype html>
<head></head>
<body>
	{$say_hello}
</body>
</html>
```

Note that with this basic setup, the say_hello.tpl file has to be placed in a subdirectoy templates/ with respect to say_hello.php

##Features##
phpMyTemplate comes with a lot of features:
* PHP (for the developper)
 * addDir: adds a new directory where to search for the template file
 * assign: assigns variables that can be retrieved in the HTML
  assign(name, value)
  assign( array(name1 => value1, name2 => value2, ...) )
 * display: allows to choose the template file to use and displays the result
 * capture: like display, but returns the result instead. The result can be saved by setting the second parameter to true
 * get_cached_file: retrieves a cached file; you can specify the file's max age. To be used in combination with ```capture('template.tpl', true)```
 * clear_compile_cache: clears the compile cache
 * clear_content_cache: clears the content cache
 * Users function:
 ``` function tplfunction_myFunction ($params, $_tpl) { } ```
 $params: array of the arguments (see the HTML section); $_tpl: the current tpl object in case you need it
* HTML (for the designer)
 * {extends file=fname.tpl}: allows to make template inheritance
 * {block name=b1} ... {/block}: use blocks in your template inheritance to change the content of the master template's block. You can use the append=replace|append|prepend parameter. Default value is replace.
 * {include file=myInclude.tpl}
 * {assign var=varName value="hello world!"}
 * {if expr} ... {elseif expr} ... {else} ... {/if}
 * {foreach $array as $k => $v} {$k} => {$v} {foreachelse} The array is empty! {/foreach}
 * {while expr} ... {/while}
 * {for var=$i from=1 to=10} {$i} {/for}
 {for var=$i from=10 downto=1} {$i} {/for}
 You can use the step parameter; it is automatically set to 1 for to and -1 for downto.
 * Display your variables: {$myVar} or {$myArray.var} for an array
 * Variable modifiers: $var|modifier
 The currently existing modifiers are: capitalize, lower, count (for an array), nl2br, and default:defaultValue (if $var doesn't exist, defaultValue will be used instead).
 * Constants, _GET and _POST can be accessed in your templates: $tpl.const.constName or $tpl.get.getName or $tpl.post.postName
 * Maths: {assign var=tmpH value=$image.height/$image.width*1000>>0}
 * Users function: {myFunction arg1=val1 arg2=val2}
 * There are two special characters: { and }; in order to actually display then in your templates, use {l} and {r}
 * phpMyTemplate is also capable of parsing your variables to check for templates tags; it can be useful if for your website you edit the content online, and save it in a database. Just by doing {$myVarWithTemplateTags}, your variable will be analysed, processed and displayed.

Note: for the users functions or the assign function, you don't have to use quotes if the value does not have any spaces.

##URL tricks##
* ?tplraw: returns the compiled template as PHP code
* ?tplnocompilecache or define the constant TPL_DEBUG: forces to recompile the templates
* ?tplnocontentcache: forces not to use the cached file
* ?format=json or use $tpl->display('json'): outputs in a JSON formatted string all the variables you have assigned to the template engine (useful for AJAX apps)

##The second cache level##
The first cache level (caching the compiled templates) is managed internally by the template engine, you don't have to do nothing besides using ?tplnocompilecache or defining TPL_DEBUG to recompile your templates, or calling ``` $tpl->clear_compile_cache() ``` to clear the compile cache. The idea comming with the second cache level is the following: you generate your page with content retrieved from a database that you input to the templates, but this content does not changes that fast, so you can cache the resulting generated HTML code.

```php
if(($page = $tpl->get_cached_file('template.tpl', 3600)) == false) {
	/** some code to retrieve your data and input it to the template engine **/

	$page = $tpl->capture('template.tpl', true);
}

echo $page;
```

With this example, your HTML will be cached one hour, and regenerated after that. You can of course change this value as you please, or use ?tplnocontentcache to generate a new HTML. You can also call ``` $tpl->clear_content_cache() ``` to clear the second cache.

Note: the second cache level is built upon the first cache level, so if there is a change in your template, it will not be taken into account until a new HTML is generated.

##Licence##
Copyright (c) 2011-2014, rohm1 <rp@rohm1.com>.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

 * Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.

 * Neither the name of rohm1 nor the names of his
   contributors may be used to endorse or promote products derived
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
