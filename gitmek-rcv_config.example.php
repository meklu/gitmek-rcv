<?php
$logfile = "/var/tmp/gitmek-rcv.log";
$maxcommits = 10;

$sendto = array(
	GITHUB_T => array(
		/* SteamDB */
		"SteamDatabase/SteamLinux" => array(
			"irc://chat.freenode.net/#steamlug",
			"irc://chat.freenode.net/#steamdb",
		),
		/* Personal */
		"meklu/mekoverlay" => array(
			"irc://chat.freenode.net/meklu,isnick",
		),
	),
	BITBUCKET_T => array(
	),
);
?>
