<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The Elegance theme is built upon  Bootstrapbase 3 (non-core).
 *
 * @package    theme
 * @subpackage theme_elegance
 * @author     Julian (@moodleman) Ridden
 * @author     Based on code originally written by G J Bernard, Mary Evans, Bas Brands, Stuart Lamour and David Scotson.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 $quicklinksnum = $PAGE->theme->settings->quicklinksnumber;
 
 ?>
 
<div id="heading">
	<h1><?php echo $PAGE->theme->settings->quicklinkstitle ?></h1>
</div>

<div class="row" id="quicklinks">
	
	<?php foreach (range(1, $quicklinksnum) as $quicklinksnumber) { 
		$qli = "quicklinkicon$quicklinksnumber";
		$qlt = "quicklink1buttontext$quicklinksnumber";
		?>
		<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6" id="quicklink">
			<a href="http://2014.imoot.org/mod/page/view.php?id=19">
				<div id="circle-highlight">
					<?php if (!empty($PAGE->theme->settings->$qli)) {
						echo '<i class="fa fa-'.$PAGE->theme->settings->$qli.'"></i>';
					} else {
						echo '<i class="fa fa-check"></i>';
					} ?>
				</div>
				<p class="btn">
					<?php if (!empty($PAGE->theme->settings->$qlt)) {
						echo $PAGE->theme->settings->$qlt;
					} else {
						echo 'Click here';
					}
					?></p>
			</a>
		</div>
	<?php } ?>
	
</div>