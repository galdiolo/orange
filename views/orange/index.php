</div>
<?=form_open('/orange/login', ['data-validate' => 'true']) ?>
<div class="login-background">
	<div class="login-area">
			<h2>Welcome</h2>
			<p>Let's get started!</p>
			<div class="email">
				<i class="fa fa-envelope"></i> <input type="text" id="email" name="email" value="" placeholder="email">
			</div>
			<div class="password">
				<i class="fa fa-key"></i> <input type="password" id="password" name="password" value="" placeholder="password">
			</div>
			<button type="submit" id="submit-button" class="btn-login">Login</button>
		<div class="extra">
			<?php o::view_event($controller_path,'login') ?>
			<a href="/">Back to Home Page</a>
		</div>
	</div>
</div>
</form>
<div>
