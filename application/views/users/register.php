        <div id="register" class="mainContent">
                <?php echo form_open('users/register'); ?>

<fieldset>
<?php if(isset($returnMessage)) echo $returnMessage; ?><br />
<?php echo validation_errors(); ?>

<label for='username'>Username</label>
<input type="text" name="username" value="<?php echo set_value('username'); ?>"  size='12'/> <br /> 

<label for='password0'>Password</label>
<input type="password" name="password0" value="" size="12" /> <br />

<label for='password1'>Password (confirm)</label>
<input type="password" name="password1" value="" size="12" /> <br />

<label for='usertype'>Role</label>
<select name='usertype' value='1'>
  <option value='1'>Buyer</option>
  <option value='2'>Seller</option>
</select><br />

<label for="captcha">Captcha</label> <input name="captcha" type="text" size='12'/><br />

<label for='image'>Image</label> <?=$captcha['image'];?>
<br />
<label for='submit'><input type="submit" value="Submit" /></label>
</fieldset>
</form>
</div>

