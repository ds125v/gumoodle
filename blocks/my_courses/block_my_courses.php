<?php
	include_once($CFG->dirroot . '/course/lib.php');


	class block_my_courses extends block_base {
		/* 
			Functions that affect this
			get_user_courses_bycap in /lib/accesslib.php ~line 930
			get_my_courses in /lib/datalib.php ~line 810
			several DB functions in /lib/dmllib.php
			elementToggleHide in /lib/javascrpt-static.js
		*/
		/*
			Standard initialization function
		*/
		function init() {
			$this->title = get_string('blockname', 'block_my_courses');
			$this->version = 2008090300;	// YYYYMMDD00
		}
	
		function get_content() {
		        global $THEME, $CFG, $USER;

			if ($this->content !== NULL) {
				return $this->content;
			}
			
			$this->content = new stdClass;
		        $this->content->footer = '';
			
			$icon  = "<img src=\"$CFG->pixpath/i/course.gif\"".
				 " class=\"icon\" alt=\"".get_string("coursecategory")."\" />";
			
			$adminseesall = true;
			if (isset($CFG->block_course_list_adminview)) {
			   if ( $CFG->block_course_list_adminview == 'own'){
			       $adminseesall = false;
			   }
			}
			
			$cat = $catname = null;
			$bFirstCat = true;
			// Get the name of the "first category".  It's the category that shows first in the list that is visible and has courses in it.
			$sFirstCategory = $this->getCurrentCategory();

			if (!empty($USER->id) and
					!(has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM)) and $adminseesall) and
					!isguestuser($USER->id)) {
				$this->content->text = '
					<script src="' . $CFG->wwwroot . '/lib/yui/yahoo/yahoo-min.js"></script>
					<script src="' . $CFG->wwwroot . '/lib/yui/event/event-min.js"></script>
					<script src="' . $CFG->wwwroot . '/lib/yui/connection/connection-min.js"></script>
					<script>
					function block_my_courses_toggleCategoryStatus(eItem, categoryName) {
						sElementName = \'mycourse_\' + categoryName;
						
						if (document.getElementById(sElementName).className.indexOf(\'hidden\') == -1) {
							sStatus = 1;
						} else {
							sStatus = 0;
						}
					
						elementToggleHide(eItem, false, function(el) {return document.getElementById(sElementName); }, \'Show Category\', \'Hide Category\');
						sUrl = "' . $CFG->wwwroot . '/blocks/my_courses/ajax_SaveStatus.php";
						sVars = "Cat=" + categoryName + "&UID=' . $USER->id . '&Status=" + sStatus;
						var transaction = YAHOO.util.Connect.asyncRequest(\'POST\', sUrl, null, sVars); 
						
						return false;
					}
					</script>';
				$this->content->text .= '<ul class="list">';
				
				$cats = get_records('course_categories');
				$arCats= array();
				foreach ($cats as $cat) {
					$arCats[$cat->id] = $cat->name;
				}
				
				if ( $courses = get_my_courses($USER->id, 'category DESC, fullname ASC') ) { //mu: Get courses sorted by category
					$courses = $this->prepCourseCategories($courses);
					foreach ($courses as $course) {
						if ($course['id'] == SITEID) {
							continue;
						}
						if ($catname == $course['categoryname']) { //mu: If I'm inside the same category, don't print category's name
							$linkcss = ($course['visible'] ? "" : " class=\"dimmed\" ");
							$this->content->text .='<li>' . $icon . '<a ' . $linkcss . ' title="' . $course['shortname'] . '" ' .
									'href="' . $CFG->wwwroot . '/course/view.php?id=' .$course['id'] . '">' . $course['fullname'] . '</a></li>';
							continue;
						} else { // New category, so find the users preference on if this category should be collapsed
							$nCollapseSetting = get_field('block_my_courses', 'collapsed', 'category_name', $course['categoryname'], 'userid', $USER->id);
							if ( strlen($nCollapseSetting) == 0 ) {
								$nCollapseSetting = ($sFirstCategory == $course['categoryname'] ? 0 : 1);
							}
							$bCollapseCatetoryCourses = ($nCollapseSetting ==1 ? true : false);
						}
						$cat = $course['category'];
						$catname = $course['categoryname'];
						if ( $bFirstCat ) {
							$bFirstCat = false;
						} else {
							$this->content->text .= '</ul>';
						}
						$this->content->text .= '<li>';
						$this->content->text .= '<input id="togglehide_mycourse ' . $catname . '" class="hide-show-image" type="image" title="Hide Category" alt="Hide Category" 
							onclick="return block_my_courses_toggleCategoryStatus(this, \'' . $catname . '\');" src="' . $CFG->pixpath.'/t/switch_' . ($bCollapseCatetoryCourses ? 'plus' : 'minus') . '.gif"/>';
						$this->content->text .= ' <strong>'.$catname."</strong></li>";
						$this->content->text .= '<ul id="mycourse_' . $catname . '" class="' . ($bCollapseCatetoryCourses ? 'hidden' : '') . '">';
						$linkcss = $course['visible'] ? "" : " class=\"dimmed\" ";
						$this->content->text .='<li>' . $icon . '<a $linkcss title="' . $course['shortname'] . '" '.
								'href=' . $CFG->wwwroot . '/course/view.php?id=' . $course['id'] . '">' . $course['fullname'] . '</a></li>';
		
		
					}
					$this->content->text .= '</ul></ul>';
					$this->title = get_string('mycourses');
					$this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">".get_string("fulllistofcourses")."</a>...";
					if (strlen($this->content->text) > 0) { // make sure we don't return an empty list
						return $this->content;
					}
				}
			}

			$this->content->footer = '';	// blank footer
			
			return $this->content;
		}
		
		protected function getCurrentCategory() {
			return get_field_sql("
				SELECT	c1.name
				FROM	mdl_course_categories c1, mdl_course_categories p1
				WHERE	c1.coursecount > 0 
					AND	c1.visible = 1 AND p1.visible = 1
					AND	p1.id = c1.parent
				ORDER BY p1.depth, p1.sortorder, c1.sortorder");
		}
		
		protected function prepCourseCategories($arCourses) {
			$cats = get_records('course_categories');
			$arCats= array();
			foreach ($cats as $cat) {
				$arCats[$cat->id] = array($cat->name, $cat->depth, $cat->sortorder);
			}
	
			foreach ($arCourses as $course) {
				$arListing['id'] = $course->id;
				$arListing['visible'] = $course->visible;
				$arListing['shortname'] = $course->shortname;
				$arListing['fullname'] = $course->fullname;
				$arListing['category'] = $course->category;
				$arListing['categoryname'] = $arCats[$course->category][0];
				$arListing['categorydepth'] = $arCats[$course->category][1];
				$arListing['categorysortorder'] = $arCats[$course->category][2];
				
				$arListings[] = $arListing;
			}
		
			usort($arListings, array(&$this, "listingCmp"));
		
			return $arListings;
		}
	
		protected function listingCmp($a, $b) {
			if ( strcmp($a["categorydepth"], $b["categorydepth"]) == 0 ) {
				if ( strcmp($a["categorysortorder"], $b["categorysortorder"]) == 0 ) {
					if ( strcmp($a["categoryname"], $b["categoryname"]) == 0 ) {
						if ( strcmp($a["shortname"], $b["shortname"]) == 0 ) {
							$nCompare = 0;
						} else {
							$nCompare = strcmp($a["shortname"], $b["shortname"]);
						}
					} else {
						$nCompare = strcmp($a["categoryname"], $b["categoryname"]);
					}
				} else {
					$nCompare = strcmp($a["categorysortorder"], $b["categorysortorder"]);
				}
			} else {
				$nCompare = strcmp($a["categorydepth"], $b["categorydepth"]);
			}
			
			return $nCompare;
		}	
	
	}
?>