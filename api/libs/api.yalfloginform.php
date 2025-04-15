<?php

/**
 * YALF login form implementation
 */
class YalfLoginForm {

    /**
     * Contains login form body
     *
     * @var string
     */
    protected $form = '';

    /**
     * Preset value for the login field
     *
     * @var string
     */
    protected $loginPreset = '';

    /**
     * Preset value for the password field
     *
     * @var string
     */
    protected $passwordPreset = '';

    /**
     * Determines whether to add line breaks between form elements
     *
     * @var bool
     */
    protected $breaks = true;

    /**
     * Determines whether to wrap the form in a container
     *
     * @var bool
     */
    protected $container = true;

    /**
     * Size of the input fields
     *
     * @var int
     */
    protected $inputSize = 20;

    /**
     * Is stay logged in checkbox shown?
     *
     * @var bool
     */
    protected $stayLogInFlag = false;

    /**
     * Is "stay logged in" an default behaviour.
     *
     * @var bool
     */
    protected $keepLoggedDefault = false;

    /**
     * Delimiter used for pre-filling login and password fields
     *
     * @var string
     */
    protected $prefillDelimiter = '_';

    public function __construct($br = true, $container = true) {
        global $system;
        $this->stayLogInFlag = $system->getConfigOption('YALF_AUTH_KEEP_CB');
        $this->keepLoggedDefault = $system->getConfigOption('YALF_AUTH_KEEP_DEFAULT');
        $this->loadForm($br, $container);
    }

    /**
     * Stores raw login form into private property
     * 
     * @param bool $br
     * @param bool $container
     * 
     * @return void
     */
    protected function loadForm($br, $container) {
        $this->breaks = $br;
        $this->container = $container;

        if (file_exists('DEMO_MODE')) {
            $this->loginPreset = 'admin';
            $this->passwordPreset = 'demo';
        }

        if (ubRouting::checkGet('authprefill')) {
            $prefillRaw = explode($this->prefillDelimiter, ubRouting::get('authprefill'));
            if (isset($prefillRaw[1])) {
                $this->loginPreset = trim($prefillRaw[0]);
                $this->passwordPreset = trim($prefillRaw[1]);
            }
        }

        if ($this->container) {
            $this->form .= wf_tag('div', false, 'ubLoginContainer');
        }

        $inputs = wf_HiddenInput('login_form', '1');
        $inputs .= wf_TextInput('username', __('Login'), $this->loginPreset, $this->breaks, $this->inputSize);
        $inputs .= wf_PasswordInput('password', __('Password'), $this->passwordPreset, $this->breaks, $this->inputSize, false);
        if ($this->stayLogInFlag) {
            $inputs .= wf_CheckInput('remember', __('Stay logged in'), $this->breaks,  $this->keepLoggedDefault);
        }
        $inputs .= wf_Submit(__('Log in'));
        $this->form .= wf_Form("", 'POST', $inputs, 'loginform');

        if ($this->container) {
            $this->form .= wf_tag('div', true);
        }
    }

    /**
     * Returns login form body
     * 
     * @return string
     */
    public function render() {
        return ($this->form);
    }
}
