<?php
/*
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright    {@link http://xoops.org/ XOOPS Project}
 * @license      {@link http://www.gnu.org/licenses/gpl-2.0.html GNU GPL 2 or later}
 * @package
 * @since
 * @author       XOOPS Development Team,
 * @author       GIJ=CHECKMATE (PEAK Corp. http://www.peak.ne.jp/)
 */

// a plugin for myAlbum-P

defined('XOOPS_ROOT_PATH') || exit('XOOPS Root Path not defined');

/*
    $db : db instance
    $myts : MyTextSanitizer instance
    $this->year : year
    $this->month : month
    $this->user_TZ : user's timezone (+1.5 etc)
    $this->server_TZ : server's timezone (-2.5 etc)
    $tzoffset_s2u : the offset from server to user
    $now : the result of time()
    $plugin = array('dirname'=>'dirname','name'=>'name','dotgif'=>'*.gif','options'=>'options')
    $just1gif : 0 or 1

    $plugin_returns[ DATE ][]
*/

// for Duplicatable
if (!preg_match('/^(\D+)(\d*)$/', $plugin['dirname'], $regs)) {
    echo('invalid dirname: ' . htmlspecialchars($plugin['dirname']));
}
$mydirnumber = $regs[2] === '' ? '' : (int)$regs[2];

// set range (added 86400 second margin "begin" & "end")
$range_start_s = mktime(0, 0, 0, $this->month, 0, $this->year);
$range_end_s   = mktime(0, 0, 0, $this->month + 1, 1, $this->year);

// options
$options = explode('|', $plugin['options']);
// options[0] : category extract
if (!empty($options[0])) {
    $whr_cid = '`cid` IN (' . addslashes($options[0]) . ')';
} else {
    $whr_cid = '1';
}

// query (added 86400 second margin "begin" & "end")
$result = $db->query('SELECT title,lid,`date` FROM ' . $db->prefix("myalbum{$mydirnumber}_photos") . " WHERE ($whr_cid) AND `date` >= $range_start_s AND `date` < $range_end_s AND `status` > 0");

while (list($title, $id, $server_time) = $db->fetchRow($result)) {
    $user_time = $server_time + $tzoffset_s2u;
    if (date('n', $user_time) != $this->month) {
        continue;
    }
    $target_date = date('j', $user_time);
    $tmp_array   = array(
        'dotgif'      => $plugin['dotgif'],
        'dirname'     => $plugin['dirname'],
        'link'        => XOOPS_URL . "/modules/{$plugin['dirname']}/photo.php?lid=$id&amp;caldate={$this->year}-{$this->month}-$target_date",
        'id'          => $id,
        'server_time' => $server_time,
        'user_time'   => $user_time,
        'name'        => 'lid',
        'title'       => $myts->htmlSpecialChars($title)
    );
    if ($just1gif) {
        // just 1 gif per a plugin & per a day
        $plugin_returns[$target_date][$plugin['dirname']] = $tmp_array;
    } else {
        // multiple gifs allowed per a plugin & per a day
        $plugin_returns[$target_date][] = $tmp_array;
    }
}
