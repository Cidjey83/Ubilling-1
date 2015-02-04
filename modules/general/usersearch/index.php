<?php

if (cfr('USERSEARCH')) {

    /**
     * Returns user profile fileds search form
     * 
     * @return string
     */
    function web_UserSearchFieldsForm() {
        $fieldinputs = wf_TextInput('searchquery', 'Search by', '', true, '40');
        $fieldinputs.=wf_RadioInput('searchtype', 'Real Name', 'realname', true, true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Login', 'login', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Phone', 'phone', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Mobile', 'mobile', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Email', 'email', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Notes', 'note', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Contract', 'contract', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'Payment ID', 'payid', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'IP', 'ip', true);
        $fieldinputs.=wf_RadioInput('searchtype', 'MAC', 'mac', true);
        $fieldinputs.=wf_tag('br');
        $fieldinputs.=wf_Submit('Search');
        $form = wf_Form('', 'POST', $fieldinputs);

        return($form);
    }

    /**
     * Returns user profile search results
     * 
     * @global object $ubillingConfig
     * @param string $query
     * @param string $searchtype
     * @return string
     */
    function zb_UserSearchFields($query, $searchtype) {
        global $ubillingConfig;
        $query = mysql_real_escape_string(trim($query));
        $searchtype = vf($searchtype);
        $altercfg = $ubillingConfig->getAlter();

        //check strict mode for our searchtype
        $strictsearch = array();
        if (isset($altercfg['SEARCH_STRICT'])) {
            if (!empty($altercfg['SEARCH_STRICT'])) {
                $strictsearch = explode(',', $altercfg['SEARCH_STRICT']);
                $strictsearch = array_flip($strictsearch);
            }
        }


        //construct query
        if ($searchtype == 'realname') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `realname` WHERE `realname` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'login') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `users` WHERE `login` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'phone') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `phones` WHERE `phone` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'mobile') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `phones` WHERE `mobile` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'email') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `emails` WHERE `email` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'note') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `notes` WHERE `note` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'contract') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `contracts` WHERE `contract` LIKE '" . $mask . $query . $mask . "'";
        }
        if ($searchtype == 'ip') {
            $mask = (isset($strictsearch[$searchtype]) ? '' : '%');
            $query = "SELECT `login` from `users` WHERE `IP` LIKE '" . $mask . $query . $mask . "'";
        }
        //mac-address search
        if ($searchtype == 'mac') {
            $allfoundlogins = array();
            $allMacs = zb_UserGetAllMACs();
            $searchMacPart = strtolower($query);
            if (!empty($allMacs)) {
                $allMacs = array_flip($allMacs);
                foreach ($allMacs as $eachMac => $macLogin) {
                    if (ispos($eachMac, $searchMacPart)) {
                        $allfoundlogins[] = $macLogin;
                    }
                }
            }
        }
        
        if ($searchtype == 'apt') {
            $query = "SELECT `login` from `address` WHERE `aptid` = '" . $query . "'";
        }
        if ($searchtype == 'payid') {
            if ($altercfg['OPENPAYZ_REALID']) {
                $query = "SELECT `realid` AS `login` from `op_customers` WHERE `virtualid`='" . $query . "'";
            } else {
                $query = "SELECT `login` from `users` WHERE `IP` = '" . int2ip($query) . "'";
            }
        }

        // пытаемся изобразить результат
        if ($searchtype != 'mac') {
            $allresults = simple_queryall($query);
            $allfoundlogins = array();
            if (!empty($allresults)) {
                foreach ($allresults as $io => $eachresult) {
                    $allfoundlogins[] = $eachresult['login'];
                }
                //если таки по четкому адресу искали - давайте уж в профиль со старта
                if ($searchtype == 'apt') {
                    rcms_redirect("?module=userprofile&username=" . $eachresult['login']);
                }
            }
        }

        $result = web_UserArrayShower($allfoundlogins);
        return($result);
    }

    function web_UserSearchAddressForm() {
        $form = '<form action="" method="POST">';
        $form.='<table width="100%" border="0">';
        if (!isset($_POST['citysel'])) {
            $form.='<tr class="row3"><td width="40%">' . __('City') . '</td><td>' .
                    web_CitySelectorAc() .
                    '</td></tr>';
        } else {
            // if city selected
            $cityname = zb_AddressGetCityData($_POST['citysel']);
            $cityname = $cityname['cityname'];
            $form.='<tr class="row3">
             <td width="40%">' . __('City') . '</td>
             <td>' . web_ok_icon() . ' ' . $cityname .
                    ' <input type="hidden" name="citysel" value="' . $_POST['citysel'] . '">' .
                    '</td></tr>';
            if (!isset($_POST['streetsel'])) {
                $form.='<tr class="row3"><td>' . __('Street') . '</td><td>' .
                        web_StreetSelectorAc($_POST['citysel']) .
                        '</td></tr>';
            } else {
                // if street selected
                $streetname = zb_AddressGetStreetData($_POST['streetsel']);
                $streetname = $streetname['streetname'];
                $form.='<tr class="row3"><td>' . __('Street') . '</td><td>' . web_ok_icon() . ' ' . $streetname .
                        ' <input type="hidden" name="streetsel" value="' . $_POST['streetsel'] . '">' .
                        '</td></tr>';
                if (!isset($_POST['buildsel'])) {
                    $form.='<tr class="row3"><td>' . __('Build') . '</td><td>' .
                            web_BuildSelectorAc($_POST['streetsel'])
                            . '</td></tr>';
                } else {
                    //if build selected
                    $buildnum = zb_AddressGetBuildData($_POST['buildsel']);
                    $buildnum = $buildnum['buildnum'];
                    $form.='<tr class="row3"><td>' . __('Build') . '</td><td>' . web_ok_icon() . ' ' . $buildnum .
                            ' <input type="hidden" name="buildsel" value="' . $_POST['buildsel'] . '">' .
                            '</td></tr>';
                    if (!isset($_POST['aptsel'])) {
                        $form.='<tr class="row3"><td>' . __('Apartment') . '</td><td>' .
                                web_AptSelectorAc($_POST['buildsel'])
                                . '</td></tr>';
                    } else {
                        //if atp selected
                        $aptnum = zb_AddressGetAptDataById($_POST['aptsel']);
                        $aptnum = $aptnum['apt'];
                        $form.='<tr class="row3"><td>' . __('Apartment') . '</td><td>' . web_ok_icon() . ' ' . $aptnum .
                                ' <input type="hidden" name="aptsel" value="' . $_POST['aptsel'] . '">' .
                                '</td></tr>';
                        $form.='<tr class="row3">
                         <td> <input type="hidden" name="aptsearch" value="' . $_POST['aptsel'] . '"> </td>
                         <td> <input type="submit" value="' . __('Find') . '"> </td>
                         </tr>';
                    }
                }
            }
        }

        $form.='</table>';
        $form.='</form>';

        return($form);
    }

    function web_UserSearchAddressPartialForm() {
        global $ubillingConfig;
        $alterconf = $ubillingConfig->getAlter();
        if ($alterconf['SEARCHADDR_AUTOCOMPLETE']) {
            $alladdress = zb_AddressGetFulladdresslistCached();
            natsort($alladdress);
            $addrlist = ' ';
            $autocomplete = '<script>
                    $(function() {
                    var availableAddrs = [
                  ';
            if (!empty($alladdress)) {
                foreach ($alladdress as $login => $eachaddress) {
                    $addrlist.='"' . $eachaddress . '",';
                }
            }
            //removing ending coma
            $addrlist = mb_substr($addrlist, 0, -1, 'UTF-8');

            $autocomplete.=$addrlist;

            $autocomplete.='
                                      ];
                    $( "#partialaddr" ).autocomplete({
                    source: availableAddrs
                    });
                    });
                    </script>
                  ';
        } else {
            $autocomplete = '';
        }

        $inputs = $autocomplete . '<input type=text name="partialaddr" id="partialaddr" size="30">';
        $inputs.=wf_Submit('Search');
        $result = wf_Form('', 'POST', $inputs, '', '');
        return ($result);
    }

    function web_UserSearchCFForm() {
        $allcftypes = cf_TypeGetAll();
        $cfsearchform = '<h3>' . __('Additional profile fields') . '</h3>';
        if (!empty($allcftypes)) {
            foreach ($allcftypes as $io => $eachtype) {
                $cfsearchform.=$eachtype['name'] . ' ' . cf_TypeGetSearchControl($eachtype['type'], $eachtype['id']);
            }
        } else {
            $cfsearchform = '';
        }
        return($cfsearchform);
    }

    function zb_UserSearchCF($typeid, $query) {
        $typeid = vf($typeid);
        $query = mysql_real_escape_string($query);
        $result = array();
        $dataquery = "SELECT `login` from `cfitems` WHERE `typeid`='" . $typeid . "' AND `content`LIKE '%" . $query . "%'";
        $allusers = simple_queryall($dataquery);
        if (!empty($allusers)) {
            foreach ($allusers as $io => $eachuser) {
                $result[] = $eachuser['login'];
            }
        }
        return ($result);
    }

    function web_UserSearchContractForm() {
        $result = '';
        $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        if (isset($altercfg['SEARCH_CUSTOM_CONTRACT'])) {
            if ($altercfg['SEARCH_CUSTOM_CONTRACT']) {
                $result.=wf_tag('h3') . __('Contract search') . wf_tag('h3', true);
                $inputs = wf_TextInput('searchquery', '', '', false);
                $inputs.= wf_HiddenInput('searchtype', 'contract');
                $inputs.= wf_Submit(__('Search'));
                $result.= wf_Form("", 'POST', $inputs, '');
                $result.=wf_delimiter();
            }
        }
        return ($result);
    }

    // show search forms
    $gridRows = wf_tag('tr', false, '', 'valign="top"');
    $gridRows.= wf_TableCell(wf_tag('h3', false, 'row3') . __('Full address') . wf_tag('h3', true) . web_UserSearchAddressForm(), '60%', '');
    $gridRows.= wf_TableCell(wf_tag('h3', false, 'row3') . __('Partial address') . wf_tag('h3', true) . web_UserSearchAddressPartialForm(), '', '');
    $gridRows.= wf_tag('tr', true);
    $gridRows.= wf_tag('tr', false, '', 'valign="top"');
    $gridRows.= wf_TableCell(wf_tag('h3', false) . __('Profile fields search') . wf_tag('h3', true) . web_UserSearchFieldsForm(), '', 'row3');
    $gridRows.= wf_TableCell(web_UserSearchContractForm() . web_UserSearchCFForm(), '', 'row3');
    $gridRows.= wf_tag('tr', true);

    $search_forms_grid = wf_TableBody($gridRows, '100%', 0, '');
    show_window(__('User search'), $search_forms_grid);


    // default fields search
    if (isset($_POST['searchquery'])) {
        $query = $_POST['searchquery'];
        $searchtype = $_POST['searchtype'];
        if (!empty($query)) {
            show_window(__('Search results'), zb_UserSearchFields($query, $searchtype));
        }
    }

    //full address search
    if (isset($_POST['aptsearch'])) {
        $aptquery = $_POST['aptsearch'];
        show_window(__('Search results'), zb_UserSearchFields($aptquery, 'apt'));
    }

    //partial address search
    if (isset($_POST['partialaddr'])) {
        $search_query = trim($_POST['partialaddr']);
        if (!empty($search_query)) {
            $found_users = zb_UserSearchAddressPartial($search_query);
            show_window(__('Search results'), web_UserArrayShower($found_users));
        }
    }

    //CF search
    if (isset($_POST['cfquery'])) {
        $search_query = $_POST['cfquery'];
        if (sizeof($search_query) > 0) {
            $found_users = zb_UserSearchCF($_POST['cftypeid'], $search_query);
            show_window(__('Search results'), web_UserArrayShower($found_users));
        }
    }

    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
?>