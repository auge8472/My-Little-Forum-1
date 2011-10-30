function bbcode(v)
 {
 // for IE
 if (document.selection)
   {
    var str = document.selection.createRange().text;
    document.forms['entryform'].elements['text'].focus();
    var sel = document.selection.createRange();
    sel.text = "[" + v + "]" + str + "[/" + v + "]";
    return;
   }
  // for Mozilla
  else if ((typeof document.forms['entryform'].elements['text'].selectionStart) != 'undefined')
   {
    var txtarea = document.forms['entryform'].elements['text'];
    var selLength = txtarea.textLength;
    var selStart = txtarea.selectionStart;
    var selEnd = txtarea.selectionEnd;
    var oldScrollTop = txtarea.scrollTop;
    var s1 = (txtarea.value).substring(0,selStart);
    var s2 = (txtarea.value).substring(selStart, selEnd);
    var s3 = (txtarea.value).substring(selEnd, selLength);
    txtarea.value = s1 + '[' + v + ']' + s2 + '[/' + v + ']' + s3;
    txtarea.selectionStart = s1.length;
    txtarea.selectionEnd = s1.length + 5 + s2.length + v.length * 2;
    txtarea.scrollTop = oldScrollTop;
    txtarea.focus();
    return;
   }
  else insert('[' + v + '][/' + v + '] ');
 }

/**
 * insert BB-Codes without text content
 *
 * @param string code
 * @param string element-ID
 */
function insertIt(code, id) {

$(id).focus();

if ($(id).createTextRange)
	{
	document.selection.createRange().duplicate().text = code;
	}
// for Mozilla
else if ((typeof $(id).selectionStart) != 'undefined')
	{
	var selEnd = $(id).selectionEnd;
	var txtLen = $(id).value.length;
	var txtbefore = $(id).value.substring(0,selEnd);
	var txtafter =  $(id).value.substring(selEnd, txtLen);
	var oldScrollTop = $(id).scrollTop;
	$(id).value = txtbefore + code + txtafter;
	$(id).selectionStart = txtbefore.length + code.length;
	$(id).selectionEnd = txtbefore.length + code.length;
	$(id).scrollTop = oldScrollTop;
	$(id).focus();
   }
  else
   {
	$(id).value += code;
	$(id).focus();
   }
}

function insert_link(form,field,link_text,link_target)
 {
 // for IE
 if (document.selection)
   {
    var str = document.selection.createRange().text;
    document.forms[form].elements[field].focus();
    var sel = document.selection.createRange();
    var insert_link = prompt(link_target,'http://');
    if(sel.text=='' && insert_link!='' && insert_link!=null) str = prompt(link_text,'');

    if(insert_link && str!=null)
     {
      if(str!='')
       {
        sel.text = "[link=" + insert_link + "]" + str + "[/link]";
       }
      else
       {
        sel.text = "[link]" + insert_link + "[/link]";
       }
     }
    return;
   }
  // for Mozilla
  else if ((typeof document.forms[form].elements[field].selectionStart) != 'undefined')
   {
    var txtarea = document.forms[form].elements[field];
    var selLength = txtarea.textLength;
    var selStart = txtarea.selectionStart;
    var selEnd = txtarea.selectionEnd;
    var oldScrollTop = txtarea.scrollTop;
    var s1 = (txtarea.value).substring(0,selStart);
    var s2 = (txtarea.value).substring(selStart, selEnd);
    var s3 = (txtarea.value).substring(selEnd, selLength);

    var insert_link = prompt(link_target,'http://');
    if(selEnd-selStart==0 && insert_link!='' && insert_link!=null) s2 = prompt(link_text,'');
    if(insert_link && s2!=null)
     {
      if(s2!='')
       {
        txtarea.value = s1 + '[link=' + insert_link + ']' + s2 + '[/link]' + s3;
        var codelength = 14 + insert_link.length + s2.length;
       }
      else
       {
        txtarea.value = s1 + '[link]' + insert_link + '[/link]' + s3;
        var codelength = 13 + insert_link.length;
       }
      txtarea.selectionStart = s1.length;
      txtarea.selectionEnd = s1.length + codelength;
      txtarea.scrollTop = oldScrollTop;
      txtarea.focus();
      return;
     }
   }
  else insert('[link=http://www.domain.tld/]Link[/link]');
 }
 
/**
 * This function inserts the bb-code buttons
 * for the textarea (#text) into the form.
 */

function bbCodeButtons(Buttons) {
var o = Buttons.length;
var x = o - 1;
var output = $A();
var j = 0;

if (Buttons && o>0)
	{
	for (var i=0;i<o;i++)
		{
		j = i + 1;
		output[j] = "<input type=\"button\" value=\""+ Buttons[i].get('text') +"\" title=\"" + Buttons[i].get('titel') + "\" class=\"bb-button\" onclick=\"bbcode('"+ Buttons[i].get('value') +"')\"><br />";
		}
	j = j + 1;
	}
$('buttonspace').update(output.join("\n"));
}

/**
 * This function inserts the smilies buttons
 * for the textarea (#text) into the form.
 */
function auge_smilies_buttons(Smilies) {
var o = Smilies.length;
var x = o - 1;
var output = $A();
var j = 0;

if (Smilies && o>0)
	{
	for (var i=0; i<6; i++)
		{
		output[i] = "<button name=\"smiley\" type=\"button\" value=\""+ Smilies[i].get('value') +"\" title=\""+ Smilies[i].get('title') + Smilies[i].get('value') +"\" onclick=\"insertIt(this.value,'text');\"><img src=\"img/smilies/"+ Smilies[i].get('url') +"\" alt=\""+ Smilies[i].get('value') +"\"></button>";
		if (i % 2 == 1)
			{
			output[i] = output[i] +"<br />";
			}
		}
	if (o > i)
		{
		j = o - 1;
		output[j] = "<span class=\"js-handler\" title=\""+ Smilies[j].get('title') +"\" onclick=\"moreSmilies(auge_smilies)\">"+ Smilies[j].get('value') +"</span>";
		if (i % 2 == 1)
			{
			output[j] = "<br />"+ output[j];
			}
		}
	}
Element.insert($('buttonspace'), {'bottom': "\n<br />"+ output.join("")});
}



/**
 * delete text of an form element with given ID
 * @param string ID
 */
function clearText(a) {
$(a).focus();
$(a).value = "";
}

function moreSmilies(Smilies) {

if ($('add-smilies'))
	{
	$('add-smilies').remove();
	}
else
	{
	var o = Smilies.size() - 1;
	var buttons = $A();

	var div = new Element('div');
	div.writeAttribute('id', 'add-smilies');
	div.writeAttribute('class', 'additional-menu');

	for (i = 6; i < o; i++)
		{
		j = i - 6;
		buttons[j] = "<button name=\"smiley\" type=\"button\" value=\""+ Smilies[i].get('value') +"\" title=\""+ Smilies[i].get('title') + Smilies[i].get('value') +"\" onclick=\"insertIt(this.value,'text'); destroyElement('add-smilies');\"><img src=\"img/smilies/"+ Smilies[i].get('url') +"\" alt=\""+ Smilies[i].get('value') +"\"></button>";
		if (i % 2 == 1)
			{
			buttons[j] = buttons[j] +"<br />";
			}
//		buttons[j] = SmileyButton(Smilies[i]);
		}
	Element.insert(div, buttons.join(''));
//	alert(div.inspect());

	$('buttonspace').insert(div);
	}
}

function destroyElement(name) {
$(name).remove();
}

function upload()
 {
  var popurl="upload.php";
  winpops=window.open(popurl,"","width=340,height=340,scrollbars,resizable");
 }

function delete_cookie()
 {
  var popurl="delete_cookie.php";
  winpops=window.open(popurl,"","width=200,height=150,scrollbars,resizable");
  return false;
 }

var img1 = new Image();
img1.src ="img/link_mo.png";
var img2 = new Image();
img2.src ="img/next_mo.png";
var img3 = new Image();
img3.src ="img/prev_mo.png";
var img4 = new Image();
img4.src ="img/update_mo.png";
