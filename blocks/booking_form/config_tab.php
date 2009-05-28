<?php
// Part of block_booking_form - A block that allows  booking to be made through Moodle
// Niall S F Barr, 2008.

$usehtmleditor = can_use_html_editor();

//echo "<h3>ID = {$this->config->eventid}</h3>";
//echo "<h3>Time = {$this->config->timemodified}</h3>";

if(!isset($this->config->limit)) {
    $this->config->limit = false;
}
if(!isset($this->config->title)) {
    $this->config->title = "";
}
if(!isset($this->config->eventtitle)) {
    $this->config->eventtitle = "";
}
if(!isset($this->config->maxbookings)) {
    $this->config->maxbookings = 0;
}


?>
<table cellpadding="9" cellspacing="0">
<tr valign="top">
<td><?php print_string('customtitle', 'block_booking_form') ?></td>
<td> <input type="text" size="50" name="title" value="<?php echo $this->config->title ?>" /></td>
</tr><tr valign="top">
<td><?php print_string('eventtitle', 'block_booking_form') ?></td>
<td> <input type="text" size="50" name="eventtitle" value="<?php echo $this->config->eventtitle ?>" /></td>
</tr>

<tr valign="top">
    <td align="right"><?php print_string('eventdescription', 'block_booking_form'); ?>:</td>
    <td><?php print_textarea($usehtmleditor, 12, 50, 0, 0, 'description', isset($this->config->description)?$this->config->description:'') ?></td>
</tr>

<tr valign="top">
<td><?php print_string('limitedplaces', 'block_booking_form') ?></td>
<td> <input type="checkbox" name="limit" <?php if($this->config->limit==true) echo ' checked="1"'; ?>/>
<input type="text" name="maxbookings" value="<?php if($this->config->limit==true) echo $this->config->maxbookings ?>"/>
<?php print_string('places', 'block_booking_form') ?></td>
</tr>
<tr valign="top">
    <td align="right"><?php print_string('confirmation', 'block_booking_form'); ?>:</td>
    <td><?php print_textarea($usehtmleditor, 12, 50, 0, 0, 'confirmation', isset($this->config->confirmation)?$this->config->confirmation:'') ?></td>
</tr>

<tr>
    <td colspan="2" align="center">
    <input type="submit" value="<?php print_string('savechanges') ?>" /></td>
</tr>
</table>


<?php if ($usehtmleditor) {
          use_html_editor();
      }
?>
