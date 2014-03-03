<?php
$logfile = "/var/tmp/gitmek-rcv.log";
$maxcommits = 10;

/* Send config */
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

/* Global display config */
$config = array(
	"color" => false,
	"shorten" => false,
	"notime" => false,
	"striporg" => false,
	"filesummary" => false,
	"commitmsglen" => 120,
);

/* Channel-specific configuration overrides */
$targetconfig = array(
	"irc://chat.freenode.net/#steamdb" => array(
		"color" => true,
		"shorten" => true,
		"notime" => true,
		"striporg" => true,
	),
	"irc://chat.freenode.net/meklu,isnick" => array(
		"color" => true,
		"filesummary" => true,
		"commitmsglen" => 50,
	),
);
?>
