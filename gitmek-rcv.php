<?php
/* copyleft 2013-2017 meklu (public domain)
 *
 * any and all re-distributions and/or modifications
 * should or should not include this disclaimer
 * depending on the douchiness-level of the distributor
 * in question
 *
 * Handles GitHub and BitBucket POST hook functionality by passing it to irker
 * Note that BitBucket support is more fragile.
 */
date_default_timezone_set("UTC");
header("Content-Type: text/plain; charset=utf-8");
/* default to failure, set to 200 on success */
http_response_code(500);

/* types */
define("INVALID_T",	-1);
define("GITHUB_T",	1 << 0);
define("BITBUCKET_T",	1 << 1);

/* you ain't gon' configure this, foo' */
define("IRKER_HOST", "127.0.0.1");
define("IRKER_PORT", 6659);

/* load the config */
include("gitmek-rcv_config.php");

function mekdie($str) {
	global $logfile;
	$success = false;
	if ($str === 0) {
		$success = true;
		http_response_code(200);
	}
	$ret = "===================================\n";
	$ret.= "=== " . strftime("%Y-%m-%d %H:%M:%S (%z)") . " ===\n";
	if ($success === true) {
		$ret.= "=== Actually purportedly successful\n";
	} else {
		$ret.= "=== ERRORS GALORE!\n";
		$ret.= "=== Message: " . $str . "\n";
	}
	if (isset($_SERVER["REMOTE_ADDR"])) {
		$ret.= "IP: " . $_SERVER["REMOTE_ADDR"] . "\n";
	}
	if (isset($_GET) && count($_GET) > 0) {
		$ret.= "\$_GET = " . var_export($_GET, true) . "\n";
	}
	if (isset($_POST) && count($_POST) > 0) {
		$ret.= "\$_POST = " . var_export($_POST, true) . "\n";
	}
	if (isset($GLOBALS["payload"])) {
		$ret.= "\$payload = " . var_export($GLOBALS["payload"], true) . "\n";
	}
	if (isset($GLOBALS["sockstr"])) {
		$ret.= "\$sockstr = " . var_export($GLOBALS["sockstr"], true) . "\n";
	}
	if (isset($logfile) && strlen($logfile) > 0) {
		error_log($ret, 3, $logfile);
	}
	if (is_string($str)) {
		$str.= "\n";
	}
	die($str);
}

if (!isset($sendto) || empty($sendto)) {
	mekdie("No send configuration!");
}

/* default type */
$type = GITHUB_T;

function getsend($payload) {
	if (!isset($payload["type"])) {
		mekdie("Empty payload?");
	}
	global $sendto;
	if (isset($sendto[$payload["type"]])) {
		$ret = array();
		$tmp = $sendto[$payload["type"]];
		if (isset($tmp[$payload["repo"]])) {
			/* Send targets can be strings too but we can
			 * cast those to a one-element array
			 */
			$ret = array_merge($ret, (array) $tmp[$payload["repo"]]);
		}
		/* wildcards */
		foreach ($tmp as $k => $v) {
			if (wild($payload["repo"], $k) === true) {
				$ret = array_merge($ret, (array) $v);
			}
		}
		if (count($ret) > 0) {
			/* remove duplicates and reset indices */
			$ret = array_values(array_flip(array_flip($ret)));
			return $ret;
		}
	}
	mekdie("No send target for payload.");
}

function getconfig($target) {
	global $config, $targetconfig;
	if (!isset($targetconfig)) {
		return $config;
	}
	if (isset($targetconfig[$target])) {
		$tmp = array();
		$tmp = array_merge($config, $targetconfig[$target]);
		return $tmp;
	}
	return $config;
}

if (isset($_GET["type"])) {
	if ($_GET["type"] === "gh") {
		$type = GITHUB_T;
	} else if ($_GET["type"] === "bb") {
		$type = BITBUCKET_T;
	} else {
		$type = INVALID_T;
	}
}

$payload = false;

if (isset($_POST["payload"])) {
	$payload = $_POST["payload"];
}

$defconfig = array(
	"color" => false,
	"shorten" => false,
	"notime" => false,
	"striporg" => false,
	"filesummary" => false,
	"commitmsglen" => 120,
);

/* unlimited by default, set this in the config */
if (!isset($maxcommits)) {
	$maxcommits = -1;
}

/* do some config merging */
if (isset($config)) {
	$config = array_merge($defconfig, $config);
} else {
	$config = $defconfig;
}
unset($defconfig);

/* test the thing if we're going cli */
if (php_sapi_name() === "cli") {
	include("expayload.php");
	$payload = $gh_ex_payload;
	if (true) {
		$type = BITBUCKET_T;
		$payload = $bb_ex_payload;
	}
}

if ($payload === false) {
	mekdie("No payload!");
}

$payload = json_decode($payload, true);

/* Sooper dooper fast wildcard function, `wild_strpos3' from
 * https://gist.github.com/meklu/d573a4ebf1c92a504825
 *
 * Cache misses cost a bit :(
 */
function wild($str, $expr) {
	if(strpos($expr, '*') === false) {
		return strcmp($expr, $str) === 0;
	}
	static $cache = array();
	if (isset($cache[$expr])) {
		if (isset($cache[$expr][$str])) {
			return $cache[$expr][$str];
		}
	} else {
		$cache[$expr] = array();
	}
	$ret = true;
	$delim = false;
	$aspos = false;
	/* string offsets */
	$eoff = 0;
	$soff = 0;
	do {
		/* asterisk position */
		$aspos = strpos($expr, '*', $eoff);
		if ($aspos === false) {
			$enew = strlen($expr);
			$snew = strlen($str);
		} else {
			$enew = $aspos;
			$delim = substr($expr, $aspos + 1, 1);
			if ($delim === false || $delim === "") {
				$snew = strlen($str);
			} else {
				$snew = strpos($str, $delim, $soff);
				if ($snew === false) {
					$snew = strlen($str);
				}
			}
		}
		/* compare strings between offset and asterisk */
		$cmplen = $enew - $eoff;
		if ($cmplen > 0) {
			$ebuf = substr($expr, $eoff, $cmplen);
			if (substr_compare($str, $ebuf, $soff, $cmplen) !== 0) {
				$ret = false;
				break;
			}
		}
		/* bump offsets */
		$eoff = $enew + 1;
		$soff = $snew;
	} while ($aspos !== false);
	$cache[$expr][$str] = $ret;
	return $ret;
}

function chkip($base, $mask, $chk) {
	if (strpos($mask, ".") === false) {
		/* numeric mask */
		$mask = (pow(2, $mask) - 1) << (32 - $mask);
	} else {
		/* ip mask */
		$mask = ip2long($mask);
	}
	$base = ip2long($base);
	$chk = ip2long($chk);
	if (($base & $mask) !== ($chk & $mask)) {
		return false;
	}
	return true;
}

function process_gh($payload) {
	/* check ip */
	if (php_sapi_name() !== "cli") {
		# 192.30.252.0/22
		if (chkip("192.30.252.0", 22, $_SERVER["REMOTE_ADDR"]) === false) {
			mekdie("IP not in acceptable range!");
		}
	}
	$event = "push";
	if (isset($_SERVER["HTTP_X_GITHUB_EVENT"])) {
		$event = $_SERVER["HTTP_X_GITHUB_EVENT"];
	}
	/* do processing */
	if ($event === "push") {
		return process_gh_commit($payload);
	} else if ($event === "issues") {
		return process_gh_issue($payload);
	} else if ($event === "issue_comment") {
		return process_gh_issuecomment($payload);
	} else if ($event === "pull_request") {
		return process_gh_pullrequest($payload);
	} else if ($event === "ping") {
		return process_gh_ping($payload);
	} else {
		mekdie("Unsupported event type '$event'!");
	}
}

function process_gh_commit($payload) {
	$commits = $payload["commits"];
	$retcommits = array();
	$atotal = 0;
	$mtotal = 0;
	$rtotal = 0;
	$branch = substr($payload["ref"], strpos($payload["ref"], "/", strpos($payload["ref"], "/") + 1) + 1);
	$pusher = $payload["pusher"]["name"];
	if ($pusher === "none") {
		$pusher = false;
	}
	foreach($commits as $commit) {
		$tmp = array();
		$tmp["id"] = $commit["id"];
		$tmp["message"] = $commit["message"];
		$tmp["author"] = $commit["author"]["name"];
		$tmp["committer"] = $commit["committer"]["name"];
		$tmp["acount"] = count($commit["added"]);
		$atotal += $tmp["acount"];
		$tmp["mcount"] = count($commit["modified"]);
		$mtotal += $tmp["mcount"];
		$tmp["rcount"] = count($commit["removed"]);
		$rtotal += $tmp["rcount"];
		$tmp["ts"] = strtotime($commit["timestamp"]);
		$retcommits[] = $tmp;
		unset($tmp);
	}
	return array(
		"type"		=> GITHUB_T,
		"event"		=> "commit",
		"ts"		=> $payload["repository"]["pushed_at"],
		"from"		=> $payload["before"],
		"to"		=> $payload["after"],
		"url"		=> $payload["repository"]["url"],
		"compare"	=> $payload["compare"],
		"pusher"	=> $pusher,
		"repo"		=> $payload["repository"]["owner"]["name"] . "/" . $payload["repository"]["name"],
		"branch"	=> $branch,
		"commits"	=> $retcommits,
		"acount"	=> $atotal,
		"mcount"	=> $mtotal,
		"rcount"	=> $rtotal,
	);
}

function process_gh_issue($payload) {
	return array(
		"type"		=> GITHUB_T,
		"event"		=> "issue",
		"url"		=> $payload["issue"]["html_url"],
		"actor"		=> $payload["sender"]["login"],
		"repo"		=> $payload["repository"]["full_name"],
		"issue"		=> $payload["issue"]["title"],
		"number"	=> $payload["issue"]["number"],
		"action"	=> $payload["action"],
	);
}

function process_gh_issuecomment($payload) {
	return array(
		"type"		=> GITHUB_T,
		"event"		=> "issuecomment",
		"url"		=> $payload["comment"]["html_url"],
		"actor"		=> $payload["sender"]["login"],
		"repo"		=> $payload["repository"]["full_name"],
		"issue"		=> $payload["issue"]["title"],
		"number"	=> $payload["issue"]["number"],
		"action"	=> $payload["action"],
	);
}

function process_gh_ping($payload) {
	/* we make a URL here because hook has no html_url */
	return array(
		"type"		=> GITHUB_T,
		"event"		=> "ping",
		"url"		=> $payload["repository"]["html_url"] . '/settings/hooks/' . $payload["hook_id"],
		"actor"		=> $payload["sender"]["login"],
		"repo"		=> $payload["repository"]["full_name"],
		"title"		=> $payload["zen"],
		"number"	=> $payload["hook_id"],
	);
}

function process_gh_pullrequest($payload) {
	return array(
		"type"		=> GITHUB_T,
		"event"		=> "pullrequest",
		"url"		=> $payload["pull_request"]["html_url"],
		"actor"		=> $payload["sender"]["login"],
		"repo"		=> $payload["repository"]["full_name"],
		"title"		=> $payload["pull_request"]["title"],
		"number"	=> $payload["pull_request"]["number"],
		"action"	=> $payload["action"],
	);
}

function process_bb($payload) {
	/* check ip */
	if(php_sapi_name() !== "cli") {
		/* https://confluence.atlassian.com/display/BITBUCKET/POST+hook+management */
		$ips = array(
			"131.103.20.165",
			"131.103.20.166",
		);
		$isgd = false;
		foreach ($ips as $gdip) {
			if (chkip($gdip, 32, $_SERVER["REMOTE_ADDRESS"]) === true) {
				$isgd = true;
				break;
			}
		}
		if ($isgd === false) {
			mekdie("IP not in acceptable range!");
		}
	}
	/* do processing */
	$commits = $payload["commits"];
	$retcommits = array();
	$atotal = 0;
	$mtotal = 0;
	$rtotal = 0;
	$from = null;
	$to = null;
	$from_brief = null;
	$to_brief = null;
	$prevparents = array();
	/* go in reverse */
	$commits = array_reverse($commits);
	foreach($commits as $commit) {
		$tmp = array();
		/* FIXME: fix this, only listening to master for now. */
		if (
			$commit["branch"] !== "master" &&
			in_array($commit["node"], $prevparents) === false
		) {
			continue;
		}
		$prevparents = $commit["parents"];
		$tmp["id"] = $commit["raw_node"];
		/* use abbreviated hashes for the comparison url */
		if ($to === null) {
			$to = $tmp["id"];
			$to_brief = $commit["node"];
		}
		/* hack... */
		$from = $commit["parents"][0];
		$from_brief = $from;
		$tmp["message"] = $commit["message"];
		/* author */
		$tmp["author"] = $commit["raw_author"];
		$tmp["author"] = substr($tmp["author"], 0, strpos($tmp["author"], "<") - 1);
		/* committer, not provided */
		$tmp["committer"] = $tmp["author"];
		/* file counts */
		$tmp["acount"] = 0;
		$tmp["mcount"] = 0;
		$tmp["rcount"] = 0;
		foreach ($commit["files"] as $file) {
			switch ($file["type"]) {
			case "added":
				$tmp["acount"] += 1;
				break;
			case "modified":
				$tmp["mcount"] += 1;
				break;
			case "removed":
				$tmp["rcount"] += 1;
				break;
			}
		}
		$atotal += $tmp["acount"];
		$mtotal += $tmp["mcount"];
		$rtotal += $tmp["rcount"];
		/* timestamp */
		$tmp["ts"] = strtotime($commit["utctimestamp"]);
		$retcommits[] = $tmp;
		unset($tmp);
	}
	/* re-reverse commits */
	$retcommits = array_reverse($retcommits);
	$url = $payload["canon_url"] . $payload["repository"]["absolute_url"];
	$compare = $url . "compare/" . $to_brief . ".." . $from_brief;
	/* compare URL's are 'flipped', e.g. HEAD..HEAD~3
	 * is equivalent to git's HEAD~3..HEAD */
	return array(
		"type"		=> BITBUCKET_T,
		"event"		=> "commit",
		/* assume 'right now', since bb doesn't provide this info */
		"ts"		=> time(),
		"from"		=> $from,
		"to"		=> $to,
		"url"		=> $url,
		"compare"	=> $compare,
		/* hmph, merely a username, perhaps look into the user GET API */
		"pusher"	=> $payload["user"],
		"repo"		=> $payload["repository"]["owner"] . "/" . $payload["repository"]["slug"],
		/* FIXME: fix this, only listening to master for now. */
		"branch"	=> "master",
		"commits"	=> $retcommits,
		"acount"	=> $atotal,
		"mcount"	=> $mtotal,
		"rcount"	=> $rtotal,
	);
}

# IRC message formatting.  For reference:
# \002 bold   \003 color   \017 reset  \026 italic/reverse  \037 underline
# 0 white           1 black         2 dark blue         3 dark green
# 4 dark red        5 brownish      6 dark purple       7 orange
# 8 yellow          9 light green   10 dark teal        11 light teal
# 12 light blue     13 light purple 14 dark gray        15 light gray

function fmt_url($str) {
	return "\00302\037$str\017";
}
function fmt_repo($str) {
	$tmp = explode("/", $str, 2);
	$tmp[0] = fmt_name($tmp[0]);
	$tmp[1] = "\00310". $tmp[1] ."\017";
	$tmp = implode("/", $tmp);
	return $tmp;
}
function fmt_name($str) {
	return "\00312$str\017";
}
function fmt_commit_name($str) {
	return fmt_name($str) . ":";
}
function fmt_commit_name_nocolor($str) {
	return "| $str:";
}
function fmt_branch($str) {
	return "\00306$str\017";
}
/* not used atm */
function fmt_tag($str) {
	return "\00306$str\017";
}
function fmt_hash($str) {
	return "\00305$str\017";
}
function fmt_hash_nocolor($str) {
	return "* $str";
}
function fmt_count($count) {
	return "\00309$count\017";
}
function fmt_issue($str) {
	return "\00307$str\017";
}
function fmt_action($type, $str = false) {
	if ($str === false) {
		$str = $type;
	}
	switch ($type) {
	case "deleted":
	case "closed":
	case "force-pushed":
		$color = "05";
		break;
	case "created":
	case "opened":
		$color = "09";
		break;
	case "modified":
	case "reopened":
		$color = "07";
		break;
	default:
		$color = NULL;
		break;
	}
	if ($color != NULL) {
		return "\003$color$str\017";
	}
	return $str;
}
function fmt_action_nocolor($str, $override = false) {
	if ($override !== false) {
		return $override;
	}
	return $str;
}
/* this is silly */
function fmt_passthru($str) {
	return $str;
}

/* maxlen:
 * 0: don't truncate
 * -1: really don't truncate, not even newlines
 */
function brief_message($str, $maxlen = 120) {
	$str = trim($str);
	$nlpos = strpos($str, "\n");
	if ($maxlen >= 0 && $nlpos !== false) {
		$str = substr($str, 0, $nlpos);
	}
	if ($maxlen > 0 && strlen($str) > $maxlen) {
		$str = substr($str, 0, $maxlen);
		$str.= "…";
	}
	return $str;
}

function shorten_url($url) {
	static $cache = array();
	if (isset($cache[$url])) {
		return $cache[$url];
	}
	$opts = array(
		"http" => array(
			"header" => "Content-type: application/x-www-form-urlencoded\r\n",
			"method" => "POST",
			"content" => http_build_query(array("url" => $url)),
		),
	);
	$ctx = stream_context_create($opts);
	$stream = @fopen("https://git.io/create", "r", false, $ctx);
	if ($stream === false) {
		/* damn it:*/
		return $url;
	}
	$body = stream_get_contents($stream);
	fclose($stream);
	return "https://git.io/$body";
}

function strip_org($repo) {
	$pos = strpos($repo, '/');
	return substr($repo, $pos + 1);
}

function fmt_payload($payload, $config) {
	$ret = "";
	/* set up formatting functions */
	$fmt = array();
	$fmt["url"] = "fmt_passthru";
	$fmt["repo"] = "fmt_passthru";
	$fmt["name"] = "fmt_passthru";
	$fmt["commit_name"] = "fmt_commit_name_nocolor";
	$fmt["tag"] = "fmt_passthru";
	$fmt["branch"] = "fmt_passthru";
	$fmt["hash"] = "fmt_hash_nocolor";
	$fmt["count"] = "fmt_passthru";
	$fmt["issue"] = "fmt_passthru";
	$fmt["action"] = "fmt_action_nocolor";
	/* color! */
	if ($config["color"] === true) {
		$fmt["url"] = "fmt_url";
		$fmt["repo"] = "fmt_repo";
		$fmt["name"] = "fmt_name";
		$fmt["commit_name"] = "fmt_commit_name";
		$fmt["tag"] = "fmt_tag";
		$fmt["branch"] = "fmt_branch";
		$fmt["hash"] = "fmt_hash";
		$fmt["count"] = "fmt_count";
		$fmt["issue"] = "fmt_issue";
		$fmt["action"] = "fmt_action";
	}
	$func = "fmt_payload_" . $payload["event"];
	if (function_exists($func)) {
		$ret = $func($payload, $config, $fmt);
	} else {
		mekdie("No valid payload format specified!");
	}
	/* post-process the lines */
	$repo = $fmt["repo"]($payload["repo"]);
	if ($config["striporg"] === true) {
		$repo = strip_org($repo);
	}
	$frepo = "[" . $repo . "] ";
	$ret = explode("\n", $ret);
	foreach ($ret as $k => $v) {
		/* skip empty lines */
		if ($v === "") {
			unset($ret[$k]);
			continue;
		}
		$ret[$k] = $frepo . $v;
	}
	$ret = implode("\n", $ret);
	unset($k);
	unset($v);
	unset($repo);
	unset($frepo);
	return $ret;
}

function fmt_payload_commit($payload, $config, $fmt) {
	$privmsg = "";
	$maxcommits = $payload["maxcommits"];
	if (count($payload["commits"]) === 0) {
		mekdie("Not enough commits to warrant action!");
	}
	/* process */
	$cmt_count = count($payload["commits"]);
	$cmt_truncmsg = "";
	if (isset($maxcommits) && $cmt_count > $maxcommits) {
		if ($maxcommits < 0) {
			/* unlimited */
		} else {
			array_splice(
				$payload["commits"],
				0,
				- $maxcommits
			);
			if ($maxcommits > 0) {
				$cmt_truncmsg = sprintf(
					" (truncated to %s)",
					$fmt["count"]($maxcommits)
				);
			}
		}
	}
	if ($payload["pusher"] !== false) {
		$privmsg.= sprintf(
			"%s pushed %s commit%s to %s",
			$fmt["name"]($payload["pusher"]),
			$fmt["count"]($cmt_count),
			($cmt_count === 1) ? "" : "s",
			$fmt["branch"]($payload["branch"])
		);
		if (!$config["notime"]) {
			$privmsg.= strftime(" on %Y-%m-%d at %H:%I:%S %Z", $payload["ts"]);
		}
		if ($config["shorten"]) {
			$privmsg.= sprintf(
				": %s",
				$fmt["url"](shorten_url($payload["compare"]))
			);
		} else {
			$privmsg.= ".";
		}
		$privmsg.= $cmt_truncmsg;
		$privmsg.= "\n";
	}
	foreach ($payload["commits"] as $commit) {
		$privmsg.= sprintf(
			"%s %s %s\n",
			$fmt["hash"](substr($commit["id"], 0, 8)),
			$fmt["commit_name"]($commit["author"]),
			brief_message($commit["message"], $config["commitmsglen"])
		);
	}
	if ($config["filesummary"] === true) {
		$privmsg.= sprintf(
			"Files: %s, %s, %s\n",
			$fmt["action"]("created", "+" . $payload["acount"]),
			$fmt["action"]("modified", "~" . $payload["mcount"]),
			$fmt["action"]("deleted", "-" . $payload["rcount"])
		);
	}
	if ($config["shorten"] === false) {
		$privmsg.= sprintf(
			"Diff at %s\n",
			$fmt["url"]($payload["compare"])
		);
	}
	return $privmsg;
}

function fmt_payload_issue($payload, $config, $fmt) {
	$privmsg = "";
	/* process */
	$privmsg.= sprintf(
		"%s %s issue %s",
		$fmt["name"]($payload["actor"]),
		$fmt["action"]($payload["action"]),
		$fmt["issue"]("#" . $payload["number"])
	);
	$privmsg.= sprintf(
		": %s. See %s",
		brief_message($payload["issue"], $config["commitmsglen"]),
		$fmt["url"]($config["shorten"] ? shorten_url($payload["url"]) : $payload["url"])
	);
	return $privmsg;
}

function fmt_payload_issuecomment($payload, $config, $fmt) {
	$privmsg = "";
	$action = "";
	/* process */
	if (!isset($payload["action"]) || $payload["action"] == "created") {
		$action = "commented";
	} else {
		$action = sprintf("%s a comment", $payload["action"]);
	}
	$privmsg.= sprintf(
		"%s %s on issue %s",
		$action,
		$fmt["name"]($payload["actor"]),
		$fmt["issue"]("#" . $payload["number"])
	);
	$privmsg.= sprintf(
		": %s. See %s",
		brief_message($payload["issue"], $config["commitmsglen"]),
		$fmt["url"]($config["shorten"] ? shorten_url($payload["url"]) : $payload["url"])
	);
	return $privmsg;
}

function fmt_payload_ping($payload, $config, $fmt) {
	$privmsg = "";
	/* process */
	$privmsg.= sprintf(
		"%s triggered a ping. See %s",
		$fmt["name"]($payload["actor"]),
		$fmt["url"]($config["shorten"] ? shorten_url($payload["url"]) : $payload["url"])
	);
	return $privmsg;
}

function fmt_payload_pullrequest($payload, $config, $fmt) {
	$privmsg = "";
	/* process */
	$privmsg.= sprintf(
		"%s %s pull request %s",
		$fmt["name"]($payload["actor"]),
		$fmt["action"]($payload["action"]),
		$fmt["issue"]("#" . $payload["number"])
	);
	$privmsg.= sprintf(
		": %s. See %s",
		brief_message($payload["title"], $config["commitmsglen"]),
		$fmt["url"]($config["shorten"] ? shorten_url($payload["url"]) : $payload["url"])
	);
	return $privmsg;
}

function process_irker($payload) {
	$ret = "";
	$targets = getsend($payload);
	$hashes = array();
	/* hash the targets' configs */
	foreach ($targets as $target) {
		$rawconfig = getconfig($target);
		$cfgjson = json_encode($rawconfig, true);
		$hash = md5($cfgjson);
		if (!isset($hashes[$hash])) {
			$hashes[$hash] = array();
			$hashes[$hash]["config"] = $rawconfig;
			$hashes[$hash]["targets"] = array();
		}
		$hashes[$hash]["targets"][] = $target;
		unset($cfgjson);
		unset($hash);
		unset($target);
	}
	unset($targets);
	foreach ($hashes as $hash) {
		$jsonarr = array(
			"to" => $hash["targets"],
			"privmsg" => fmt_payload($payload, $hash["config"]),
		);
		$ret.= json_encode($jsonarr) . "\n";
		unset($jsonarr);
	}
	unset($configs);
	return $ret;
}

switch($type) {
case GITHUB_T:
	echo "Got a GitHub payload…\n";
	$payload = process_gh($payload);
	break;
case BITBUCKET_T:
	echo "Got a BitBucket payload…\n";
	$payload = process_bb($payload);
	break;
default:
	mekdie("No valid parse method specified!");
}

$payload["maxcommits"] = $maxcommits;
$sockstr = process_irker($payload);

echo $sockstr . "\n";

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($sock === false) {
	mekdie(
		"Failed to acquire socket!\n" .
		socket_strerror(socket_last_error()) .
		"\n"
	);
} else {
	echo "Socket successfully created.\n";
}

echo "Attempting to connect to " . IRKER_HOST . " on port " . IRKER_PORT . "…\n";
$result = socket_connect($sock, IRKER_HOST, IRKER_PORT);
if ($result === false) {
	mekdie(
		"Connection failed!\n" .
		socket_strerror(socket_last_error()) .
		"\n"
	);
} else {
	echo "Connected.\n";
}

echo "Sending request…\n";
socket_write($sock, $sockstr, strlen($sockstr));
echo "OK.\n";

echo "Closing socket…\n";
socket_close($sock);
echo "Done.\n";

mekdie(0);
