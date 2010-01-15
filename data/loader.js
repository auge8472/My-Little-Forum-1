document.observe('dom:loaded',onloadList);

function onloadList() {

if ($('bb-code-buttons'))
	{
	auge_bbc_buttons(auge_buttons);

	Element.insert($('bb-code-buttons'), {'bottom': " <input value=\"Link\" title=\"Link einfügen: \[link=http://www.domain.tld/\]Link\[/link\] oder \[link\]http://www.domain.tld/\[/link\]\" class=\"bb-button\" type=\"button\" name=\"link2\" onclick=\"insert_link('entryform','text','Link-Text (optional)','Link-Ziel (URL):');\" />"});

	if (typeof(auge_upload) != "undefined")
		{
		Element.insert($('bb-code-buttons'), {'bottom': " <input value=\""+ auge_upload.get('text') +"\" title=\""+ auge_upload.get('title') +"\" class=\"bb-button\" type=\"button\" name=\"imgupload\" onclick=\"upload();\" />"});
		}
	}

if ($('delete-text') && typeof(delete_text) != "undefined")
	{
	Element.insert($('delete-text'), {'bottom': " - <span class=\"js-handler\" onclick=\"clearText('text'); return false;\">"+ delete_text +"</a>"});
	}

}