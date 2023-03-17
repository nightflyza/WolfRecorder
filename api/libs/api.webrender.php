<?php

$contentContainer = '';

/**
 * Shows data in primary content container
 * 
 * @global string $ContentContainer
 * @param string $title
 * @param string $align
 * @param string $data
 */
function show_window($title, $data, $align = 'left') {
    global $contentContainer;
    $window_content = '
        <table width="100%" border="0">
        <tr>
        <td><h2>' . @$title . '</h2></td>
        </tr>
        <tr>
        <td valign="top" align="' . $align . '">
        ' . @$data . '
        </td>
        </tr>
        </table>
        ';
    $contentContainer = $contentContainer . $window_content;
}

function show_error($data) {
    return show_window('', '<span class="alert_error">' . $data . '</span>', 'center');
}

function show_warning($data) {
    return show_window('', '<span class="alert_warning">' . $data . '</span>', 'center');
}

function show_success($data) {
    return show_window('', '<span class="alert_success">' . $data . '</span>', 'center');
}

function show_info($data) {
    return show_window('', '<span class="alert_info">' . $data . '</span>', 'center');
}
