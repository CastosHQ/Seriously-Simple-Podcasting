<?php
/**
 * @var string $next_step
 * */
?>

<div class="ssp-onboarding ssp-onboarding__step-3">
	<?php include __DIR__ . '/steps-header.php'; ?>
	<div class="ssp-onboarding__settings">
		<div class="ssp-onboarding__settings-header">
			<h1>Podcast Category</h1>
		</div>
		<form class="ssp-onboarding__settings-body" action="<?php echo $next_step ?>" method="post">
			<div class="ssp-onboarding__settings-item">
				<h2>Primary Category</h2>
				<label for="data_category" class="description">
					What primary category should we publish your podcast in?
				</label>
				<div class="ssp-onboarding__select">
					<select name="data_category" id="data_category" class="js-parent-category" data-subcategory="data_subcategory"><option selected="selected" value="">-- None --</option><option value="Arts">Arts</option><option value="Business">Business</option><option value="Comedy">Comedy</option><option value="Education">Education</option><option value="Fiction">Fiction</option><option value="Government">Government</option><option value="History">History</option><option value="Health &amp; Fitness">Health &amp; Fitness</option><option value="Kids &amp; Family">Kids &amp; Family</option><option value="Leisure">Leisure</option><option value="Music">Music</option><option value="News">News</option><option value="Religion &amp; Spirituality">Religion &amp; Spirituality</option><option value="Science">Science</option><option value="Society &amp; Culture">Society &amp; Culture</option><option value="Sports">Sports</option><option value="Technology">Technology</option><option value="True Crime">True Crime</option><option value="TV &amp; Film">TV &amp; Film</option></select>
				</div>
			</div>

			<div class="ssp-onboarding__settings-item">
				<h2>Primary Sub-Category</h2>
				<label for="data_subcategory" class="description">
					Your podcast sub-category based on the primary category selected above.
				</label>
				<div class="ssp-onboarding__select">
					<select name="data_subcategory" id="data_subcategory" class=""><option selected="selected" value="">-- None --</option><optgroup label="Arts" style="display: none;"><option value="Books">Books</option><option value="Design">Design</option><option value="Fashion &amp; Beauty">Fashion &amp; Beauty</option><option value="Food">Food</option><option value="Performing Arts">Performing Arts</option><option value="Visual Arts">Visual Arts</option></optgroup><optgroup label="Business" style="display: none;"><option value="Careers">Careers</option><option value="Entrepreneurship">Entrepreneurship</option><option value="Investing">Investing</option><option value="Management">Management</option><option value="Marketing">Marketing</option><option value="Non-profit">Non-profit</option></optgroup><optgroup label="Comedy" style="display: none;"><option value="Comedy Interviews">Comedy Interviews</option><option value="Improv">Improv</option><option value="Standup">Standup</option></optgroup><optgroup label="Education" style="display: none;"><option value="Courses">Courses</option><option value="How to">How to</option><option value="Language Learning">Language Learning</option><option value="Self Improvement">Self Improvement</option></optgroup><optgroup label="Fiction" style="display: none;"><option value="Comedy Fiction">Comedy Fiction</option><option value="Drama">Drama</option><option value="Science Fiction">Science Fiction</option></optgroup><optgroup label="Health &amp; Fitness" style="display: none;"><option value="Alternative Health">Alternative Health</option><option value="Fitness">Fitness</option><option value="Medicine">Medicine</option><option value="Mental Health">Mental Health</option><option value="Nutrition">Nutrition</option><option value="Sexuality">Sexuality</option></optgroup><optgroup label="Kids &amp; Family" style="display: none;"><option value="Education for Kids">Education for Kids</option><option value="Parenting">Parenting</option><option value="Pets &amp; Animals">Pets &amp; Animals</option><option value="Stories for Kids">Stories for Kids</option></optgroup><optgroup label="Leisure" style="display: none;"><option value="Animation &amp; Manga">Animation &amp; Manga</option><option value="Automotive">Automotive</option><option value="Aviation">Aviation</option><option value="Crafts">Crafts</option><option value="Games">Games</option><option value="Hobbies">Hobbies</option><option value="Home &amp; Garden">Home &amp; Garden</option><option value="Video Games">Video Games</option></optgroup><optgroup label="Music" style="display: none;"><option value="Music Commentary">Music Commentary</option><option value="Music History">Music History</option><option value="Music Interviews">Music Interviews</option></optgroup><optgroup label="News" style="display: none;"><option value="Business News">Business News</option><option value="Daily News">Daily News</option><option value="Entertainment News">Entertainment News</option><option value="News Commentary">News Commentary</option><option value="Politics">Politics</option><option value="Sports News">Sports News </option><option value="Tech News">Tech News</option></optgroup><optgroup label="Religion &amp; Spirituality" style="display: none;"><option value="Buddhism">Buddhism</option><option value="Christianity">Christianity</option><option value="Hinduism">Hinduism</option><option value="Islam">Islam</option><option value="Judaism">Judaism</option><option value="Spirituality">Spirituality</option></optgroup><optgroup label="Science" style="display: none;"><option value="Astronomy">Astronomy</option><option value="Chemistry">Chemistry</option><option value="Earth Sciences">Earth Sciences</option><option value="Life Sciences">Life Sciences</option><option value="Mathematics">Mathematics</option><option value="Natural Sciences">Natural Sciences</option><option value="Nature">Nature</option><option value="Physics">Physics</option><option value="Social Sciences">Social Sciences</option></optgroup><optgroup label="Society &amp; Culture" style="display: none;"><option value="Documentary">Documentary</option><option value="Personal Journals">Personal Journals</option><option value="Philosophy">Philosophy</option><option value="Places &amp; Travel">Places &amp; Travel</option><option value="Relationships">Relationships</option></optgroup><optgroup label="Sports" style="display: none;"><option value="Baseball">Baseball</option><option value="Basketball">Basketball</option><option value="Cricket">Cricket</option><option value="Fantasy Sports">Fantasy Sports </option><option value="Football">Football</option><option value="Golf">Golf</option><option value="Hockey">Hockey</option><option value="Rugby">Rugby</option><option value="Running">Running</option><option value="Soccer">Soccer</option><option value="Swimming">Swimming</option><option value="Tennis">Tennis</option><option value="Volleyball">Volleyball</option><option value="Wilderness">Wilderness</option><option value="Wrestling">Wrestling</option></optgroup><optgroup label="TV &amp; Film" style="display: none;"><option value="After Shows">After Shows</option><option value="Film History">Film History</option><option value="Film Interviews">Film Interviews</option><option value="Film Reviews">Film Reviews</option><option value="TV Reviews">TV Reviews</option></optgroup></select>
				</div>
			</div>

			<div class="ssp-onboarding__submit">
				<a href="<?php echo $next_step ?>" class="button"><span>Skip</span></a>
				<button type="submit">Proceed</button>
			</div>
		</form>
	</div>
</div>
