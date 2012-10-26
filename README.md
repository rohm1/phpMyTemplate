phpMyTemplate
=============

phpMyTemplate is a PHP template engine. It will allow you to separate the PHP code and the HTML so that you can more easily share the work in a team while keeping your code flexible and progressive.

##Simple setup##

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
	{$hello_world}
</body>
</html>
```

Note that with this basic setup, the say_hello.tpl file has to be place in a subdirectoy templates/ with respect to say_hello.php

##Features##
phpMyTemplate comes with a lot of features:
* In the PHP
 * addDir: adds a new directory where to search for the template file
 * assign: assigns variables that can be retrieved in the HTML
  * assign(name, value)
  * assign( array(name1 => value1, name2 => value2, ...) )
 * display: allows to choose the template file to use
 * Users function:
 ```php
 function tplfunction_myFunction ($params, $_tpl) { }
 ```
 $params: array of the arguments (see the HTML section); $_tpl: the current tpl object in case you need it
* In the HTML
 * {extends file=fname.tpl}: allows to make template inheritance
 * {block name=b1} ... {/block}: use blocks in your template inheritance to change the content of the master template's block. You can use the append=none|replace|append|prepend parameter. Default value is replace.
 * {assign var=varName value="hello world!"}
 * {if expr} ... {elseif expr} ... {else} ... {/if}
 * {foreach $array as $k => $v} {$k} => {$v} {foreachelse} The array is empty! {/foreach}
 * {while expr} ... {/while}
 * {for var=i from=1 to=10} {$i} {/for}
 {for var=i from=1 downto=10} {$i} {/for}
 You can use the step parameter; it is automatically set to 1 for to and -1 to downto.
 * Display your variables: {$myVar} or {$myArray.var} for an array
 * Constants, _GET and _POST can be accessed in your templates: $tpl.const.constName or $tpl.get.getName or $tpl.post.postName
 * In a reasonable way, you can do maths: {assign var=tmpH value=$image.height/$image.width*1000>>0}
 * Users function: {myFunction arg1=val1 arg2=val2}

Note: for the users functions or the assign function, you don't have to use quotes if the value does not have any spaces.
