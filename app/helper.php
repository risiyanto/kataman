<?php


class Helper extends \Prefab {
	function badwords($val) {
		$bad_words = array("badword","jerk","damn");
		$replacement_words = array("@#$@#", "j&*%", "da*@"); 
		return str_ireplace($bad_words, $replacement_words, $val);
	}
}
