function bbcode(v)
 {
 if (document.selection) // for IE
   {
    var str = document.selection.createRange().text;
    document.forms['entryform'].elements['text'].focus();
    var sel = document.selection.createRange();
    sel.text = "[" + v + "]" + str + "[/" + v + "]";
    return;
   }
  else if ((typeof document.forms['entryform'].elements['text'].selectionStart) != 'undefined') // for Mozilla
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

function insert(what)
 {
  if (document.forms['entryform'].elements['text'].createTextRange)
   {
    document.forms['entryform'].elements['text'].focus();
    document.selection.createRange().duplicate().text = what;
   }
  else if ((typeof document.forms['entryform'].elements['text'].selectionStart) != 'undefined') // für Mozilla
   {
    var tarea = document.forms['entryform'].elements['text'];
    var selEnd = tarea.selectionEnd;
    var txtLen = tarea.value.length;
    var txtbefore = tarea.value.substring(0,selEnd);
    var txtafter =  tarea.value.substring(selEnd, txtLen);
    var oldScrollTop = tarea.scrollTop;
    tarea.value = txtbefore + what + txtafter;
    tarea.selectionStart = txtbefore.length + what.length;
    tarea.selectionEnd = txtbefore.length + what.length;
    tarea.scrollTop = oldScrollTop;
    tarea.focus();
   }
  else
   {
    document.forms['entryform'].elements['text'].value += what;
    document.forms['entryform'].elements['text'].focus();
   }
 }

function insert_link(form,field,link_text,link_target)
 {
 if (document.selection) // for IE
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
  else if ((typeof document.forms[form].elements[field].selectionStart) != 'undefined') // for Mozilla
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

function clear()
 {
  document.forms['entryform'].elements['text'].value = "";
 }

function more_smilies()
 {
  var popurl="more_smilies.php";
  winpops=window.open(popurl,"","width=250,height=250,scrollbars,resizable");
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

img1 = new Image();
img1.src ="img/link_mo.gif";
img2 = new Image();
img2.src ="img/next_mo.gif";
img3 = new Image();
img3.src ="img/prev_mo.gif";
img4 = new Image();
img4.src ="img/update_mo.gif";