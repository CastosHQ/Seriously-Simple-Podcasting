<style media="screen" type="text/css">
	/* <!-- */
	
	#header h1, #footer h1,
	#header h2, #footer h2,
	#header p,  #footer p,  {
		margin-left: 2%;
		padding-right: 2%;
		text-align: center;
	}
	
	#active2 #tab2,
	#active3 #tab3,
	#active4 #tab4,
	#active5 #tab5 {
		font-weight: bold;
		text-decoration: none;
		color: #000;
	}
	
	/* Start of Column CSS */
	#container2 {
		clear: left;
		float: left;
		width: 100%;
		overflow: hidden;
	}
	
	#container1 {
		float: left;
		width: 100%;
		position: relative;
		right: 50%;
	}
	
	#col1 {
		float: left;
		width: 46%;
		position: relative;
		left: 52%;
		overflow: hidden;
	}
	
	#col2 {
		float: left;
		width: 46%;
		position: relative;
		left: 56%;
		overflow: hidden;
		vertical-align: center;
	}
	
	#header, #footer {
		padding: 10px;
	}
	
	.form {
		padding-top: 30px;
		margin: auto;
		width: 50%;
		clear: both;
	}
	
	.signup {
		text-align: center;
	}
	
	.button {
		background-color: #008CBA; /* Green */
		border: none;
		color: white;
		padding: 15px 32px;
		text-align: center;
		text-decoration: none;
		display: inline-block;
		font-size: 16px;
	}
	
	/* --> */
</style>
<div class="wrap">
	<div id="header">
		
		<h1>Welcome to the brand new Seriously Simple Podcasting</h1>
	</div>
	<div id="container2">
		<div id="container1">
			<div id="col1">
				
				<iframe width="560" height="315" src="https://www.youtube.com/embed/i_EOez6ZPpY?ecver=1" frameborder="0" allowfullscreen></iframe>
			
			</div>
			<div id="col2">
				
				<p>Tired of managing your podcast episodes on two separate platforms, or having your media files slow down your web server and site? We've integrated best in class media hosting with the workflow you know in Seriously Simple Hosting. </p>
				<ul>
					<li>Direct file upload from WordPress dashboard</li>
					<li>Seamless integration with Seriously Simple Podcasting plugin</li>
					<li>Lightning fast episode downloads</li>
					<li>Safe, secure file storage and a robust hosting platform</li>
				</ul>
				<p>Every account comes with a Free 14 day Trial</p>
				<br>
				<div class="signup">
					<a class='button' href="https://app.seriouslysimplepodcasting.com/register">Sign Up Today</a>
				</div>
			</div>
		</div>
	</div>
	
	<div class="form">
		<form action="https://www.getdrip.com/forms/56581223/submissions" method="post" data-drip-embedded-form="56581223">
			<h3 data-drip-attribute="headline">Check out what's Inside!</h3>
			<div data-drip-attribute="description">We've taken the simplicity and ease of use of Seriously Simple Podcasting and added a powerful, CDN backed media hosting platform to it. Seriously Simple Hosting gives you the workflow you love within WordPress but with industry leading media hosting built in.<br><br>
				Sign up to learn more about how Seriously Simple Hosting can help elevate your podcast to a whole new level.
			</div>
			<br>
			<div>
				<label for="fields[email]">Email Address</label><br/>
				<input type="email" name="fields[email]" value=""/>
			</div>
			<br>
			<div>
				<input type="submit" name="submit" value="Sign Up" data-drip-attribute="sign-up-button"/>
			</div>
		
		</form>
	</div>
	<div id="footer">
		<p><a href="<?php echo $ssp_dismiss_url; ?>">Dismiss this message</a></p>
	</div>
</div>