<?php

if (ubRouting::get('action') == 'rest') {
    $restApi = new RestAPI();
    $restApi->catchRequest();
    die();
}