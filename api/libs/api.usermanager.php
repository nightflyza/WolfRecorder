<?php

/**
 * Basic user management interface
 */
class UserManager {

    /**
     * YalfCore system object placeholder
     *
     * @var object
     */
    protected $system = '';

    /**
     * System messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some static routes etc
     */
    const URL_ME = '?module=usermanager';
    const ROUTE_DELETE = 'deleteuser';
    const ROUTE_EDIT = 'edituserdata';
    const ROUTE_PERMISSIONS = 'edituserpermissions';
    const ROUTE_NEWUSER = 'registernewuser';

    /**
     * New user parameters here
     */
    const PROUTE_DOREGISTER = 'registernewuserplease'; // just create new user flag
    const PROUTE_DOEDIT = 'editthisuser'; // username to edit user profile data as flag
    const PROUTE_DOPERMS = 'changepermissions'; // username to change permissions as flag
    const PROUTE_USERNAME = 'username';
    const PROUTE_PASSWORD = 'password';
    const PROUTE_PASSWORDCONFIRM = 'confirmation';
    const PROUTE_NICKNAME = 'nickname';
    const PROUTE_EMAIL = 'email';
    const PROUTE_ROOTUSER = 'thisisrealyrootuser'; // root user permission flag

    /**
     * Creates new user manager instance
     */

    public function __construct() {
        $this->initMessages();
        $this->initSystemCore();
    }

    /**
     * Inits current system core instance for further usage
     * 
     * @global object $system
     * 
     * @return void
     */
    protected function initSystemCore() {
        global $system;
        $this->system = $system;
    }

    /**
     * Inits system messages helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Deletes existing user
     * 
     * @param string $userName
     * 
     * @return void
     */
    public function deleteUser($userName) {
        if (file_exists(USERS_PATH . $userName)) {
            unlink(USERS_PATH . $userName);
            log_register('USER DELETE {' . $userName . '}');
        }
    }

    /**
     * Renders list of available users with some controls
     * 
     * @return string
     */
    public function renderUsersList() {
        $result = '';
        $allUsers = rcms_scandir(USERS_PATH);
        if (!empty($allUsers)) {

            $cells = wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allUsers as $index => $eachUser) {
                $cells = wf_TableCell($eachUser);
                $actControls = '';
                $actControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $eachUser, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $eachUser, wf_img('skins/icon_key.gif', __('Edit user')), $this->messages->getEditAlert()) . ' ';
                $actControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_PERMISSIONS . '=' . $eachUser, web_edit_icon(__('Permissions')), $this->messages->getEditAlert());
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        $result .= wf_delimiter();

        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_NEWUSER . '=true', web_add_icon() . ' ' . __('Register new user'), false, 'ubButton');
        return($result);
    }

    /**
     * Renders new user registration form
     * 
     * @return string
     */
    public function renderRegisterForm() {
        $result = '';

        $inputs = wf_HiddenInput(self::PROUTE_DOREGISTER, 'true');
        $inputs .= wf_TextInput(self::PROUTE_USERNAME, __('Login'), '', true, 20, 'alphanumeric');
        $inputs .= wf_PasswordInput(self::PROUTE_PASSWORD, __('Password'), '', true, 20);
        $inputs .= wf_PasswordInput(self::PROUTE_PASSWORDCONFIRM, __('Password confirmation'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_NICKNAME, __('NickName'), '', true, 20, 'alphanumeric');
        $inputs .= wf_TextInput(self::PROUTE_EMAIL, __('Email'), '', true, 20);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Registers new user
     * 
     * @return void/string on error
     */
    public function createUser() {
        $result = '';

        //all of this props are required for normal registration
        $requiredParams = array(
            self::PROUTE_USERNAME,
            self::PROUTE_PASSWORD,
            self::PROUTE_PASSWORDCONFIRM,
            self::PROUTE_NICKNAME,
            self::PROUTE_NICKNAME,
            self::PROUTE_EMAIL
        );

        if (ubRouting::checkPost($requiredParams)) {
            $newLogin = ubRouting::post(self::PROUTE_USERNAME, 'vf');
            $newPasword = ubRouting::post(self::PROUTE_PASSWORD);
            $confirmation = ubRouting::post(self::PROUTE_PASSWORDCONFIRM);
            $newNickName = ubRouting::post(self::PROUTE_NICKNAME, 'mres');
            $newEmail = ubRouting::post(self::PROUTE_EMAIL, 'mres');
            $newUserRights = '';

            if (!empty($newLogin)) {
                $userDataPath = USERS_PATH . $newLogin;
                if (!file_exists($userDataPath)) {
                    if ($newPasword == $confirmation) {
                        if (!empty($newEmail)) {
                            if (!empty($newNickName)) {
                                $newUserData = array(
                                    'admin' => $newUserRights,
                                    'password' => md5($newPasword),
                                    'nickname' => $newNickName,
                                    'username' => $newLogin,
                                    'email' => $newEmail,
                                    'hideemail' => '1',
                                    'tz' => '2'
                                );

                                $saveUserData = serialize($newUserData);

                                file_put_contents($userDataPath, $saveUserData);
                                log_register('USER REGISTER {' . $newLogin . '}');
                            } else {
                                $result .= __('Empty NickName');
                            }
                        } else {
                            $result .= __('Empty email');
                        }
                    } else {
                        $result .= __('Passwords did not match');
                    }
                } else {
                    $result .= __('User already exists');
                }
            } else {
                $result .= __('Empty login');
            }
        }
        return($result);
    }

    /**
     * Rdeders existing user editing interface
     * 
     * @param string $userName
     * 
     * @return string
     */
    public function renderEditForm($userName) {
        $result = '';
        $userName = ubRouting::filters($userName, 'vf');
        if (!empty($userName)) {
            if (file_exists(USERS_PATH . $userName)) {
                $currentUserData = $this->system->getUserData($userName);
                $inputs = wf_HiddenInput(self::PROUTE_DOEDIT, $userName);
                $inputs .= wf_PasswordInput(self::PROUTE_PASSWORD, __('New password'), '', true, 20);
                $inputs .= wf_PasswordInput(self::PROUTE_PASSWORDCONFIRM, __('New password confirmation'), '', true, 20);
                $inputs .= wf_TextInput(self::PROUTE_NICKNAME, __('NickName'), $currentUserData['nickname'], true, 20, 'alphanumeric');
                $inputs .= wf_TextInput(self::PROUTE_EMAIL, __('Email'), $currentUserData['email'], true, 20);
                $inputs .= wf_Submit(__('Save'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('User not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Empty username'), 'error');
        }
        return($result);
    }

    /**
     * Saves userdata changes if its required
     * 
     * @return void/string on error
     */
    public function saveUser() {
        $result = '';
        if (ubRouting::checkPost(self::PROUTE_DOEDIT)) {
            $editUserName = ubRouting::post(self::PROUTE_DOEDIT, 'vf');
            if (!empty($editUserName)) {
                $saveDataPath = USERS_PATH . $editUserName;

                if (file_exists($saveDataPath)) {
                    $currentUserData = $this->system->getUserData($editUserName);
                    $newUserData = $currentUserData;
                    if (!empty($currentUserData)) {
                        $updateProfile = false;

                        $newPasword = ubRouting::post(self::PROUTE_PASSWORD);
                        $confirmation = ubRouting::post(self::PROUTE_PASSWORDCONFIRM);
                        $newNickName = ubRouting::post(self::PROUTE_NICKNAME, 'mres');
                        $newEmail = ubRouting::post(self::PROUTE_EMAIL, 'mres');

                        //password update?
                        if (!empty($newPasword)) {
                            if ($newPasword == $confirmation) {
                                $newPasswordHash = md5($newPasword);
                                if ($currentUserData['password'] != $newPasswordHash) {
                                    //ok its really new password
                                    $newUserData['password'] = $newPasswordHash;
                                    $updateProfile = true;
                                }
                            } else {
                                $result .= __('Passwords did not match');
                            }
                        }

                        //nickname update
                        if (!empty($newNickName)) {
                            if ($currentUserData['nickname'] != $newNickName) {
                                $newUserData['nickname'] = $newNickName;
                                $updateProfile = true;
                            }
                        }

                        //email update
                        if (!empty($newEmail)) {
                            if ($currentUserData['email'] != $newEmail) {
                                $newUserData['email'] = $newEmail;
                                $updateProfile = true;
                            }
                        }

                        //saving profile changes if required
                        if ($updateProfile) {
                            if (is_writable($saveDataPath)) {
                                $newProfileToSave = serialize($newUserData);
                                file_put_contents($saveDataPath, $newProfileToSave);
                                log_register('USER CHANGE DATA {' . $editUserName . '}');
                            } else {
                                $result .= __('Profile write failure');
                            }
                        }
                    } else {
                        $result .= __('Profile read failure');
                    }
                } else {
                    $result .= __('User not exists');
                }
            } else {
                $result .= __('Empty username');
            }
        }
        return($result);
    }

    /**
     * Saves user permissions changes if its required
     * 
     * @return void/string on error
     */
    public function savePermissions() {
        if (ubRouting::checkPost(self::PROUTE_DOPERMS)) {
            $editUserName = ubRouting::post(self::PROUTE_DOPERMS, 'vf');
            if (!empty($editUserName)) {
                $saveDataPath = USERS_PATH . $editUserName;
                if (file_exists($saveDataPath)) {
                    $currentUserData = $this->system->getUserData($editUserName);
                    $newUserData = $currentUserData;
                    if (!empty($currentUserData)) {
                        $updateProfile = false;
                        $currentRootState = ($currentUserData['admin'] == '*') ? true : false;
                        $newRootState = (ubRouting::checkPost(self::PROUTE_ROOTUSER)) ? true : false;
                        $oldRightString = $currentUserData['admin'];
                        $systemRights = $this->system->getRightsDatabase();
                        $newRightsString = '';

                        if (ubRouting::checkPost('_rights')) {
                            $rightsTmp = ubRouting::post('_rights');
                            if (!empty($rightsTmp) AND is_array($rightsTmp)) {
                                foreach ($rightsTmp as $eachRight => $rightState) {
                                    if (isset($systemRights[$eachRight])) {
                                        //skipping unknown rights
                                        $newRightsString .= '|' . $eachRight . '|';
                                    }
                                }
                            }
                        }



                        //new user state is "have root permisssions"
                        if ($newRootState) {
                            $newRightsString = '*';
                        }

                        //take decision to update rights
                        if ($newRightsString != $oldRightString) {
                            $updateProfile = true;
                            $newUserData['admin'] = $newRightsString;
                        }

                        if ($updateProfile) {
                            if (is_writable($saveDataPath)) {
                                $newProfileToSave = serialize($newUserData);
                                file_put_contents($saveDataPath, $newProfileToSave);
                                log_register('USER CHANGE PERMISSIONS {' . $editUserName . '}');
                            } else {
                                $result .= __('Profile write failure');
                            }
                        }
                    } else {
                        $result .= __('Profile read failure');
                    }
                } else {
                    $result .= __('User not exists');
                }
            } else {
                $result .= __('Empty username');
            }
        }
    }

    /**
     * Renders form for editing users permissions
     * 
     * @param string $userName
     * 
     * @return string
     */
    public function renderPermissionsForm($userName) {
        $result = '';
        $userName = ubRouting::filters($userName, 'vf');
        if (!empty($userName)) {
            if (file_exists(USERS_PATH . $userName)) {
                $currentUserData = $this->system->getUserData($userName);

                if (!empty($currentUserData)) {
                    $rootRights = false;
                    $currentRightsString = $currentUserData['admin'];
                    $currentRightsArr = array();
                    $systemRights = $this->system->getRightsDatabase();

                    if ($currentRightsString !== '*') {
                        preg_match_all('/\|(.*?)\|/', $currentRightsString, $rights_r);
                        if (!empty($rights_r[1])) {
                            foreach ($rights_r[1] as $right) {
                                if (isset($systemRights[$right])) {
                                    $currentRightsArr[$right] = $right;
                                }
                            }
                        }
                    } else {
                        $rootRights = true;
                    }
                    //form here
                    $inputs = wf_HiddenInput(self::PROUTE_DOPERMS, $userName);
                    $inputs .= wf_CheckInput(self::PROUTE_ROOTUSER, __('User have all available rights and permissions'), true, $rootRights);
                    $inputs .= wf_tag('hr');
                    if (!$rootRights) {
                        if (!empty($systemRights)) {
                            foreach ($systemRights as $eachRightId => $eachRightDesc) {
                                $haveThisRight = (isset($currentRightsArr[$eachRightId])) ? true : false;
                                $rightLabel = __($eachRightDesc) . ' - ' . $eachRightId;
                                $inputs .= wf_CheckInput('_rights[' . $eachRightId . ']', $rightLabel, true, $haveThisRight);
                            }
                        }
                    }
                    $inputs .= wf_Submit(__('Save'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Profile read failure'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('User not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Empty username'), 'error');
        }
        return($result);
    }

}
