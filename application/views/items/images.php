        <div id="addListingImage" class="mainContent">
                <?php echo form_open_multipart('listings/imageUpload/'.$item['itemHash']); ?>

<fieldset>
<?php if(isset($returnMessage)) echo $returnMessage; ?><br />
<?php echo validation_errors(); ?>

<label for='username'>Item</label><?=$item['name'];?><br /><br />

<label for='file'>Image file</label>
<input type='file' name='userfile' size='12' />
<br /><br />

<label for=''>Main photo?</label>
<input type='checkbox' name='mainPhoto' value='1' /><br />
<br />
<label for='submit'><input type="submit" value="Submit" /></label>
</fieldset>
</form>


	<?php foreach ($images as $image): ?>
		<div class="productBox">
<img src='data:image/jpeg;base64,<?=$image['encoded'];?>' height='90' width='120'><br />
<?=anchor('listings/imageRemove/'.$image['imageHash'], "Delete");?> |
<?=anchor('listings/mainImage/'.$image['imageHash'],'Main Image');?>
		</div>
	<?php endforeach ?>
		<div class="clear"></div>
	</div>
