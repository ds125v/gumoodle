/**
 * selection.js - Defines functions used by email.html in quickmail package
 *
 * @author: Bibek Bhattarai and Wen Hao Chuang
 * 2007/03/29 09:00:00 
 * @package quickmailv2
 **/

//Global variables used for pointing to members and mailto selection list
var selectedList;
var availableList;

/** This function initializes selectedlist and availablelist with members and mailto selection lists respectively.
  * This function is called everytime html form is reloaded 
  **/
function createListObjects(){
	//availablelist points to members list, that holds list of users (Teachers and Students) present in the class
	availableList = document.getElementById("members");
	//Selectedlist points to mailto list, that holds list of users (Teachers and Students) to which email has to be sent
	selectedList = document.getElementById("mail_to");
}

/** This function is used to remove user from selected list and add it back to available list */
function remove_user(){
	//for all items in selected list
	for(var i=selectedList.length-1; i>=0; i--){
		//if selected, append the user to available list. 
		//Append will automatically remove it from selected list
		if(selectedList.options[i].selected == true){
			availableList.appendChild(selectedList.options[i]);
		}		
	}
	//call selectnone function to remove selection/highlight after move
	selectNone(selectedList, availableList);	
}

/** This function is used to add user to selectedlist and remove it from available list */
function add_user(){
	//for all items in available list
	for(var i=availableList.length-1; i>=0; i--){
		//if selected, append the user to selected list.
		//Append will automatically remove it from available list
		if(availableList.options[i].selected == true){
			selectedList.appendChild(availableList.options[i]);
		}
	}
	//call selectnone function to remove selection/hightlight after move
	selectNone(selectedList, availableList);	
}

/** This function is used to remove all users from selectionlist and add them to available list */
function removeAll(){
	var len = selectedList.length-1;
	//Select all users in selected list and append them to available list
	for(i=len; i>=0; i--){
		availableList.appendChild(selectedList.options[i]);	
	}
	//De-select all users after move
	selectNone(selectedList, availableList);	
}

/** This function is used to add all users from availablelist to selectionlist*/
function addAll(){
	var len = availableList.length - 1;
	//Select all users from availablelist and append them to selectedlist
	for(i=len; i>=0; i--){
		selectedList.appendChild(availableList.options[i]);
	}
	//De-select all users after move
	selectNone(selectedList, availableList);
}

/** This function is used to deselect users in availablelist, selectedlist after move */
function selectNone(list1, list2){
	//Set all elements on list1 to selected false
	for(var i=list1.length-1; i>=0 ; i--){
		list1.options[i].selected = false;		
	}
	//Set all elements on list2 to selected false
	for(var i=list2.length-1; i>=0; i--){
		list2.options[i].selected = false;
	}
}

/** This function is used to construct the list of user to whom email has to be sent */
function updateList(){
	var ids = '';
	// add user id of all elements in seleted list to string email as comma seperated value
	for(var i=selectedList.length-1; i>=0 ; i--){
		var val = selectedList.options[i].value;
		var valarr = val.split(" ");
		val = valarr[0];
		ids = ids+val;
		//do not add "," after last element
		if(i!=0){
			ids = ids+',';		
		}
	}
	//set hidden input value mailuser as email
	document.getElementById("mailuser").value = ids ;	
}

/** This function is used to construct the list of user to whom email has to be sent.
	This function builds list of emails to be sent through external client*/
function mail_to_ext_client(){
	var emails = '';
	// add user id of all elements in seleted list to string email as comma seperated value
	for(var i=selectedList.length-1; i>=0 ; i--){
		var val = selectedList.options[i].value;
		var valarr = val.split(" ");
		val = valarr[1];
		emails = emails+val;
		//do not add "," after last element
		if(i!=0){
			emails = emails+',';		
		}
	}
	from_email = document.getElementById("fromemail").value;
	//Redirects to external client with list of emails as bcc recievers
	location.href='mailto:'+from_email+'?bcc='+emails;
}
