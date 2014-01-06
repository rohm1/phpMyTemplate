<?php
/*
See class.tpl.php for license infos
*/

/* user fonctions for the template engine */

/**
 * Assigns a variable
 *
 * {assign var=foo value=bar}
 *
 * @param array $params an array containing the parameters assigned in the template
 * @param object $_tpl the current tpl object
 * @return void
 * @see tpl::assign()
 */
function tplfunction_assign($params, $_tpl)
{
    $_tpl->assign($params['var'], $params['value'], isset($params['parse']) && $params['parse'] == 'true');
}

/**
 * Generates a HTML Select element
 *
 * Parameters
 *   - name: name of the select
 *   - options: array of options
 *   - [optional] select: value of the per default selected option
 *   - [optional] id: id to give to the select
 *   - [optional] classes: string or array of classes to give to the Select
 *
 * {htmlselect name=foo options=$options}
 *
 * @param array $params an array containing the parameters assigned in the template
 * @param object $_tpl the current tpl object
 * @return void
 */
function tplfunction_htmlselect($params, $_tpl)
{
    $name = $params['name'];
    $options = $params['options'];
    $selected = isset($params['selected']) ? $params['selected'] : null;
    $id = isset($params['id']) ? $params['id'] : null;
    $classes = isset($params['classes']) ? $params['classes'] : null;

    if (!empty($classes) && is_array($classes)) {
        $classes = implode(' ', $classes);
    }

    echo '<select name="' . $name . '"' . (!empty($id) ? ' id="' . $id . '"' : '') . (!empty($classes) != 0 ? ' class="' . $classes .'"' : '') .'>' . PHP_EOL;

    foreach ($options as $option_value => $option_text) {
        echo "\t" . '<option value="' . $option_value . '"' . ($option_value == $selected ? ' selected' : '') .'>' . $option_text . '</option>' . PHP_EOL;
    }

    echo '</select>' . PHP_EOL;
}
