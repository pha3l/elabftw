<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// inc/editXP.php
?>
<script src="js/tiny_mce/tiny_mce.js"></script>
<?php
// ID
if(isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])){
    $id = $_GET['id'];
} else {
    die("The id parameter in the URL isn't a valid item ID.");
}

// SQL for editXP
$sql = "SELECT * FROM experiments WHERE id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
$data = $req->fetch();

// Check id is owned by connected user
if ($data['userid'] != $_SESSION['userid']) {
    die("You are trying to edit an experiment which is not yours.");
}

// Check for lock
if ($data['locked'] == 1) {
    die("Item is locked. Can't edit.");
}

// BEGIN CONTENT
?>
<section class='item <?php echo $data['outcome'];?>'>
<a class='align_right' href='delete_item.php?id=<?php echo $id;?>&type=exp' onClick="return confirm('Delete this experiment ?');"><img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/trash.png' title='delete' alt='delete' /></a>
<!-- ADD TAG FORM -->
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/tags.gif' alt='' /> <h4>Tags</h4><span class='smallgray'> (click a tag to remove it)</span><br />
<div class='tags'>
<span id='tags_div'>
<?php
$sql = "SELECT id, tag FROM experiments_tags WHERE item_id = ".$id;
$tagreq = $bdd->prepare($sql);
$tagreq->execute();
// DISPLAY TAGS
while($tags = $tagreq->fetch()){
echo "<span class='tag'><a onclick='delete_tag(".$tags['id'].",".$id.")'>";
echo stripslashes($tags['tag']);?>
</a></span>
<?php } //end while tags ?>
</span>
<input type="text" name="tag" id="addtaginput" placeholder="Add a tag" />
</div>
<!-- END ADD TAG -->
<!-- BEGIN EDITXP FORM -->
<form id="editXP" name="editXP" method="post" action="editXP-exec.php" enctype='multipart/form-data'>
<input name='item_id' type='hidden' value='<?php echo $id;?>' />

<h4>Date</h4><span class='smallgray'> (date format : YYMMDD)</span><br />
<img src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/calendar.png' title='date' alt='Date :' /><input name='date' id='datepicker' size='6' type='text' value='<?php echo $data['date'];?>' />

<span class='align_right'>
<h4>Status</h4>
<!-- Status get selected by default -->
<?php
        if (isset($_SESSION['new_outcome'])){
            $status = $_SESSION['new_outcome'];
            unset($_SESSION['new_outcome']);
        } else {
            $status = $data['outcome'];
        }
?>
      <select name="outcome">
<option <?php echo ($status === "running") ? "selected" : "";?> value="running">Running</option>
<option <?php echo ($status === "success") ? "selected" : "";?> value="success">Success</option>
<option <?php echo ($status === "redo") ? "selected" : "";?> value="redo">Need to be redone</option>
<option <?php echo ($status === "fail") ? "selected" : ""; ?> value="fail">Fail</option>
</select>
</span>
<br />
<br />

<h4>Title</h4><br />
      <textarea id='title_txtarea' name='title' rows="1" cols="80"><?php if(empty($_SESSION['errors'])){
          echo stripslashes($data['title']);
      } else {
          echo stripslashes($_SESSION['new_title']);
      } ?></textarea>

<br />
<h4>Experiment</h4>
<br />
<textarea id='body_area' class='mceditable' name='body' rows="15" cols="80">
    <?php echo stripslashes($data['body']);?>
</textarea>


<?php
// FILE UPLOAD
require_once('inc/file_upload.php');
// DISPLAY FILES
require_once('inc/display_file.php');
?>

<!-- SUBMIT BUTTON -->
<div class='center' id='submitdiv'>
<p>SUBMIT</p>
<input type='image' src='themes/<?php echo $_SESSION['prefs']['theme'];?>/img/submit.png' name='Submit' value='Submit' onClick="this.form.submit();" />
</div>
</form><!-- end editXP form -->

<h4>Linked items</h4>
<div id='links_div'>
<?php
// DISPLAY LINKED ITEMS
$sql = "SELECT link_id, id FROM experiments_links WHERE item_id = ".$id;
$req = $bdd->prepare($sql);
$req->execute();
// Check there is at least one link to display
if ($req->rowcount() != 0) {
    echo "<ul>";
    while ($links = $req->fetch()) {
        // SQL to get title
        $linksql = "SELECT id, title FROM items WHERE id = :link_id";
        $linkreq = $bdd->prepare($linksql);
        $linkreq->execute(array(
            'link_id' => $links['link_id']
        ));
        $linkdata = $linkreq->fetch();
        echo "<li>- <a href='database.php?mode=view&id=".$linkdata['id']."'>".stripslashes($linkdata['title'])."</a>";
echo "<a onclick='delete_link(".$links['id'].", ".$id.")'>
<img src='themes/".$_SESSION['prefs']['theme']."/img/trash.png' title='delete' alt='delete' /></a></li>";
    } // end while
    echo "</ul>";
} else { // end if link exist
    echo "<br />";
}
?>
</div>
<p class='inline'>Add a link</p>
<input id='linkinput' size='60' type="text" name="link" placeholder="from the database" />

</section>

<script>
// JAVASCRIPT
<?php
// KEYBOARD SHORTCUTS
echo "key('".$_SESSION['prefs']['shortcuts']['create']."', function(){location.href = 'create_item.php?type=exp'});";
echo "key('".$_SESSION['prefs']['shortcuts']['submit']."', function(){document.forms['editXP'].submit()});";
?>
// TAGS AUTOCOMPLETE
$(function() {
		var availableTags = [
<?php // get all user's tag for autocomplete
$sql = "SELECT DISTINCT tag FROM experiments_tags WHERE userid = :userid ORDER BY id DESC LIMIT 500";
$getalltags = $bdd->prepare($sql);
$getalltags->execute(array(
    'userid' => $_SESSION['userid']
));
while ($tag = $getalltags->fetch()){
    echo "'".$tag[0]."',";
}?>
		];
		$( "#addtaginput" ).autocomplete({
			source: availableTags
		});
	});
// DELETE TAG JS
function delete_tag(tag_id, item_id) {
    var you_sure = confirm('Delete this tag ?');
    if (you_sure == true) {
        var jqxhr = $.post('delete_tag.php', {
            id: tag_id,
            item_id: item_id,
            type: 'exp'
        }).done(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=" + item_id + " #tags_div");
        })
    }
    return false;
}
// ADD TAG JS
// listen keypress, add tag when it's enter
jQuery('#addtaginput').keypress(function (e) {
    addTagOnEnter(e);
});

function addTagOnEnter(e) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get tag
        var tag = $('#addtaginput').attr('value');
        // POST request
        var jqxhr = $.post('add_tag.php', {
            tag: tag,
            item_id: <?php echo $id; ?> , type: 'exp'
        })
        // reload the tags list
        .done(function () {
            $("#tags_div").load("experiments.php?mode=edit&id=<?php echo $id;?> #tags_div");
            // clear input field
            $("#addtaginput").val("");
            return false;
        })
    } // end if key is enter
}
// LINKS AUTOCOMPLETE
$(function() {
		var availableLinks = [
<?php // get all user's links for autocomplete
$sql = "SELECT title, id FROM items";
$getalllinks = $bdd->prepare($sql);
$getalllinks->execute();
while ($link = $getalllinks->fetch()){
    // html_entity_decode is needed to convert the quotes
    echo "'".$link['id']." - ".html_entity_decode(substr($link[0], 0, 60), ENT_QUOTES)."',";
}?>
		];
		$( "#linkinput" ).autocomplete({
			source: availableLinks
		});
	});
// DELETE LINK JS
function delete_link(id, item_id) {
    var you_sure = confirm('Delete this link ?');
    if (you_sure == true) {
        var jqxhr = $.post('delete_link.php', {
            id: id,
            item_id : item_id
        }).done(function () {
            $("#links_div").load("experiments.php?mode=edit&id=" + item_id + " #links_div");
        })
    }
    return false;
}
// ADD LINK JS
// listen keypress, add link when it's enter
jQuery('#linkinput').keypress(function (e) {
    addLinkOnEnter(e);
});

function addLinkOnEnter(e) { // the argument here is the event (needed to detect which key is pressed)
    var keynum;
    if (e.which) {
        keynum = e.which;
    }
    if (keynum == 13) { // if the key that was pressed was Enter (ascii code 13)
        // get link
        var link_id = decodeURIComponent($('#linkinput').attr('value'));
        // parseint will get the id, and not the rest (if there is number in title)
        link_id = parseInt(link_id, 10);
        // POST request
        var jqxhr = $.post('add_link.php', {
            link_id: link_id,
            item_id: <?php echo $id; ?>
        })
        // reload the link list
        .done(function () {
            $("#links_div").load("experiments.php?mode=edit&id=<?php echo $id;?> #links_div");
            // clear input field
            $("#linkinput").val("");
            return false;
        })
    } // end if key is enter
}
// DATEPICKER
$( "#datepicker" ).datepicker({dateFormat: 'ymmdd'});
// SELECT ALL TXT WHEN FOCUS ON TITLE INPUT
$("#title").focus(function(){
    $("#title").select();
});
// EDITOR
tinyMCE.init({
    theme : "advanced",
    mode : "specific_textareas",
    editor_selector : "mceditable",
    content_css : "css/tinymce.css",
    theme_advanced_toolbar_location : "top",
    theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
    plugins : "table",
    theme_advanced_buttons3_add : "forecolor, backcolor, tablecontrols",
    font_size_style_values : "10px,12px,13px,14px,16px,18px,20px"
});

// AUTOSAVE EVERY 2 SECONDS only when window is on focus
// we need to wait for mcedit to load (and the user to make a modification)
function wait_a_bit() {
    // just wait for 2 secs
    setTimeout("startCheck()", 2000)
}
function startCheck() {
    // check every 2 secs if tab has focus
    setInterval("focusCheck()", 2000);
}
function focusCheck () {
    if (document.hasFocus())
        autoSave();
}
function autoSave() {
        $.ajax({
            type: "POST",
            url: "editXP-autosave.php",
            data: {
            id : <?php echo $id;?>,
            // we need this to get the updated content
            body : tinyMCE.activeEditor.getContent()
            }
        });
}

wait_a_bit();
// change title
$(document).ready(function() {
    document.title = "<?php echo $data['title']; ?>";
});
</script>
