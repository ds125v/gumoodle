if (getcookie('turnitintool_choice_user')==null) {
	var choiceUserString = '';
	var choiceCountString = '';
} else {
	var choiceUserString = getcookie('turnitintool_choice_user');
	var choiceCountString = getcookie('turnitintool_choice_count');
}
var sid;
var openurl;
function updateSubForm(submissionArray,stringsArray,thisForm,genspeed) {
	// submissionArray(userid,partid,title)
	// stringsArray('addsubmission','resubmit','resubmission','resubmissionnotenabled');
	var userid = thisForm.userid.value;
	var partid = thisForm.submissionpart.value;
	if (genspeed===1) {
		thisForm.submissiontitle.value='';
		document.getElementById('submissionnotice').innerHTML='';
	} else {
		thisForm.submissiontitle.value='';
		thisForm.submissiontitle.readOnly=false;
		thisForm.submissiontitle.style.color='inherit';
		thisForm.submitbutton.value=stringsArray[0];
		thisForm.submitbutton.disabled=false;
		if (thisForm.submissiontype.value==1) {
			thisForm.submissionfile.disabled=false;
		} else {
			thisForm.submissiontext.disabled=false;
			thisForm.submissiontext.readOnly=false;
		}
	}
	var userFound=false;
	for (i=0;i<submissionArray.length;i++) {
		submission = submissionArray[i];
		if (genspeed===1) {
			if (submission[0]===userid && submission[1]===partid && !userFound) {
				userFound=true;
				thisForm.submissiontitle.value=submission[2];
				document.getElementById('submissionnotice').innerHTML='<i>('+stringsArray[2]+')</i>';
			}
		} else {
			if (submission[0]===userid && submission[1]===partid && !userFound) {
				userFound=true;
				thisForm.submissiontitle.value=submission[2];
				thisForm.submissiontitle.readOnly=true;
				thisForm.submissiontitle.style.color='#666666';
				thisForm.submitbutton.value=stringsArray[3];
				thisForm.submitbutton.disabled=true;
				if (thisForm.submissiontype.value==1) {
					thisForm.submissionfile.disabled=true;
				} else {
					thisForm.submissiontext.disabled=true;
					thisForm.submissiontext.readOnly=true;
				}
			}
		}
	}
}
function screenOpen(url,sidin,autoupdate,warn) {
	if (warn && !confirm(warn)) {
		return false;
	} else {
		window.sid = sidin;
		openurl=url;
		newwindow=window.open(url);
		newwindow.focus();
		unloadFunction();
	}
}
function turnitintool_countchars(thisarea,outputblock,maxchars,errormsg) {
	var outblock = document.getElementById(outputblock);
	outblock.innerHTML=thisarea.value.length+'/'+maxchars;
	if (thisarea.value.length>maxchars) {
		alert(errormsg);
	}
}
function unloadFunction() {
	if (window.newwindow.closed) {
		window.location.href=location.href+'&update='+sid;
	} else {
		setTimeout(unloadFunction,500);
	}
}
function turnitintool_jumptopage(url) {
	window.location.href=url;
}
function roundNumber(number,places) {
	var output = Math.round(number*Math.pow(10,places))/Math.pow(10,places);
	return output;
}
function turnitintool_pauseFor(secs) {
	time=secs*100;
	settimeout(turnitintool_pauseFor,time);
}
function turnitintool_editpart(idstring,thisid) {
	strings=idstring.split(":");
	for (i=0;i<strings.length;i++) {
		if (thisid!=strings[i]) {
			document.getElementById('partname_'+strings[i]).style.display='none';
			document.getElementById('dtstart_'+strings[i]).style.display='none';
			document.getElementById('dtdue_'+strings[i]).style.display='none';
			document.getElementById('dtpost_'+strings[i]).style.display='none';
			document.getElementById('maxmarks_'+strings[i]).style.display='none';
			document.getElementById('tick_'+strings[i]).style.display='none';
			
			document.getElementById('partnametext_'+strings[i]).style.display='block';
			document.getElementById('dtstarttext_'+strings[i]).style.display='block';
			document.getElementById('dtduetext_'+strings[i]).style.display='block';
			document.getElementById('dtposttext_'+strings[i]).style.display='block';
			document.getElementById('maxmarkstext_'+strings[i]).style.display='block';
			document.getElementById('ticktext_'+strings[i]).style.display='none';
		} else {
			document.getElementById('partname_'+strings[i]).style.display='block';
			document.getElementById('dtstart_'+strings[i]).style.display='block';
			document.getElementById('dtdue_'+strings[i]).style.display='block';
			document.getElementById('dtpost_'+strings[i]).style.display='block';
			document.getElementById('maxmarks_'+strings[i]).style.display='block';
			document.getElementById('tick_'+strings[i]).style.display='block';
			
			document.getElementById('partnametext_'+strings[i]).style.display='none';
			document.getElementById('dtstarttext_'+strings[i]).style.display='none';
			document.getElementById('dtduetext_'+strings[i]).style.display='none';
			document.getElementById('dtposttext_'+strings[i]).style.display='none';
			document.getElementById('maxmarkstext_'+strings[i]).style.display='none';
			document.getElementById('ticktext_'+strings[i]).style.display='none';
		}
	}
}
function turnitintool_addvalues(thisid,values) {
	document.getElementById('partnametext_'+thisid).innerHTML=values['partnametext'];
	document.getElementById('dtstarttext_'+thisid).innerHTML=values['dtstarttext'];
	document.getElementById('dtduetext_'+thisid).innerHTML=values['dtduetext'];
	document.getElementById('dtposttext_'+thisid).innerHTML=values['dtposttext'];
	document.getElementById('maxmarkstext_'+thisid).innerHTML=values['maxmarkstext'];
	document.getElementById('ticktext_'+thisid).innerHTML=values['ticktext'];
	document.getElementById('partname_'+thisid).style.display='none';
	document.getElementById('dtstart_'+thisid).style.display='none';
	document.getElementById('dtdue_'+thisid).style.display='none';
	document.getElementById('dtpost_'+thisid).style.display='none';
	document.getElementById('maxmarks_'+thisid).style.display='none';
	document.getElementById('tick_'+thisid).style.display='none';
}
function dopercents(thisform,totalnum) {
	var totalweights=0;
	for (i=1;i<=totalnum;i++) {
		thisvalue=document.getElementById('weightinput_'+i).value;
		if (isNaN(thisvalue)) {
			thisvalue=0;
		}
		totalweights=parseInt(totalweights)+parseInt(thisvalue);
	}
	for (i=1;i<=totalnum;i++) {
		thisvalue=document.getElementById('weightinput_'+i).value;
		if (isNaN(thisvalue)) {
			thisvalue=0;
		}
		thispercent=roundNumber(thisvalue/totalweights*100,2);
		output='<i style="color: #888888;">('+thispercent+'%)*</i>';
		document.getElementById('weight_'+i).innerHTML=output;
	}
}
function assignmentcheck(turnitintoolid) {
	if (turnitintoolid==getcookie('turnitintoolid')) {
		setcookie('turnitintoolid',turnitintoolid,null,'/','','');
	} else {
		togglehideall();
		setcookie('turnitintoolid',turnitintoolid,null,'/','','');
	}
}
function setuserchoice() {
	//alert(document.cookie);
	choiceUserArray=choiceUserString.split('_');
	choiceCountArray=choiceCountString.split('_');
	if (choiceUserArray.length==0 && choiceUserString.length>0) {
		choiceUserArray=new Array(choiceUserString);
		choiceCountArray=new Array(choiceCountString);
	}
	for (i=0;i<choiceUserArray.length;i++) {
		for (n=1;n<=choiceCountArray[i];n++) {
			try {
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_1").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_2").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_3").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_4").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_5").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_6").style.display="block";
				document.getElementById("toggle_"+choiceUserArray[i]+"_"+n+"_7").style.display="block";
				document.getElementById('userblock_'+choiceUserArray[i]).src="images/minus.gif";
			} catch(err) {
				// Nothing	
			}
		}
	}
}
function toggleview(userid,count,force) {
	if (force==null) {
		for (i=1;i<=count;i++) {
			blockDisplay1=document.getElementById("toggle_"+userid+"_"+i+"_1");
			blockDisplay2=document.getElementById("toggle_"+userid+"_"+i+"_2");
			blockDisplay3=document.getElementById("toggle_"+userid+"_"+i+"_3");
			blockDisplay4=document.getElementById("toggle_"+userid+"_"+i+"_4");
			blockDisplay5=document.getElementById("toggle_"+userid+"_"+i+"_5");
			blockDisplay6=document.getElementById("toggle_"+userid+"_"+i+"_6");
			blockDisplay7=document.getElementById("toggle_"+userid+"_"+i+"_7");
			if (blockDisplay1.style.display=="block") {
				blockDisplay1.style.display="none";
				blockDisplay2.style.display="none";
				blockDisplay3.style.display="none";
				blockDisplay4.style.display="none";
				blockDisplay5.style.display="none";
				blockDisplay6.style.display="none";
				blockDisplay7.style.display="none";
				document.getElementById('userblock_'+userid).src="images/plus.gif";
				if (i==count) {
					removefromchoice(userid,choiceUserString,choiceCountString);
				}
			} else {
				blockDisplay1.style.display="block";
				blockDisplay2.style.display="block";
				blockDisplay3.style.display="block";
				blockDisplay4.style.display="block";
				blockDisplay5.style.display="block";
				blockDisplay6.style.display="block";
				blockDisplay7.style.display="block";
				document.getElementById('userblock_'+userid).src="images/minus.gif";
				if (i==count) {
					if (choiceUserString=='') {
						sep='';
					} else {
						sep='_';	
					}
					choiceUserString += sep+userid;
					choiceCountString += sep+i;
				}
			}
		}
	} else {
		for (i=1;i<=count;i++) {
			document.getElementById("toggle_"+userid+"_"+i+"_1").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_2").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_3").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_4").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_5").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_6").style.display=force;
			document.getElementById("toggle_"+userid+"_"+i+"_7").style.display=force;
			if (force=='none') {
				document.getElementById('userblock_'+userid).src="images/plus.gif";
			} else {
				document.getElementById('userblock_'+userid).src="images/minus.gif";
			}
			if (force=="none" && i==count) {
				removefromchoice(userid,choiceUserString,choiceCountString);
			} else if (i==count) {
				if (choiceUserString=='') {
					sep='';
				} else {
					sep='_';	
				}
				choiceUserString += sep+userid;
				choiceCountString += sep+i;
			}
		}
	}
	setcookie('turnitintool_choice_user',choiceUserString,null,'/','','');
	setcookie('turnitintool_choice_count',choiceCountString,null,'/','','');
}
function toggleshowall() {
	for (i=0;i<users.length;i++) {
		for (n=1;n<=count[i];n++) {
			document.getElementById("toggle_"+users[i]+"_"+n+"_1").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_2").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_3").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_4").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_5").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_6").style.display="block";
			document.getElementById("toggle_"+users[i]+"_"+n+"_7").style.display="block";
			document.getElementById('userblock_'+users[i]).src="images/minus.gif";
		}
	}
	choiceUserString=users.join('_');
	choiceCountString=count.join('_');
	setcookie('turnitintool_choice_user',choiceUserString,null,'/','','');
	setcookie('turnitintool_choice_count',choiceCountString,null,'/','','');
}
function togglehideall(docookie) {
	for (i=0;i<users.length;i++) {
		for (n=1;n<=count[i];n++) {
			document.getElementById("toggle_"+users[i]+"_"+n+"_1").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_2").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_3").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_4").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_5").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_6").style.display="none";
			document.getElementById("toggle_"+users[i]+"_"+n+"_7").style.display="none";
			document.getElementById('userblock_'+users[i]).src="images/plus.gif";
		}
	}
	if (docookie==null) {
		choiceUserString = '';
		choiceCountString = '';
		setcookie('turnitintool_choice_user',choiceUserString,null,'/','','');
		setcookie('turnitintool_choice_count',choiceCountString,null,'/','','');
	}
}
function removefromchoice(findvar,string1,string2) {
	var keymatch = null;
	var newarray1 = new Array();
	var newarray2 = new Array();
	array1=string1.split('_');
	array2=string2.split('_');
	for (j=0;j<array1.length;j++) {
		// remove the find var from array1
		if (array1[j]!=findvar) {
			newarray1.push(array1[j]);
		} else {
			keymatch = j;
		}
	}
	for (j=0;j<array2.length;j++) {
		// Remove the same key from array2
		if (j!=keymatch) {
			newarray2.push(array2[j]);
		}
	}
	choiceUserString = newarray1.join('_');
	choiceCountString = newarray2.join('_');
}
function setcookie( name, value, expires, path, domain, secure ) {
	var today = new Date();
	today.setTime( today.getTime() );

	if ( expires ) {
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );
	
	document.cookie = name + "=" +escape( value ) +
	( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) + 
	( ( path ) ? ";path=" + path : "" ) + 
	( ( domain ) ? ";domain=" + domain : "" ) +
	( ( secure ) ? ";secure" : "" );
}
function getcookie( check_name ) {
	var a_all_cookies = document.cookie.split( ';' );
	var a_temp_cookie = '';
	var cookie_name = '';
	var cookie_value = '';
	var b_cookie_found = false;
	
	for ( i = 0; i < a_all_cookies.length; i++ ) {
		a_temp_cookie = a_all_cookies[i].split( '=' );
		cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
		if ( cookie_name == check_name ) {
			b_cookie_found = true;
			if ( a_temp_cookie.length > 1 ) {
				cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
			}
			return cookie_value;
			break;
		}
		a_temp_cookie = null;
		cookie_name = '';
	}
	if ( !b_cookie_found ) {
		return null;
	}
}
function deletecookie( name, path, domain ) {
	if ( getcookie( name ) ) document.cookie = name + "=" +
	( ( path ) ? ";path=" + path : "") +
	( ( domain ) ? ";domain=" + domain : "" ) +
	";expires=Thu, 01-Jan-1970 00:00:01 GMT";
}
function editgrade(submission,textcolor,background,warn) {
	if (warn && !confirm(warn)) {
		return false;
	} else {
		var edititem=document.getElementById("edit_"+submission);
		var gradeitem=document.getElementById("grade_"+submission);
		var tickitem=document.getElementById("tick_"+submission);
		var hideshowitem=document.getElementById("hideshow_"+submission);
		gradeitem.style.backgroundColor=background;
		gradeitem.style.color=textcolor;
		gradeitem.style.border='1px inset';
		gradeitem.readOnly=false;
		edititem.style.display='none';
		tickitem.style.display='inline';
		hideshowitem.style.display='inline';
	}
}
function viewgrade(submission,textcolor,background,warn) {
	var edititem=document.getElementById("edit_"+submission);
	var gradeitem=document.getElementById("grade_"+submission);
	var tickitem=document.getElementById("tick_"+submission);
	var hideshowitem=document.getElementById("hideshow_"+submission);
	gradeitem.style.backgroundColor=background;
	gradeitem.style.color=textcolor;
	gradeitem.style.border='none';
	gradeitem.readOnly=true;
	tickitem.style.display='none';
	var addwarn='';
	if (warn) {
		addwarn = ',\''+warn+'\'';
	}
	document.write('<a href="javascript:;" onclick="editgrade(\''+submission+'\',\'black\',\'white\''+addwarn+');" id="edit_'+submission+'"><img src="images/editicon.gif" class="tiiicons" /></a>');
	if (gradeitem.value=='') {
		hideshowitem.style.display='none';
	}
}