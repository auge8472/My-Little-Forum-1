<?php


/**
 * regular expressions
 */
$validator['email'] = "/^[^@]+@.+\.\D{2,}$/";

/**
 * general arrays
 */
$separators = array("new line"=>"\n", "comma"=>",");


/**
 * counts the chars of the words in a given string
 *
 * @param string $string
 * @param string $setting
 * @param string $message
 * @return array $error
 */
function processCountCharsInWords($string, $setting, $message) {
$error = array();
$text_arr = explode(" ",$string);
$countWords = count($text_arr);

for ($i=0; $i<$countWords; $i++)
	{
	trim($text_arr[$i]);
	$laenge = mb_strlen($text_arr[$i]);
	if ($laenge > $setting)
		{
		$error[] = str_replace("[word]", htmlspecialchars(mb_substr($text_arr[$i],0,$setting))."...", $message);
		}
	}

return $error;
} # End: processCountCharsInWords



/**
 * splits URL in parts and encodes the parts
 *
 * @param string $url
 * @return string $url
 */
function processUrlEncode($url, $uri= true) {
$url = trim($url);
$temp = parse_url($url);
$nurl .= (!empty($temp['scheme'])) ? $temp['scheme'].'://' : '';
$nurl .= (!empty($temp['user']) and !empty($temp['pass'])) ? $temp['user'].':'.$temp['pass'].'@' : '';
$nurl .= (!empty($temp['host'])) ? $temp['host'] : '';
$nurl .= (!empty($temp['port'])) ? ':'.$temp['port'] : '';
if (!empty($temp['path']))
	{
	$temp['path'] = explode("/", $temp['path']);
	for ($i = 0; $i < count($temp['path']); $i ++)
		{
		if (!empty($temp['path'][$i]))
			{
			if ($i==0 and $uri===false)
				{
				$nurl .= rawurlencode($temp['path'][$i]);
				}
			else
				{
				$nurl .= '/'.rawurlencode($temp['path'][$i]);
				}
			}
		}
	}
if (!empty($temp['query']))
	{
	$nurl .= '?';
	if (strpos($temp['query'], ';'))
		{
		$queryParts = explode(';', $temp['query']);
		}
	else if (strpos($temp['query'], '&amp;'))
		{
		$queryParts = explode('&amp;', $temp['query']);
		}
	else if (strpos($temp['query'], '&'))
		{
		$queryParts = explode('&', $temp['query']);
		}
	else
		{
		$queryParts = array($temp['query']);
		}
	for ($i = 0; $i < count($queryParts); $i++)
		{
		$splitter[$i] = explode('=', $queryParts[$i]);
		if ($i == 0)
			{
			$nurl .= $splitter[$i][0].'='.urlencode($splitter[$i][1]);
			}
		else
			{
			$nurl .= '&amp;'.$splitter[$i][0].'='.urlencode($splitter[$i][1]);
			}
		}
	}
else
	{
	$nurl .= '';
	}
$nurl .= (!empty($temp['fragment'])) ? '#'.$temp['fragment'] : '';

return $nurl;
} # End: processUrlEncode



/**
 * filters subscriptions of postings if there is a subscription for the whole thread 
 *
 * @param array $a (subscriptions)
 * @return array $a (subscriptions)
 * @return bool false
 */
function processSubscriptFilter($a) {
global $settings, $db_settings, $connid, $lang;

if (is_array($a) === false) return false;

# $temp will contain all elements of
# $a wich is a thread subscription.
# $temp will be compared with $a
# to search for posting subscriptions
# of the user who wants to subscribe
# to the whole thread
$temp = array();
$i = 0;

# loop over all elements of the parameter array $a ...
foreach ($a as $sub)
	{
	# ... to search for the elements,
	# wich are/contains thread subscriptions
	if ($sub['thread_notify'] == 1)
		{
		$temp[$i]['tid'] = $sub['tid'];
		$temp[$i]['id'] = $sub['id'];
		}
	$i++;
	}

# second loop over parameter array $a (all subscriptions)
for ($i = 0; $i < count($a); $i++)
	{
	# additional loop over array $temp (thread subscriptions)
	foreach ($temp as $tmp)
		{
		# has thread-ID of $a[$i] the same value like $tmp and
		# the posting-ID of $a[$i] and $tmp is different, then ...
		if ($tmp['tid'] == $a[$i]['tid']
		and $a[$i]['id'] != $tmp['id'])
			{
			$queryDel[] = intval($a[$i]['id']);
			$a[$i]['delete'] = 1;
			break;
			}
		}
	}

# Are there subscriptions, wich can be deleted?
if (!empty($queryDel))
	{
	# if the answer is yes, give the posting ID(s)
	# a proper format for the use in a database query
	if (count($queryDel) > 1)
		{
		$queryDel = join(", ", $queryDel);
		$matches = "IN (".$queryDel.")";
		}
	else
		{
		$matches = "= ".$queryDel[0];
		}

	$queryUnsubscribe = "UPDATE ".$db_settings['forum_table']." SET 
		email_notify = 0
		WHERE user_id = ". intval($_SESSION[$settings['session_prefix'].'user_id']) ."
		AND id ".$matches;
	$result = mysql_query($queryUnsubscribe, $connid);
	if ($result === false) return false;
	}

return $a;
} # End: processSubscriptFilter



/**
 * search thread subscription of the current user and thread
 *
 * @param int $tid
 * @param int $user
 * @return bool
 */
function processSearchThreadSubscriptions($tid, $user) {
global $db_settings, $connid;
$querySTS = "SELECT
user_id,
tid
FROM ".$db_settings['usersubscripts_table']."
WHERE tid = ".intval($tid)."
AND user_id = ".intval($user);
$resultSTS = mysql_query($querySTS, $connid);
if (!$resultSTS) return false;
else $subscriptThread = mysql_fetch_assoc($resultSTS);
$return = !empty($subscriptThread) ? $subscriptThread : false;
return $return;
} # End:  processSearchThreadSubscriptions



/**
 * unifies all possible line breaks into unixoid break
 *
 * @param string $string
 * @param string $to
 * @return string $string
 */
function convertLineBreaks($string, $to = "\n") {
return preg_replace("/\015\012|\015|\012/", $to, $string);
} # End: convertLineBreaks



/**
 * extracts all line breaks from a string
 *
 * @param string $string
 * @return string $string
 */
function bbcodeStripContents($string) {
return preg_replace("/[^\n]/", '', $string);
} # End: bbcodeStripContents



/**
 * returns a string of a link from a given link-bbcode
 *
 * @param string $action
 * @param array $attributes
 * @param string $content
 * @param array $params
 * @param int $node_object
 * @return string
 */
function bbcodeDoURL($action, $attributes, $content, $params, $node_object) {

/**
 * Origin of code inside "if ($action == 'validate')" is jlog 1.1.3
 * see: http://jeenaparadies.net/webdesign/jlog/
 */
if ($action == 'validate')
	{
	if (preg_match('#^(http://|ftp://|news:|mailto:|/)#i', $url)) return true; 
	# Some people just write www.example.org, skipping the http://
	# We're going to be gentle a prefix this link with the protocoll.
	# However, example.org (without www) will not be recognized
	else if (substr($url, 0, 4) == 'www.') return true;
	# all other links will be ignored
	return true;
	}
if (!isset ($attributes['default']))
	{
	return '<a rel="nofollow" href="'.htmlspecialchars($content).'">'.htmlspecialchars(shorten_link($content)).'</a>';
	}
return '<a rel="nofollow" href="'.htmlspecialchars($attributes['default']).'">'.$content.'</a>';
} #End: bbcodeDoURL



/**
 * formats and sends an email
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $from
 * @return bool
 */
function processEmail($to, $subject, $message, $from='') {
global $settings;
$mhs = "\n";
$to = convertLineBreaks($to, '');
$subject = mb_encode_mimeheader(convertLineBreaks($subject, ''), 'UTF-8', "Q", $mhs);
$message = myQuotedPrintableEncode($message);

if ($from == '')
	{
	$headers = "From: ".encodeMailName($settings['forum_name'], $mhs)." <".$settings['forum_email'].">".$mhs;
	}
else
	{
	$headers  = "From: ".convertLineBreaks($from, '').$mhs;
	}

$headers .= "MIME-Version: 1.0".$mhs;
$headers .= "X-Mailer: Php/".phpversion().$mhs;
$headers .= "X-Sender-IP: ".$_SERVER['REMOTE_ADDR'].$mhs;
$headers .= "Content-Type: text/plain; charset=UTF-8; format=flowed".$mhs;
$headers .= "Content-Transfer-Encoding: quoted-printable";

if ($settings['mail_parameter']!='')
	{
	if(@mail($to, $subject, $message, $headers, $settings['mail_parameter']))
		{
		return true;
		}
	else
		{
		return false;
		}
	}
else
	{
	if(@mail($to, $subject, $message, $headers))
		{
		return true;
		}
	else
		{
		return false;
		}
	}
} # End: processEmail



/**
 * puts a name into a formatted string for mail header
 *
 * @param string $name
 * @param string $linefeed
 * @return string $name
 */
function encodeMailName($name, $lf="\r\n") {
$name = str_replace('"', '\\"', $name);
if (preg_match("/(\.|\;|\")/", $name))
	{
	return '"'.mb_encode_mimeheader($name, 'UTF-8', "Q", $lf).'"';
	}
else
	{
	return mb_encode_mimeheader($name, 'UTF-8', "Q", $lf);
	}
} # End: encodeMailName



/**
 * Encode string to quoted-printable.
 * Original written by Andy Prevost http://phpmailer.sourceforge.net
 * and distributed under the Lesser General Public License (LGPL) http://www.gnu.org/copyleft/lesser.html
 *
 * @return string
 */
function myQuotedPrintableEncode($input, $line_max=76, $space_conv = false ) {
$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
$lines = preg_split('/(?:\r\n|\r|\n)/', $input);
$eol = "\n";
$escape = '=';
$output = '';
while (list(, $line) = each($lines))
	{
	$linlen = strlen($line);
	$newline = '';
	for ($i = 0; $i < $linlen; $i++)
		{
		$c = substr($line, $i, 1);
		$dec = ord( $c );
		# convert first point in the line into =2E
		if (($i == 0) && ($dec == 46))
			{ 
			$c = '=2E';
			}
		if ($dec == 32)
			{
			# convert space at eol only
			if ($i==($linlen-1))
				{
				$c = '=20';
				}
			elseif ($space_conv)
				{
				$c = '=20';
				}
			}
		# always encode "\t", which is *not* required
		elseif (($dec == 61) || ($dec < 32) || ($dec > 126))
			{ 
			$h2 = floor($dec/16);
			$h1 = floor($dec%16);
			$c = $escape.$hex[$h2].$hex[$h1];
			}
		# CRLF is not counted
		if ((strlen($newline) + strlen($c)) >= $line_max)
			{
			# soft line break; " =\r\n" is okay
			$output .= $newline.$escape.$eol;
			$newline = '';
			# check if newline first character will be point or not
			if ($dec == 46)
				{
				$c = '=2E';
				}
			}
		$newline .= $c;
		} # end of for
	$output .= $newline.$eol;
	} # end of while
return $output;
} # End: myQuotedPrintableEncode



/**
 * process the standard parameters (category, page, order, descasc)
 * and compares theyre values against if needed
 *
 */
function processStandardParametersGET() {
global $settings;
$checkOrder = array('subject', 'category', 'name', 'time', 'last_answer');
$checkDescAsc = array('DESC', 'ASC');

if (isset($_GET['page']) and intval($_GET['page']) > -1)
	{
	if (!isset($_SESSION[$settings['session_prefix'].'page']))
		{
		$_SESSION[$settings['session_prefix'].'page'] = intval($_GET['page']);
		}
	else if (intval($_GET['page']) != $_SESSION[$settings['session_prefix'].'page'])
		{
		$_SESSION[$settings['session_prefix'].'page'] = intval($_GET['page']);
		}
	}
else
	{
	if (!isset($_SESSION[$settings['session_prefix'].'page']))
		{
		$_SESSION[$settings['session_prefix'].'page'] = 0;
		}
	}
if (isset($_GET['category']) and intval($_GET['category']) > -1)
	{
	if (!isset($_SESSION[$settings['session_prefix'].'category']))
		{
		$_SESSION[$settings['session_prefix'].'category'] = intval($_GET['category']);
		}
	else if (intval($_GET['category']) != $_SESSION[$settings['session_prefix'].'category'])
		{
		$_SESSION[$settings['session_prefix'].'category'] = intval($_GET['category']);
		}
	}
else
	{
	if (!isset($_SESSION[$settings['session_prefix'].'category']))
		{
		$_SESSION[$settings['session_prefix'].'category'] = 0;
		}
	}
if (!empty($_GET['order'])
	and in_array(strtolower($_GET['order']), $checkOrder))
	{
	if (!isset($_SESSION[$settings['session_prefix'].'order']))
		{
		$_SESSION[$settings['session_prefix'].'order'] = strtolower($_GET['order']);
		}
	else if (strtolower($_GET['order']) != $_SESSION[$settings['session_prefix'].'order'])
		{
		$_SESSION[$settings['session_prefix'].'order'] = strtolower($_GET['order']);
		}
	}
else
	{
	if (!isset($_SESSION[$settings['session_prefix'].'order']))
		{
		$_SESSION[$settings['session_prefix'].'order'] = 'last_answer';
		}
	}
if (!empty($_GET['descasc'])
	and in_array(strtoupper($_GET['descasc']), $checkDescAsc))
	{
	if (!isset($_SESSION[$settings['session_prefix'].'descasc']))
		{
		$_SESSION[$settings['session_prefix'].'descasc'] = strtoupper($_GET['descasc']);
		}
	else if (strtoupper($_GET['descasc']) != $_SESSION[$settings['session_prefix'].'descasc'])
		{
		$_SESSION[$settings['session_prefix'].'descasc'] = strtoupper($_GET['descasc']);
		}
	}
else
	{
	if (!isset($_SESSION[$settings['session_prefix'].'descasc']))
		{
		$_SESSION[$settings['session_prefix'].'descasc'] = 'DESC';
		}
	}
} # End: processStandardParametersGET



/**
 * process the view names to the according file names
 *
 * @param string $view
 * @return string $filename
 */
function processView2Filename($view) {
$fname = '';
if ($view == 'thread')
	{
	$fname = 'forum.php';
	}
else
	{
	$fname = $view .'.php';
	}
return $fname;
} #End: processView2Filename



/**
 * counts the users which are online
 *
 * @param integer $user_online_period (in minutes, optional)
 * @return
 */
function processSetUsersOnline($user_online_period = 10) {
global $connid, $db_settings, $settings;

$user_id = isset($_SESSION[$settings['session_prefix'].'user_id']) ? $_SESSION[$settings['session_prefix'].'user_id'] : 0;
$diff = $user_online_period * 60;
$queryDeleteOutdatedRequests = "DELETE FROM ".$db_settings['useronline_table']."
WHERE requestdate < DATE_SUB(NOW(), INTERVAL ". intval($diff) ." SECOND)";

@mysql_query($queryDeleteOutdatedRequests, $connid);

$querySetUserOnline = "INSERT INTO ". $db_settings['useronline_table'] ." SET
ip_addr = INET_ATON('". mysql_real_escape_string($_SERVER["REMOTE_ADDR"]) ."'),
requestdate = NOW(),
user_id = ". intval($user_id) ."
ON DUPLICATE KEY UPDATE
requestdate = NOW()";

@mysql_query($querySetUserOnline, $connid);

} # End: processSetUsersOnline



/**
 * logs out a user
 *
 * @param string $forwardingURL
 * @param string $message
 */
function processLogOutUser($url, $mess) {
global $settings;
$param = session_get_cookie_params();
setcookie("auto_login", "", time() - 86401);
setcookie("user_view", $settings['standard'], time() - 86401);
setcookie("curr_view", $settings['standard'], time() - 86401);
setcookie(session_name(), "", time() - 86401, $param["path"], $param["domain"], $param["secure"], $param["httponly"]);
session_destroy();
header("location: ".$settings['forum_address'].$url);
die($mess);
} # End: processLogOutUser

?>