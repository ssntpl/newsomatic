"use strict";
jQuery(document).ready(function(){
    jQuery('.newsomatic_select2').select2();
    jQuery("#query_string").gcomplete({
        style: "default",
        effect: false,
        pan: '#query_string'
    });
    var elementExists = document.getElementById("query_string_title");
    if(elementExists !== null)
    {
        jQuery("#query_string_title").gcomplete({
            style: "default",
            effect: false,
            pan: '#query_string_title'
        });
    }
});

var ajaxurl = mycustomsettings.ajaxurl;
jQuery(document).ready(function(){
    var last_valid_selection = null;
    jQuery('.max_5val').change(function(event) 
    {
        if (jQuery(this).val().length > 5) 
        {
            jQuery(this).val(last_valid_selection);
        } 
        else 
        {
            last_valid_selection = jQuery(this).val();
        }
    });
    jQuery('span.wpnewsomatic-delete').on('click', function(){
        var confirm_delete = confirm('Delete This Rule?');
        if (confirm_delete) {
            jQuery(this).parent().parent().remove();
            jQuery('#myForm').submit();						
        }
    });
});

function getPathTo(element) {
    if (element.id !== '') {
        if (element.id != 'newsomatic_container') {
            var res = element.id;
            res = res.replace('/\\/g', "");
            res = res.replace('/"/g', "");
            res = res.replace('/\'/g', "");
            return "//*[@id='" + res + "']";
        } else {
            return '//body/*';
        }
    }
    var res = element.className;
    if (res !== '' && res != 'highlight') {
        res = res.replace('highlight ', "");
        res = res.replace(' highlight ', " ");
        res = res.replace(' highlight', "");
        res = res.replace('/\\/g', "");
        res = res.replace('/"/g', "");
        res = res.replace('/\'/g', "");
        if (res !== '' && res != ' ') {
            res = jQuery.trim(res);
            if (res == '') {
                return newsomatic_get_tree_xpath(element);
            }
            return "//*[@class='" + res + "']";
        }
    }
    var itempropz = element.getAttribute("itemprop");
    if (itempropz !== '' && itempropz !== null) {
        return "//*[@itemprop='" + itempropz + "']";
    }
    if (element === document.body) {
        return '//body/*';
    }
    return getPathTo(element.parentNode);
}

function newsomatic_get_tree_xpath(element) {
    var paths = [];
    for (; element && element.nodeType == Node.ELEMENT_NODE; element = element.parentNode) {
        var index = 0;
        var moreSiblings = false;
        for (var sibling = element.previousSibling; sibling; sibling = sibling.previousSibling) {
            if (sibling.nodeType == Node.DOCUMENT_TYPE_NODE)
                continue;

            if (sibling.nodeName == element.nodeName)
                ++index;
        }

        for (var sibling = element.nextSibling; sibling && !moreSiblings; sibling = sibling.nextSibling) {
            if (sibling.nodeName == element.nodeName)
                moreSiblings = true;
        }

        var tagName = (element.prefix ? element.prefix + ":" : "") + element.localName;
        var pathIndex = (index || moreSiblings ? "[" + (index + 1) + "]" : "");
        if (element.id && !(element.id.match(/[0-9]+/))) {
            tagName = "/*";
            pathIndex = '[@id="' + element.id + '"]';
        };

        paths.splice(0, 0, tagName + pathIndex);

        if (element.id && !(element.id.match(/[0-9]+/))) {
            break;
        }

    }

    return paths.length ? "/" + paths.join("/") : null;
};
(function($) {

    $.newsomatic_iframe = function() {
        $('body').prepend('<div class="newsomatic_iframe__overlay"><div class="newsomatic_iframe__centerWrap"><div class="newsomatic_iframe__centerer"><div class="newsomatic_iframe__contentWrap" style="background: url(https://1.bp.blogspot.com/-vIHeaMvTAts/XOsDjqTD0jI/AAAAAAAAAx4/SRvufVxlRwYufBlZVmWUYng_dhW0rs2OwCLcBGAs/s1600/loading.gif) no-repeat center"><div class="newsomatic_iframe__scaleWrap" style="visibility: hidden;"><div class="newsomatic_iframe__closeBtn"><p>x</p></div>');
    };
    $('.newsomatic_selector').on('change', function(e) {
        var selvalue = $(this).val();
        if (selvalue != 'visual') {
            return;
        }
        e.preventDefault();
        var myCont = '';
        if (jQuery(this).attr('data-target-field-cont') != '') {
            myCont = jQuery(this).attr('data-target-field-cont');
            if (myCont === undefined) {
                myCont = '';
            }
        }
        var mySrc = '';
        if (jQuery(this).attr('data-source-field-id') != '') {
            mySrc = jQuery('*[id="' + jQuery(this).attr('data-source-field-id') + myCont + '"]').val();
			mySrc = mySrc.split("\n");
			mySrc = mySrc[0];
        }
        var myDest = '';
        if (jQuery(this).attr('data-target-field-id') != '') {
            myDest = jQuery(this).attr('data-target-field-id') + myCont;
        }
        if (myDest == '') {
            return;
        }
        if (mySrc === undefined) {
            alert('Please select a source from where you wish to import content.');
            return;
        }
        if (mySrc.indexOf('category-') != -1 || mySrc == 'any') {
            alert('This feature is not supported when source is set to Any or to a specific News Category.');
            return;
        }
        
        if(mySrc.indexOf('%%counter_') != -1)
        {
            var rexxx = /%%counter_(\d+)_(\d+)_(\d+)%%/;
            mySrc = mySrc.replace(rexxx, '$1');
        }
        
        var usephantom = jQuery('select#use_phantom' + myCont).val();

        var iframeUrl = ajaxurl + '?action=newsomatic_iframe&address=' + encodeURIComponent(mySrc);
        if (usephantom != '') {
            iframeUrl += '&usephantom=' + encodeURIComponent(usephantom);
        }
        $('.newsomatic_iframe__overlay .newsomatic_iframe__scaleWrap').append('<iframe id="cr_page_frame" src="' + iframeUrl + '">');

        $('.newsomatic_iframe__overlay').fadeIn(750);
        $("#cr_page_frame").on("load", function() {

            $('.newsomatic_iframe__scaleWrap').css('visibility', 'visible');
            var prev;
            var doc = document.getElementById("cr_page_frame").contentDocument;
            doc.body.onmouseover = handler;

            function handler(event) {

                if (event.target === doc.body ||
                    (prev && prev === event.target)) {
                    return;
                }
                if (prev instanceof SVGElement) {
                    prev.classList.remove('highlight'); 
                    prev = undefined;
                } else if (prev && prev.className) {
                    prev.className = prev.className.replace(/\bhighlight\b/, '');
                    prev = undefined;
                }
                if (event.target) {
                    prev = event.target;
                    if (prev instanceof SVGElement) {
                        prev.classList.add('highlight'); 
                    } else {
                        prev.className += " highlight";
                    }
                }
            }
            $("#cr_page_frame").contents().find("body *").on('click', function() {
                if (jQuery(this).hasClass('highlight')) {
                    var xpathval = '';
                    var element = $(this)[0];
                    if (element && element.id && !(element.id.match(/[0-9]+/)))
                        xpathval = "//*[@id='" + element.id + "']";
                    else
                        xpathval = getPathTo(element);
                    jQuery('#' + myDest).val(xpathval);
                    
                    $('.newsomatic_iframe__overlay').fadeOut(750, function() {
                        $(this).find('iframe').remove();
                        jQuery('.newsomatic_iframe__scaleWrap').css('visibility', 'hidden');
                    });

                    return false;

                }


            });
        })

        $('.newsomatic_iframe__overlay iframe').on('click', function(e) {
            e.stopPropagation();
        });

        $('.newsomatic_iframe__overlay').on('click', function(e) {
            e.preventDefault();
            $('.newsomatic_iframe__overlay').fadeOut(750, function() {
                $(this).find('iframe').remove();
                jQuery('.newsomatic_iframe__scaleWrap').css('visibility', 'hidden');
            });
        });
    });
}(jQuery));

jQuery.newsomatic_iframe();

var unsaved = false;
jQuery(document).ready(function () {
jQuery(":input").change(function(){
if(this.id != 'select-shortcode')
{
    var classes = this.className;
    var classes = this.className.split(' ');
    var found = jQuery.inArray('actions', classes) > -1;
    if(this.id != 'select-shortcode' && this.id != 'PreventChromeAutocomplete' && this.className != 'sc_chat_form_field_prompt_text' && !found)
        unsaved = true;
}
});
function unloadPage(){ 
if(unsaved){
    return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
}
}
window.onbeforeunload = unloadPage;
});

function deletePostsManual(number, type, typeId)
{
    if (confirm("Are you sure you want to delete all posts generated by this rule?") == true) {
        document.getElementById("run_img" + number).style.visibility = "visible";
        document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/running.gif";
        var data = {
            action: 'newsomatic_my_action',
            id: number,
            type: typeId,
            how: type
        };
        jQuery.post(ajaxurl, data, function(response) {
            if(response.trim() == 'ok')
            {
                document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/ok.gif";
            }
            else
            {
                if(response.trim() == 'nochange')
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/nochange.gif";
                }
                else
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
                }
            }
        }).fail( function(xhr) 
        {
            console.log('Error occured in processing: ' + xhr.statusText + ' - please check plugin\'s \'Activity and Logging\' menu for details.');
            document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
        });
    } else {
        return;
    }
}
function duplicatePostsManual(number, type, typeId)
{
    if (confirm("Are you sure you want to duplicate this rule?") == true) {
        document.getElementById("run_img" + number).style.visibility = "visible";
        document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/running.gif";
        var data = {
            action: 'newsomatic_my_action',
            id: number,
            type: typeId,
            how: type
        };
        jQuery.post(ajaxurl, data, function(response) {
            if(response.trim() == 'ok')
            {
                document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/ok.gif";
                location.reload();
            }
            else
            {
                if(response.trim() == 'nochange')
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/nochange.gif";
                }
                else
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
                }
            }
        }).fail( function(xhr) 
        {
            console.log('Error occured in processing: ' + xhr.statusText + ' - please check plugin\'s \'Activity and Logging\' menu for details.');
            document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
        });
    } else {
        return;
    }
}
function runNowManual(number, typeId)
{
    if (confirm("Are you sure you want to run this rule now?") == true) {
        document.getElementById("run_img" + number).style.visibility = "visible";
        document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/running.gif";
        var data = {
            action: 'newsomatic_run_my_action',
            id: number,
            type: typeId
        };
        jQuery.post(ajaxurl, data, function(response) {
            if(response.trim() == 'ok')
            {
                document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/ok.gif";
            }
            else
            {
                if(response.trim() == 'nochange')
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/nochange.gif";
                }
                else
                {
                    document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
                }
            }
        }).fail( function(xhr) 
        {
            console.log('Error occured in processing: ' + xhr.statusText + ' - please check plugin\'s \'Activity and Logging\' menu for details.');
            document.getElementById("run_img" + number).src= mycustomsettings.plugin_dir_url + "images/failed.gif";
        });
    } else {
        return;
    }
}


function actionsChangedManual(ruleId, selectedValue, typeId)
{
    if (selectedValue==='run')
    {
        if(unsaved){
            alert("You have unsaved changes on this page. Please save your changes before manually running rules!");
            return;
        }
        runNowManual(ruleId, typeId);
    }
    else
    {
        if (selectedValue==='duplicate')
        {
            duplicatePostsManual(ruleId, 'duplicate', typeId);
        }
        else
        {
            if (selectedValue==='trash')
            {
                deletePostsManual(ruleId, 'trash', typeId);
            }
            else
            {
                deletePostsManual(ruleId, 'delete', typeId);
            }
        }
    }
}

		jQuery(document).ready(function() {
			jQuery('.newsomatic_image_button').on('click', function(){
				tb_show('',"media-upload.php?type=image&TB_iframe=true");
                window.send_to_editor = function(html) {
                    var url = jQuery(html).attr('src');
                    jQuery('#cr_input_box').val(url);
                    tb_remove();
                };
			});
		});
function thisonChangeHandler(cb) {
if(cb.checked == true)
{
    jQuery("input.activateDeactivateClass:checkbox").each( function () {
        jQuery(this).prop('checked', true);
    });
}
else
{
    jQuery("input.activateDeactivateClass:checkbox").each( function () {
        jQuery(this).prop('checked', false);
    });
}
}
var codemodalfzr = document.getElementById('mymodalfzr');
var btn = document.getElementById("mybtnfzr");
var span = document.getElementById("newsomatic_close");
var ok = document.getElementById("newsomatic_ok");
if(btn != null)
{
    btn.onclick = function() {
        codemodalfzr.style.display = "block";
    }
}
if(span != null)
{
    span.onclick = function() {
        codemodalfzr.style.display = "none";
    }
}
if(ok != null)
{
    ok.onclick = function() {
        codemodalfzr.style.display = "none";
    }
}
window.onclick = function(event) {
    if (event.target == codemodalfzr) {
        codemodalfzr.style.display = "none";
    }
}

jQuery("#myForm").on('submit', function (e) {
    jQuery(this).on('submit', function() {
        return false;
    });

    e.preventDefault();
    var changedCheckboxes = [];

    var this_master = jQuery(this);
    jQuery('button[type=submit], input[type=submit]').prop('disabled',true);
    this_master.find('input[type="checkbox"]').each( function () {
        var checkbox_this = jQuery(this);

        if (checkbox_this.attr("id") !== "exclusion")
        {
            if( checkbox_this.is(":checked") == true ) {
                checkbox_this.attr('value','1');
            } else {
                checkbox_this.prop('checked',true);  
                checkbox_this.attr('value','0');
                changedCheckboxes.push(checkbox_this);
            }
        }
    });
    if (typeof mycustomsettings.max_input_vars !== 'undefined' && jQuery('input, textarea, select, button').length >= mycustomsettings.max_input_vars) {
        this_master.append("<span style='color:red;'>Saving settings, please wait...</span>");
        var coderevolution_max_input_var_data = this_master.serialize();
        this_master.find("table").remove();
        this_master.append("<input type='hidden' class='coderevolution_max_input_var_data' name='coderevolution_max_input_var_data'/>");
        this_master.find("input.coderevolution_max_input_var_data").val(coderevolution_max_input_var_data);
    }
    setTimeout(() => {
        this.submit();
        changedCheckboxes.forEach(function(item) {
            item.prop('checked', false);
        });
    }, 10);
});

function createAdmin(i) {
    var modals = [];
    var btns = [];
    var spans = [];
    var oks = [];
    var btns = [];
    var myarr = [];
    modals = document.getElementById("mymodalfzr" + i);
    btns = document.getElementById("mybtnfzr" + i);
    spans = document.getElementById("newsomatic_close" + i);
    oks = document.getElementById("newsomatic_ok" + i);
    btns.onclick = function(e) {
        modals.style.display = "block";
    }
    spans.onclick = function(e) {
        modals.style.display = "none";
    }
    oks.onclick = function(e) {
        modals.style.display = "none";
    }
    modals.addEventListener("click", function(e) {
        if (e.target !== this)
            return;
        modals.style.display = "none";
    }, false);
    jQuery(document).ready(function(){
        jQuery("#query_string" + i).gcomplete({
            style: "default",
            effect: false,
            pan: '#query_string' + i
        });
        var elementExists = document.getElementById("query_string_title");
        if(elementExists !== null)
        {
            jQuery("#query_string_title" + i).gcomplete({
                style: "default",
                effect: false,
                pan: '#query_string_title' + i
            });
        }
    });
}