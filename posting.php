<?php
###############################################################################
# my little forum 1                                                            #
# Copyright (C) 2013 Heiko August                                             #
# http://www.auge8472.de/                                                     #
#                                                                             #
# This program is free software; you can redistribute it and/or               #
# modify it under the terms of the GNU General Public License                 #
# as published by the Free Software Foundation; either version 2              #
# of the License, or (at your option) any later version.                      #
#                                                                             #
# This program is distributed in the hope that it will be useful,             #
# but WITHOUT ANY WARRANTY; without even the implied warranty of              #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                #
# GNU General Public License for more details.                                #
#                                                                             #
# You should have received a copy of the GNU General Public License           #
# along with this program; if not, write to the Free Software                 #
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. #
###############################################################################

include_once("inc.php");
include_once("functions/include.prepare.php");

# generate captcha if captcha is on
# and a not logged user wants to post
if (empty($_SESSION[$settings['session_prefix'].'user_id'])
&& $settings['captcha_posting'] == 1)
	{
	require('captcha/captcha.php');
	$captcha = new captcha();
	}
