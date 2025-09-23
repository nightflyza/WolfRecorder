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
     * Contains predefined user roles as role=>name
     *
     * @var array
     */
    protected $userRoles = array();

    /**
     * Contains predefined roles rights
     *
     * @var array
     */
    protected $rolesRights = array();

    /**
     * HyprSpace object instance for user saved records storage
     *
     * @var object
     */
    protected $hyprSpace = '';

    /**
     * Some static routes etc
     */
    const URL_ME = '?module=usermanager';
    const ROUTE_DELETE = 'deleteuser';
    const ROUTE_EDIT = 'edituserdata';
    const ROUTE_PERMISSIONS = 'edituserpermissions';
    const ROUTE_NEWUSER = 'registernewuser';
    const ROUTE_GHOSTMODE = 'ghostmode';

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
    const PROUTE_USERROLE = 'userrole';
    const PROUTE_ROOTUSER = 'thisisrealyrootuser'; // root user permission flag

    /**
     * Creates new user manager instance
     */
    public function __construct() {
        $this->initMessages();
        $this->initSystemCore();
        $this->setUserRoles();
        $this->initHyprSpace();
    }

    /**
     * Sets some predefined user roles
     * 
     * @return void
     */
    protected function setUserRoles() {
        global $ubillingConfig;
        $this->userRoles = array(
            'LIMITED' => __('User'),
            'ADMINISTRATOR' => __('Administrator'),
            'OPERATOR' => __('Operator'),
        );

        $limitedRights = $ubillingConfig->getAlterParam('LIMITED_RIGHTS');
        if (!empty($limitedRights)) {
            $limitedRights = explode(',', $limitedRights);
            $rightsString = '';
            foreach ($limitedRights as $io => $each) {
                $rightsString .= '|' . $each . '|';
            }
            $this->rolesRights['LIMITED'] = $rightsString;
            //operator have limited users rights + access to all cameras/channels
            $this->rolesRights['OPERATOR'] = $rightsString . '|OPERATOR|';
        }

        $this->rolesRights['ADMINISTRATOR'] = '*';
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
     * Inits HyprSpace instance for further usage
     *
     * @return void
     */
    protected function initHyprSpace() {
        $this->hyprSpace = new HyprSpace();
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
            //flushing ACLs
            $acl = new ACL();
            $acl->flushUser($userName);
            //flushing user records
            $this->flushUserRecords($userName);
            //deleting user
            unlink(USERS_PATH . $userName);
            log_register('USER DELETE {' . $userName . '}');
        }
    }

    /**
     * Returns all available users data
     * 
     * @return array
     */
    public function getAllUsersData() {
        $result = array();
        $allUsers = rcms_scandir(USERS_PATH);
        if (!empty($allUsers)) {
            foreach ($allUsers as $index => $eachLogin) {
                $eachUserData = $this->system->getUserData($eachLogin);
                if (!empty($eachUserData)) {
                    $result[$eachLogin]['login'] = $eachUserData['username'];
                    $result[$eachLogin]['password'] = $eachUserData['password'];
                    $result[$eachLogin]['rights'] = $eachUserData['admin'];
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is user registered or not?
     * 
     * @param string $login
     * 
     * @return bool
     */
    public function isUserRegistered($login) {
        $result = false;
        $allUsers = rcms_scandir(USERS_PATH);
        if (!empty($allUsers)) {
            foreach ($allUsers as $index => $eachLogin) {
                if ($eachLogin == $login) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns bytes count allocated by users saved records
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    protected function getUserSize($userLogin) {
        $result = 0;
        if (!empty($userLogin)) {
            $basePath = $this->hyprSpace->getPathRecords();
            if (file_exists($basePath)) {
                $userRecPath = $basePath . $userLogin . '/';
                if (file_exists($userRecPath)) {
                    $allFiles = rcms_scandir($userRecPath, '*' . Export::RECORDS_EXT);
                    if (!empty($allFiles)) {
                        foreach ($allFiles as $io => $each) {
                            $result += filesize($userRecPath . $each);
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Flushes all user saved records
     * 
     * @param string $userName
     * 
     * @return void
     */
    protected function flushUserRecords($userName) {
        if (!empty($userName)) {
            $basePath =  $this->hyprSpace->getPathRecords();
            if (file_exists($basePath)) {
                $userRecPath = $basePath . $userName . '/';
                if (file_exists($userRecPath)) {
                    rcms_delete_files($userRecPath, true);
                    log_register('USER FLUSH RECORDS {' . $userName . '}');
                }
            }
        }
    }

    /**
     * Returns role name by its short ID
     *
     * @param string $roleId
     * @return void
     */
    protected function getUserRoleName($roleId = '') {
        $result = '';
        if (isset($this->userRoles[$roleId])) {
            $result = $this->userRoles[$roleId];
        }
        return ($result);
    }

    /**
     * Returns user role ID depends on assigned permissions array
     *
     * @param string $userPermissions
     * @return void
     */
    protected function getUserRoleSet($userPermissions) {
        $result = '';
        $permSets = array_flip($this->rolesRights);
        if (!empty($userPermissions)) {
            // Sort both permission strings to ensure consistent comparison
            $userPermsArray = str_split($userPermissions);
            sort($userPermsArray);
            $normalizedUserPerms = implode('', $userPermsArray);

            foreach ($permSets as $permString => $roleId) {
                $permArray = str_split($permString);
                sort($permArray);
                $normalizedPermString = implode('', $permArray);

                if ($normalizedUserPerms === $normalizedPermString) {
                    $result = $roleId;
                    break;
                }
            }
        }

        return ($result);
    }

    /**
     * Renders list of available users with some controls
     * 
     * @return string
     */
    public function renderUsersList() {
        $result = '';
        $allUsers = rcms_scandir(USERS_PATH);
        $myLogin = whoami();
        if (!empty($allUsers)) {
            $cells = wf_TableCell(__('User'));
            $cells .= wf_TableCell(__('Size'));
            $cells .= wf_TableCell(__('Set of rights'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allUsers as $index => $eachUser) {
                $userExportsSize = $this->getUserSize($eachUser);
                $cells = wf_TableCell($eachUser);
                $actControls = '';
                $cells .= wf_TableCell(wr_convertSize($userExportsSize), '', '', 'sorttable_customkey="' . $userExportsSize . '"');
                $actControls = wf_JSAlert(self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $eachUser, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_EDIT . '=' . $eachUser, wf_img('skins/icon_key.gif', __('Edit user')), $this->messages->getEditAlert()) . ' ';
                $actControls .= wf_Link(self::URL_ME . '&' . self::ROUTE_PERMISSIONS . '=' . $eachUser, web_edit_icon(__('Permissions')), false);
                if (cfr('ROOT')) {
                    if ($myLogin != $eachUser) {
                        $ghostModeLabel = __('Login as') . ' ' . $eachUser . ' ' . __('in ghost mode');
                        $actControls .= wf_JSAlert(self::URL_ME . '&' . self::ROUTE_GHOSTMODE . '=' . $eachUser, wf_img('skins/ghost.png', $ghostModeLabel), $ghostModeLabel . '?');
                    }
                }

                $userProfileData = $this->system->getUserData($eachUser);
                $userAssignedRights = $userProfileData['admin'];


                $userRightsLabel = '⚙️' . ' ' . __('Another');

                if (!empty($userProfileData['admin'])) {
                    $roleId = $this->getUserRoleSet($userAssignedRights);
                    if (!empty($roleId)) {
                        $userRightsLabel = __($this->getUserRoleName($roleId));
                    }
                }

                $cells .= wf_TableCell($userRightsLabel);
                $cells .= wf_TableCell($actControls);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        $result .= wf_delimiter();

        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_NEWUSER . '=true', web_icon_create() . ' ' . __('Register new user'), false, 'ubButton');
        return ($result);
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
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector(self::PROUTE_USERROLE, $this->userRoles, __('Permissions'), '', true);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Registers new user
     * 
     * @param string $login
     * @param string $password
     * @param string $confirmation
     * @param string $role
     * 
     * @return void/string on error
     */
    public function createUser($login, $password, $confirmation, $role = '') {
        $result = '';
        $newLogin = ubRouting::filters($login, 'vf');
        $newPasword = ubRouting::filters($password);
        $confirmation = ubRouting::filters($confirmation);
        $newNickName = ubRouting::filters($login, 'vf');
        $newRole = ubRouting::filters($role, 'vf');
        $newEmail = $newLogin . '@wolfrecorder.com';
        $newUserRights = '';
        if (!empty($newRole)) {
            if (isset($this->rolesRights[$newRole])) {
                $newUserRights = $this->rolesRights[$newRole];
            }
        }

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

        return ($result);
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
                $inputs .= wf_HiddenInput(self::PROUTE_NICKNAME, $currentUserData['nickname']);
                $inputs .= wf_HiddenInput(self::PROUTE_EMAIL, $currentUserData['email']);
                $inputs .= wf_delimiter(0);
                $inputs .= wf_Submit(__('Save'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result .= $this->messages->getStyledMessage(__('User not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Empty username'), 'error');
        }
        return ($result);
    }

    /**
     * Saves userdata changes if its required
     * 
     * @param string $login
     * @param string $password
     * @param string $confirmation
     * 
     * @return void/string on error
     */
    public function saveUser($login, $password, $confirmation) {
        $result = '';
        $editUserName = ubRouting::filters($login, 'vf');
        if (!empty($editUserName)) {
            $saveDataPath = USERS_PATH . $editUserName;

            if (file_exists($saveDataPath)) {
                $currentUserData = $this->system->getUserData($editUserName);
                $newUserData = $currentUserData;
                if (!empty($currentUserData)) {
                    $updateProfile = false;

                    $newPasword = ubRouting::filters($password);
                    $confirmation = ubRouting::filters($confirmation);
                    $newNickName = $currentUserData['nickname'];
                    $newEmail = $currentUserData['email'];

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

        return ($result);
    }

    /**
     * Saves user permissions changes if its required
     * 
     * @return void/string on error
     */
    public function savePermissions() {
        $result = '';
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
                            if (!empty($rightsTmp) and is_array($rightsTmp)) {
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
        return ($result);
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
        return ($result);
    }
}
