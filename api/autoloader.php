<?php
//preloading of some required libs
include('api/staticloader.php');

//Register and load file classes 
spl_autoload_register(function ($className) {

    $api_directory = 'api' . DIRECTORY_SEPARATOR;
    $libs_directory = 'libs' . DIRECTORY_SEPARATOR;

    // Defined path
    $classFileName = strtolower($className);

    $apiClassFileName = $api_directory . $libs_directory . 'api.' . $classFileName . '.php';
   
    if (strpos($className, 'nya_') !== false) {
        $notOrmTable = str_replace("nya_", '', $className);

        $exec = '
            class ' . $className . ' extends NyanORM {
                public function __construct() {
                  parent::__construct();
                   $this->tableName = "' . $notOrmTable . '";
                 }
             }';

        eval($exec); //automatic models generation
    } else {
        if (file_exists($apiClassFileName)) {
            include $apiClassFileName;
        } 
    }
});
?>
