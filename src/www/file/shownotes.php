<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('www/file/file_utils.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/reference/CrossReferenceFactory.class.php');

// NTY Now only for registered users on CodeX
if (!user_isloggedin()) {
    /*
    Not logged in
    */
    exit_not_logged_in();
}
if($request->valid(new Valid_UInt('release_id'))) {
    $release_id = $request->get('release_id');
} else {
    exit_error($GLOBALS['Language']->getText('file_shownotes','not_found_err'),$GLOBALS['Language']->getText('file_shownotes','release_not_found'));
}

$frsrf = new FRSReleaseFactory();
$release =& $frsrf->getFRSReleaseFromDb($release_id);


if (!$release || !$release->isActive() || !$release->userCanRead()) {
	exit_error($Language->getText('file_shownotes','not_found_err'),$Language->getText('file_shownotes','release_not_found'));
} else {

    $hp =& CodeX_HTMLPurifier::instance();
	$group_id = $release->getGroupID();

	file_utils_header(array('title'=>$Language->getText('file_shownotes','release_notes'),'group'=>$group_id));

	$HTML->box1_top($Language->getText('file_shownotes','notes'));

	echo '<h3>'.$Language->getText('file_shownotes','release_name').': <A HREF="showfiles.php?group_id='.$group_id.'">'.$hp->purify($release->getName()).'</A></H3>
		<P>';

/*
	Show preformatted or plain notes/changes
*/
	if ($release->isPreformatted()) {
		echo '<PRE>';
        echo '<B>'.$Language->getText('file_shownotes','notes').':</B>'
             .$hp->purify($release->getNotes(), CODEX_PURIFIER_BASIC, $group_id).

            '<HR NOSHADE>'.
            '<B>'.$Language->getText('file_shownotes','changes').':</B>'
            .$hp->purify($release->getChanges(), CODEX_PURIFIER_BASIC, $group_id);
        echo '</PRE>';
    }else{
        echo '<B>'.$Language->getText('file_shownotes','notes').':</B>'
            .$hp->purify($release->getNotes(), CODEX_PURIFIER_BASIC, $group_id).

            '<HR NOSHADE>'.
            '<B>'.$Language->getText('file_shownotes','changes').':</B>'
            .$hp->purify($release->getChanges(), CODEX_PURIFIER_BASIC, $group_id);
    }
    
    $crossref_fact= new CrossReferenceFactory($release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
    $crossref_fact->fetchDatas();
    if ($crossref_fact->getNbReferences() > 0) {
        echo '<hr noshade>';
        echo '<b> '.$Language->getText('svn_utils','references').'</b>';
        $crossref_fact->DisplayCrossRefs();
    }

	$HTML->box1_bottom();

	file_utils_footer(array());

}

?>
