<?php

$manifestor=new Manifestator();
$appName=$system->getPageTitle();
$manifestor->setName($appName);
$manifestor->setShortName($appName);
$manifestor->render();