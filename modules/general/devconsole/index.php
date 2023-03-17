<?php

if ($system->getAuthEnabled()) {
    if (cfr('ROOT')) {

        /**
         * Minimalistic development/debugging console implementation
         */
        class DevConsole {

            /**
             * System message helper instance.
             *
             * @var object
             */
            public $messages = '';

            /**
             * Some static routes etc
             */
            const URL_ME = '?module=devconsole';
            const ROUTE_PHPCON = 'phpconsole';
            const PROUTE_QUERY = 'devsqlquery';
            const PROUTE_CODE = 'devphpcode';

            public function __construct() {
                $this->initMessages();
            }

            /**
             * Inits system message helper
             * 
             * @return void
             */
            protected function initMessages() {
                $this->messages = new UbillingMessageHelper();
            }

            /**
             * Renders module controls
             * 
             * @return string
             */
            public function panel() {
                $result = '';
                $result .= wf_Link(self::URL_ME, wf_img('skins/icon_restoredb.png') . ' ' . __('SQL console'), false, 'ubButton') . ' ';
                $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_PHPCON . '=true', wf_img('skins/icon_php.png') . ' ' . __('PHP console'), false, 'ubButton') . ' ';
                return($result);
            }

            /**
             * Renders SQL console form
             * 
             * @return string
             */
            public function renderSqlConsole() {
                $result = '';
                $inputs = wf_TextArea(self::PROUTE_QUERY, '', '', true, '80x10');
                $inputs .= wf_Submit(__('Run SQL query'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
                return($result);
            }

            /**
             * Renders PHP console form
             * 
             * @return string
             */
            public function renderPhpConsole() {
                $result = '';
                $inputs = wf_TextArea(self::PROUTE_CODE, '', '', true, '80x10');
                $inputs .= wf_Submit(__('Run PHP code'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
                return($result);
            }

            /**
             * Runs some database query
             * 
             * @param string $query
             * 
             * @return string
             */
            public function runQuery($query) {
                global $loginDB;
                $result = '';
                $query_result = array();
                $stripquery = substr($query, 0, 70) . '..';
                log_register('SQLCONSOLE ' . $stripquery);
                ob_start();



                if (!empty($query)) {
                    if (!extension_loaded('mysql')) {
                        $resultRaw = mysqli_query($loginDB, $query);
                    } else {
                        $resultRaw = mysql_query($query);
                    }

                    if ($resultRaw === false) {
                        ob_end_clean();
                        $result .= $this->messages->getStyledMessage(__('Wrong query') . ': ' . $query, 'error');
                    } else {
                        if (!extension_loaded('mysql')) {
                            while (@$row = mysqli_fetch_assoc($resultRaw)) {
                                $query_result[] = $row;
                            }
                        } else {
                            while (@$row = mysql_fetch_assoc($resultRaw)) {
                                $query_result[] = $row;
                            }
                        }

                        $sqlDebugData = ob_get_contents();
                        ob_end_clean();
                        log_register('SQLCONSOLE QUERYDONE');
                    }

                    $result .= $this->messages->getStyledMessage(__('Query executed successfully') . ': ' . $query, 'success');
                    $result .= wf_delimiter(0);

                    if (!empty($query_result)) {
                        $result .= wf_tag('pre');
                        $result .= var_export($query_result, true);
                        $result .= wf_tag('pre', true);
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Query returned empty result'), 'info');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Empty query'), 'error');
                }
                return($result);
            }

        }

        $console = new DevConsole();
        //rendering controls
        show_window(__('Developers console'), $console->panel());

        //rendering interfaces
        if (ubRouting::checkGet($console::ROUTE_PHPCON)) {
            show_window(__('PHP'), $console->renderPhpConsole());
        } else {
            show_window(__('SQL'), $console->renderSqlConsole());
        }

        //performing SQL queries
        if (ubRouting::checkPost($console::PROUTE_QUERY)) {
            show_window(__('SQL query result'), $console->runQuery(ubRouting::post($console::PROUTE_QUERY)));
        }

        ///Or executing PHP code right here
        if (ubRouting::checkPost($console::PROUTE_CODE)) {
            //executing code directly here because variables/objects visibily is broken inside methods or functions
            $code = ubRouting::post($console::PROUTE_CODE);
            $phpCodeExecResult = '';
            $code = trim($code);
            if (!empty($code)) {

                $phpCodeExecResult .= $console->messages->getStyledMessage(__('Running this code'), 'info') . wf_delimiter(0);
                $phpCodeExecResult .= highlight_string('<?php' . "\n" . $code . "\n" . '?>', true);

                //executing it
                $stripcode = substr($code, 0, 70) . '..';
                log_register('DEVCONSOLE ' . $stripcode);
                ob_start();
                eval($code);
                $debugData = ob_get_contents();
                ob_end_clean();

                if (!empty($debugData)) {
                    $phpCodeExecResult .= $console->messages->getStyledMessage(__('Console debug data'), 'warning') . wf_delimiter(0);
                    $phpCodeExecResult .= wf_tag('pre') . $debugData . wf_tag('pre', true);
                } else {
                    $phpCodeExecResult .= $console->messages->getStyledMessage(__('Console debug data is empty'), 'success') . wf_delimiter(0);
                }

                log_register('DEVCONSOLE DONE');
            } else {
                $phpCodeExecResult .= $console->messages->getStyledMessage(__('Empty code part received'), 'error');
            }
            show_window(__('PHP code execution result'), $phpCodeExecResult);
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('Authorization engine disabled'));
}