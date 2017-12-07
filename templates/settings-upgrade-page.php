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
		
		<h1>Welcome to Seriously Simple Podcasting by Castos</h1>
	</div>
	<div id="container2">
		<div id="container1">
			<div id="col1">
				
				<iframe width="560" height="315" src="https://www.youtube.com/embed/Se3H1IDAYtw?rel=0&amp;showinfo=0" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>
			</div>
			<div id="col2">
				
				<p>Castos podcast hosting combines best in class media hosting with the workflow you know and love in Seriously Simple Podcasting. </p>
				<ul>
					<li>Direct file upload from WordPress dashboard</li>
					<li>Playback duration level analytics on all episodes</li>
					<li>Seamless integration with Seriously Simple Podcasting plugin</li>
					<li>Lightning fast episode downloads and real-time streaming</li>
					<li>Safe, secure file storage on a robust hosting platform</li>
				</ul>
				<p>Every account comes with a Free 14 day Trial</p>
				<br>
				<div class="signup">
					<a class='button' href="https://www.castos.com/">Sign Up Today</a>
				</div>
			</div>
		</div>
	</div>
	
	
	<div id="footer">
		<p><a href="<?php echo $ssp_dismiss_url; ?>">Dismiss this message</a></p>
	</div>
</div>
