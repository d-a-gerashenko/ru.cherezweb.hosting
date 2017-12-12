<?php
const FSTAB_PATH = '/etc/fstab';
$fstabContent = file_get_contents(FSTAB_PATH);
$fstabFileRows = preg_split("/[\r\n]/", $fstabContent) ;
foreach ($fstabFileRows as $key => $fstabFileRow) {
    if (strpos($fstabFileRow, '#') === 0) {
        continue;
    }
    $colls = preg_split("/[\s][\s]*/", $fstabFileRow);
    if (count($colls) !== 6) {
        continue;
    }
    if ($colls[1] === '/') {
        $colls[3] = $colls[3].',usrjquota=aquota.user,grpjquota=aquota.group,jqfmt=vfsv0';
        $fstabFileRows[$key] = implode("\t", $colls);
    }
}
$newFstabContent = implode("\n", $fstabFileRows);
file_put_contents(FSTAB_PATH, $newFstabContent);