<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once ('pre.php');
require_once ('www/file/file_utils.php');

define("FRS_EXPANDED_ICON", util_get_image_theme("ic/toggle_minus.png"));
define("FRS_COLLAPSED_ICON", util_get_image_theme("ic/toggle_plus.png"));

use Tuleap\FRS\PackagePermissionManager;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\ReleasePermissionManager;

$authorized_user = false;

$request  = HTTPRequest::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$permission_manager = new FRSPermissionManager(
    new FRSPermissionDao(),
    new FRSPermissionFactory(new FRSPermissionDao())
);

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);
$user            = UserManager::instance()->getCurrentUser();
if ($permission_manager->isAdmin($project, $user) || $permission_manager->userCanRead($project, $user)) {
    $authorized_user = true;
}

$frspf = new FRSPackageFactory();
$frsrf = new FRSReleaseFactory();
$frsff = new FRSFileFactory();

$packages = array();
$num_packages = 0;
// Retain only packages the user is authorized to access, or packages containing releases the user is authorized to access...
$res = $frspf->getFRSPackagesFromDb($group_id);
foreach ($res as $package) {
    if ($frspf->userCanRead($group_id, $package->getPackageID(), $user->getId())
         && $permission_manager->userCanRead($project, $user)) {

        if ($request->existAndNonEmpty('release_id')) {
            if($request->valid(new Valid_UInt('release_id'))) {
        	    $release_id = $request->get('release_id');
                $row3 = & $frsrf->getFRSReleaseFromDb($release_id);
            }
        }
        if (!$request->existAndNonEmpty('release_id') || $row3->getPackageID() == $package->getPackageID()) {
            $packages[$package->getPackageID()] = $package;
            $num_packages++;
        }
    }
}


if ($request->valid(new Valid_Pv('pv'))) {
    $pv = $request->get('pv');
} else {
    $pv = false;
}

$hp = Codendi_HTMLPurifier::instance();


$params = array (
    'title' => $Language->getText('file_showfiles',
    'file_p_for',
    $hp->purify($project_manager->getProject($group_id)->getPublicName())
), 'pv' => $pv);
$project->getService(Service::FILE)->displayHeader($project, $params['title']);

if ($num_packages < 1) {
    echo '<h3>' . $Language->getText('file_showfiles', 'no_file_p') . '</h3><p>' . $Language->getText('file_showfiles', 'no_p_available');
    if ($permission_manager->isAdmin($project, $user)) {
        echo '<p><a href="admin/package.php?func=add&amp;group_id='. $group_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') .']</a></p>';
    }
    file_utils_footer($params);
    exit;
}

$html = '';

if ($pv) {
    $html .= '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ':</h3>';
} else {
    $html .= "<TABLE width='100%'><TR><TD>";
    $html .= '<h3>' . $Language->getText('file_showfiles', 'p_releases') . ' ' . help_button('frs.html#delivery-manager-jargon') . '</h3>';
    $html .= "</TD>";
    $html .= "<TD align='left'> ( <A HREF='showfiles.php?group_id=$group_id&pv=1'><img src='" . util_get_image_theme("msg.png") . "' border='0'>&nbsp;" . $Language->getText('global', 'printer_version') . "</A> ) </TD>";
    $html .= "</TR></TABLE>";

    $html .= '<p>' . $Language->getText('file_showfiles', 'select_release') . '</p>';

}
// get unix group name for path
$group_unix_name = $project->getUnixName();

$proj_stats['packages'] = $num_packages;
$pm   = PermissionsManager::instance();
$fmmf = new FileModuleMonitorFactory();

$javascript_packages_array = array();

if (!$pv && $permission_manager->isAdmin($project, $user)) {
    $html .= '<p><a href="admin/package.php?func=add&amp;group_id='. $group_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'create_new_p') .']</a></p>';
}

$package_permission_manager = new PackagePermissionManager($permission_manager, $frspf);
$release_permission_manager = new ReleasePermissionManager($permission_manager, $frsrf);

// Iterate and show the packages
while (list ($package_id, $package) = each($packages)) {
    $can_see_package = false;

    if ($package->isActive()) {
        $emphasis = 'strong';
    } else {
        $emphasis = 'em';
    }

    $can_see_package = $package_permission_manager->canUserSeePackage($user, $package, $project);

    if ($can_see_package) {
        detectSpecialCharactersInName($package->getName(), $GLOBALS['Language']->getText('file_showfiles', 'package'));
        $html .= '<fieldset class="package">';
        $html .= '<legend>';
        if (!$pv) {
            $html .= '<a href="#" onclick="javascript:toggle_package(\'p_'.$package_id.'\'); return false;" /><img src="'.FRS_EXPANDED_ICON.'" id="img_p_'.$package_id.'" /></a>&nbsp;';
        }
        $html .= " <$emphasis>". $hp->purify(util_unconvert_htmlspecialchars($package->getName())) ."</$emphasis>";
        if (!$pv) {
            if ($permission_manager->isAdmin($project, $user)) {
                $html .= '     <a href="admin/package.php?func=edit&amp;group_id='. $group_id .'&amp;id=' . $package_id . '" title="'.  $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML)  .'">';
                $html .= '       '. $GLOBALS['HTML']->getImage('ic/edit.png',array('alt'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) , 'title'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) ));
                $html .= '</a>';
            }
            $html .= ' &nbsp; ';
            $html .= '  <a href="filemodule_monitor.php?filemodule_id=' . $package_id . '&group_id='.$group_id.'">';
            if ($fmmf->isMonitoring($package_id, $user, false)) {
                $html .= '<img src="'.util_get_image_theme("ic/notification_stop.png").'" alt="'.$Language->getText('file_showfiles', 'stop_monitoring').'" title="'.$Language->getText('file_showfiles', 'stop_monitoring').'" />';
            } else {
                $html .= '<img src="'.util_get_image_theme("ic/notification_start.png").'" alt="'.$Language->getText('file_showfiles', 'start_monitoring').'" title="'.$Language->getText('file_showfiles', 'start_monitoring').'" />';
            }
            $html .= '</a>';
            if ($permission_manager->isAdmin($project, $user)) {
                $html .= '     &nbsp;&nbsp;<a href="admin/package.php?func=delete&amp;group_id='. $group_id .'&amp;id=' . $package_id .'" title="'.  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML)  .'" onclick="return confirm(\''.  $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'warn'), CODENDI_PURIFIER_CONVERT_HTML)  .'\');">'
                            . $GLOBALS['HTML']->getImage('ic/trash.png', array('alt'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) , 'title'=>  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) )) .'</a>';
            }
        }
        $html .= '</legend>';

        if ($package->isHidden()) {
            //TODO i18n
            $html .= '<div style="text-align:center"><em>'.$Language->getText('file_showfiles', 'hidden_package').'</em></div>';
        }
        // get the releases of the package
        // Order by release_date and release_id in case two releases
        // are published the same day
        $res_release = $frsrf->getFRSReleasesFromDb($package_id);
        $num_releases = count($res_release);

        if (!isset ($proj_stats['releases']))
            $proj_stats['releases'] = 0;
        $proj_stats['releases'] += $num_releases;

        $javascript_releases_array = array();
        $html .= '<div id="p_'.$package_id.'">';
        if (!$pv && $permission_manager->isAdmin($project, $user)) {
            $html .= '<p><a href="admin/release.php?func=add&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'">['. $GLOBALS['Language']->getText('file_admin_editpackages', 'add_releases') .']</a></p>';
        }
        if (!$res_release || $num_releases < 1) {
            $html .= '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
        } else {
            $cpt_release = 0;
            // iterate and show the releases of the package
            foreach ($res_release as $package_release) {
                $can_see_release = false;

                if ($package_release->isActive()) {
                    $emphasis = 'strong';
                } else {
                    $emphasis = 'em';
                }

                $can_see_release = $release_permission_manager->canUserSeeRelease($user, $package_release, $project);
                if ($can_see_release) {
                    detectSpecialCharactersInName($package_release->getName(), $GLOBALS['Language']->getText('file_showfiles', 'release'));

                    $permission_exists = $pm->isPermissionExist($package_release->getReleaseID(), 'RELEASE_READ');

                    // Highlight the release if one was chosen
                    if ($request->existAndNonEmpty('release_id')) {
                        if($request->valid(new Valid_UInt('release_id'))) {
            	            $release_id = $request->get('release_id');
            	            if ($release_id == $package_release->getReleaseID()) {
            	            	$bgcolor = 'boxitemalt';
            	            }
                        } else {
                            $bgcolor = 'boxitem';
                        }
                    } else {
                        $bgcolor = 'boxitem';
                    }
                    $html .= '<table width="100%" class="release">';
                    $html .= ' <TR id="p_'.$package_id.'r_'.$package_release->getReleaseID().'">';
                    $html .= '  <TD>';
                    if (!$pv) {
                        $html .= '<a href="#" onclick="javascript:toggle_release(\'p_'.$package_id.'\', \'r_'.$package_release->getReleaseID().'\'); return false;" /><img src="'.FRS_EXPANDED_ICON.'" id="img_p_'.$package_id.'r_'.$package_release->getReleaseID().'" /></a>';
                    }
                    $html .= "     <$emphasis>". $hp->purify($package_release->getName()) . "</$emphasis>";
                    if (!$pv) {
                        if ($permission_manager->isAdmin($project, $user)) {
                            $html .= '     <a href="admin/release.php?func=edit&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id=' . $package_release->getReleaseID() . '" title="'.  $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML)  .'">'
                            . $GLOBALS['HTML']->getImage('ic/edit.png',array('alt'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) , 'title'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editpackages', 'edit'), CODENDI_PURIFIER_CONVERT_HTML) )) .'</a>';
                        }
                        $html .= '&nbsp;';
                        $html .= '     <a href="shownotes.php?release_id=' . $package_release->getReleaseID() . '"><img src="'.util_get_image_theme("ic/text.png").'" alt="'.$Language->getText('file_showfiles', 'read_notes').'" title="'.$Language->getText('file_showfiles', 'read_notes').'" /></a>';
                    }
                    $html .= '  </td>';
                    $html .= ' <td style="text-align:center">';
                    if ($package_release->isHidden()) {
                        $html .= '<em>'.$Language->getText('file_showfiles', 'hidden_release').'</em>';
                    }
                    $html .= '</td> ';
                    $html .= '  <TD class="release_date">' . format_date("Y-m-d", $package_release->getReleaseDate()) . '';
                    if (!$pv && $permission_manager->isAdmin($project, $user)) {
                        $html .= ' <a href="admin/release.php?func=delete&amp;group_id='. $group_id .'&amp;package_id='. $package_id .'&amp;id=' . $package_release->getReleaseID() . '" title="'.  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML)  .'" onclick="return confirm(\''.  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'warn'), CODENDI_PURIFIER_CONVERT_HTML) .'\');">'
                        . $GLOBALS['HTML']->getImage('ic/trash.png', array('alt'=> $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) , 'title'=>  $hp->purify($GLOBALS['Language']->getText('file_admin_editreleases', 'delete'), CODENDI_PURIFIER_CONVERT_HTML) )) .'</a>';
                    }
                    $html .= '</TD></TR>' . "\n";
                    $html .= '</table>';

                    // get the files in this release....
                    $res_file = $frsff->getFRSFileInfoListByReleaseFromDb($package_release->getReleaseID());
                    $num_files = count($res_file);

                    if (!isset ($proj_stats['files']))
                        $proj_stats['files'] = 0;
                    $proj_stats['files'] += $num_files;

                    $javascript_files_array = array();
                    if (!$res_file || $num_files < 1) {
                        $html .= '<span class="files" id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_0"><B>' . $Language->getText('file_showfiles', 'no_files') . '</B></span>' . "\n";
                        $javascript_files_array[] = "'f_0'";
                    } else {
                        $javascript_files_array[] = "'f_0'";
                        //get the file_type and processor type
                        $q = "select * from frs_filetype";
                        $res_filetype = db_query($q);
                        while ($resrow = db_fetch_array($res_filetype)) {
                            $file_type[$resrow['type_id']] = $resrow['name'];
                        }

                        $q = "select * from frs_processor";
                        $res_processor = db_query($q);
                        while ($resrow = db_fetch_array($res_processor)) {
                            $processor[$resrow['processor_id']] = $resrow['name'];
                        }

                        $html .= '<span class="files" id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_0">';

                        $title_arr = array ();
                        $title_arr[] = $Language->getText('file_admin_editreleases', 'filename');
                        $title_arr[] = $Language->getText('file_showfiles', 'size');
                        $title_arr[] = $Language->getText('file_showfiles', 'd_l');
                        $title_arr[] = $Language->getText('file_showfiles', 'arch');
                        $title_arr[] = $Language->getText('file_showfiles', 'type');
                        $title_arr[] = $Language->getText('file_showfiles', 'date');
                        $title_arr[] = $Language->getText('file_showfiles', 'md5sum');
                        $title_arr[] = $Language->getText('file_showfiles', 'user');
                        $html .= html_build_list_table_top($title_arr, false, false, true, null, "files_table") . "\n";

                        // colgroup is used here in order to avoid table resizing when expand or collapse files, with CSS properties.
                        $html .= '<colgroup>';
                        $html .= ' <col class="frs_filename_col">';
                        $html .= ' <col class="frs_size_col">';
                        $html .= ' <col class="frs_downloads_col">';
                        $html .= ' <col class="frs_architecture_col">';
                        $html .= ' <col class="frs_filetype_col">';
                        $html .= ' <col class="frs_date_col">';
                        $html .= ' <col class="frs_md5sum_col">';
                        $html .= ' <col class="frs_user_col">';
                        $html .= '</colgroup>';

                            // now iterate and show the files in this release....
                        foreach($res_file as $file_release) {
                            $filename = $file_release['filename'];
                            $list = explode('/', $filename);
                            $fname = $list[sizeof($list) - 1];
                            $html .= "\t\t" . '<TR id="p_'.$package_id.'r_'.$package_release->getReleaseID().'f_'.$file_release['file_id'].'" class="' . $bgcolor . '"><TD><B>';

                            $javascript_files_array[] = "'f_".$file_release['file_id']."'";

                            if (($package->getApproveLicense() == 0) && (isset ($GLOBALS['sys_frs_license_mandatory']) && !$GLOBALS['sys_frs_license_mandatory'])) {
                                // Allow direct download
                                $html .= '<A HREF="/file/download.php/' . $group_id . "/" . $file_release['file_id'] . "/" . $hp->purify($file_release['filename']) . '" title="' . $file_release['file_id'] . " - " . $hp->purify($fname) . '">' . $hp->purify($fname) . '</A>';
                            } else {
                                // Display popup
                                $html .= '<A HREF="javascript:showConfirmDownload(' . $group_id . ',' . $file_release['file_id'] . ')" title="' . $file_release['file_id'] . " - " . $hp->purify($fname) . '">' . $hp->purify($fname) . '</A>';
                            }
                            $size_precision = 0;
                            if ($file_release['file_size'] < 1024) {
                                $size_precision = 2;
                            }
                            $owner = UserManager::instance()->getUserById($file_release['user_id']);
                            $html .= '</B></TD>' . '<TD>' . FRSFile::convertBytesToKbytes($file_release['file_size'], $size_precision) . '</TD>' . '<TD>' . ($file_release['downloads'] ? $file_release['downloads'] : '0') . '</TD>';
                            $html .= '<TD>' . (isset ($processor[$file_release['processor']]) ?  $hp->purify($processor[$file_release['processor']], CODENDI_PURIFIER_CONVERT_HTML) : "") . '</TD>';
                            $html .= '<TD>' . (isset ($file_type[$file_release['type']]) ? $hp->purify($file_type[$file_release['type']]) : "") . '</TD>' . '<TD>' . format_date("Y-m-d", $file_release['release_time']) . '</TD>'.
                                  '<TD>' . (isset ($file_release['computed_md5'])? $hp->purify($file_release['computed_md5']): ""). '</TD>' .
                                  '<TD>' . (isset ($file_release['user_id'])? $hp->purify($owner->getRealName()): ""). '</TD>'
                            .'</TR>
                             <TR>
                                <TD class="frs_comment">
                                    <p class="help-block">'.
                                        $hp->purify($file_release['comment'], CODENDI_PURIFIER_BASIC, $group_id).'
                                    </p>
                                </TD>
                            </TR>';
                                 if (!isset ($proj_stats['size']))
                                $proj_stats['size'] = 0;
                            $proj_stats['size'] += $file_release['file_size'];
                            if (!isset ($proj_stats['downloads']))
                                $proj_stats['downloads'] = 0;
                            $proj_stats['downloads'] += $file_release['downloads'];
                        }
                        $html .= '</table>';
                        $html .= '</span>';
                    }
                    $javascript_releases_array[] = "'r_".$package_release->getReleaseID()."': [" . implode(",", $javascript_files_array) . "]";
                    $cpt_release = $cpt_release + 1;
                }
            }
            if (!$cpt_release) {
                $html .= '<B>' . $Language->getText('file_showfiles', 'no_releases') . '</B>' . "\n";
            }
        }
        $html .= '</div>';
        $html .= '</fieldset>';
        $javascript_packages_array[] = "'p_".$package_id."': {" . implode(",", $javascript_releases_array) . "}";
    }
}


?>

<SCRIPT language="JavaScript">
<!--
function showConfirmDownload(group_id,file_id) {
    url = "/file/confirm_download.php?popup=1&group_id=" + group_id + "&file_id=" + file_id;
    wConfirm = window.open(url,"confirm","width=520,height=450,resizable=1,scrollbars=1");
    wConfirm.focus();
}

function download(group_id,file_id,filename) {
    url = "/file/download.php/" + group_id + "/" + file_id +"/"+filename;
    wConfirm.close();
    self.location = url;

}

function toggle_package(package_id) {
    Element.toggle(package_id);
    toggle_image(package_id);
}

function toggle_release(package_id, release_id) {
    $A(packages[package_id][release_id]).each(function(file_id) {
        // toggle the content of the release (the files)
        Element.toggle(package_id + release_id + file_id);
    });
    toggle_image(package_id + release_id);
}

function toggle_image(image_id) {
    var img_element = $('img_' + image_id);
    if (img_element.src.indexOf('<?php echo FRS_COLLAPSED_ICON; ?>') != -1) {
        img_element.src = '<?php echo FRS_EXPANDED_ICON; ?>';
    } else {
        img_element.src = '<?php echo FRS_COLLAPSED_ICON; ?>';
    }
}

-->

</SCRIPT>
<?php
echo $html;
if (!$pv) {
    $javascript_array = 'var packages = {';
    $javascript_array .= implode(",", $javascript_packages_array);
    $javascript_array .= '}';
    print '<script language="javascript">'.$javascript_array.'</script>';

    ?>

    <script language="javascript">
    // at page loading, we only expand the first release of the package, and collapse the others
    var cpt_release;
    $H(packages).keys().each(function(package_id) {
        cpt_release = 0;
        $H(packages[package_id]).keys().each(function(release_id) {
            if (cpt_release > 0) {
                //Element.toggle(package_id + release_id);
                toggle_release(package_id, release_id);
            }
            cpt_release++;
        });
    });
    </script>

    <?php
}
// project totals (statistics)
if (isset ($proj_stats['size'])) {

    $total_size = FRSFile::convertBytesToKbytes($proj_stats['size']);

    print '<p>';
    print '<b>' . $Language->getText('file_showfiles', 'proj_total') . ': </b>';
    print $proj_stats['releases'].' '.$Language->getText('file_showfiles', 'stat_total_nb_releases').', ';
    print $proj_stats['files'].' '.$Language->getText('file_showfiles', 'stat_total_nb_files').', ';
    print $total_size.' '.$Language->getText('file_showfiles', 'stat_total_size').', ';
    print $proj_stats['downloads'].' '.$Language->getText('file_showfiles', 'stat_total_nb_downloads').'.';
    print '</p>';
}

file_utils_footer($params);
