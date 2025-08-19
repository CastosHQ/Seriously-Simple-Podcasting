<?php

use Codeception\TestCase\WPTestCase;

class Castos_Integration_Test extends WPTestCase {
	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Tests that the Players_Controller::html_player method returns the new html player code
	 *
	 * @covers Players_Controller::html_player
	 * @group player-controller-html-player
	 * @skip
	 */
	public function test_ssp_categories_match_castos_categories() {
		$this->markTestSkipped('Temporarily skipped while taxonomy parity is being reviewed.');
		$feed_handler      = $this->get_feed_handler();
		$castos_categories = $this->castos_categories();

		foreach ( $this->ssp_categories() as $category => $subcategories ) {
			foreach ( $subcategories as $subcategory ) {
				$castos_category_name = $feed_handler->get_castos_category_name( $category, $subcategory );

				$this->assertContains(
					$castos_category_name,
					$castos_categories,
					sprintf(
						'Missing Castos category mapping for "%s" (from "%s" -> "%s")',
						$castos_category_name,
						$category,
						$subcategory
					)
				);
			}
		}
	}


	/**
	 * @return \SeriouslySimplePodcasting\Handlers\Feed_Handler
	 * */
	protected function get_feed_handler() {
		return ssp_get_service( 'feed_handler' );
	}

	/**
	 * @return array
	 */
	protected function ssp_categories() {
		$subcategories  = ssp_config( 'settings/feed-subcategories' );
		$ssp_categories = array();

		foreach ( $subcategories as $subcategory ) {
			if ( ! empty( $subcategory['group'] ) && ! empty( $subcategory['label'] ) ) {
				$ssp_categories[ $subcategory['group'] ][] = $subcategory['label'];
			}
		}

		return $ssp_categories;
	}

	/**
	 * @return string[]
	 */
	protected function castos_categories() {
		return array(
			0   => 'Arts',
			66  => 'Arts: Books',
			1   => 'Arts: Design',
			2   => 'Arts: Fashion & Beauty',
			3   => 'Arts: Food',
			4   => 'Arts: Literature',
			5   => 'Arts: Performing Arts',
			6   => 'Arts: Visual Arts',
			7   => 'Business',
			8   => 'Business: Business News',
			9   => 'Business: Careers',
			67  => 'Business: Entrepreneurship',
			10  => 'Business: Investing',
			68  => 'Business: Management',
			11  => 'Business: Management & Marketing',
			69  => 'Business: Marketing',
			70  => 'Business: Non-profit',
			12  => 'Business: Shopping',
			13  => 'Comedy',
			71  => 'Comedy: Comedy Interviews',
			72  => 'Comedy: Improv',
			73  => 'Comedy: Standup',
			14  => 'Education',
			74  => 'Education: Courses',
			15  => 'Education: Educational Technology',
			16  => 'Education: Higher Education',
			75  => 'Education: How to',
			17  => 'Education: K-12',
			18  => 'Education: Language Courses',
			76  => 'Education: Language Learning',
			77  => 'Education: Self Improvement',
			19  => 'Education: Training',
			78  => 'Fiction',
			79  => 'Fiction: Comedy Fiction',
			80  => 'Fiction: Drama',
			81  => 'Fiction: Science Fiction',
			20  => 'Games & Hobbies',
			21  => 'Games & Hobbies: Automotive',
			22  => 'Games & Hobbies: Aviation',
			23  => 'Games & Hobbies: Hobbies',
			24  => 'Games & Hobbies: Other Games',
			25  => 'Games & Hobbies: Video Games',
			82  => 'Government',
			26  => 'Government & Organizations',
			27  => 'Government & Organizations: Local',
			28  => 'Government & Organizations: National',
			29  => 'Government & Organizations: Non-Profit',
			30  => 'Government & Organizations: Regional',
			31  => 'Health',
			84  => 'Health & Fitness',
			32  => 'Health & Fitness: Alternative Health',
			85  => 'Health & Fitness: Fitness',
			86  => 'Health & Fitness: Medicine',
			87  => 'Health & Fitness: Mental Health',
			88  => 'Health & Fitness: Nutrition',
			35  => 'Health & Fitness: Sexuality',
			33  => 'Health: Fitness & Nutrition',
			36  => 'Health: Kids & Family',
			34  => 'Health: Self-Help',
			83  => 'History',
			89  => 'Kids & Family',
			90  => 'Kids & Family: Education for Kids',
			91  => 'Kids & Family: Parenting',
			92  => 'Kids & Family: Pets & Animals',
			93  => 'Kids & Family: Stories for Kids',
			94  => 'Leisure',
			95  => 'Leisure: Animation & Manga',
			96  => 'Leisure: Automotive',
			97  => 'Leisure: Aviation',
			98  => 'Leisure: Crafts',
			99  => 'Leisure: Games',
			100 => 'Leisure: Hobbies',
			101 => 'Leisure: Home & Garden',
			102 => 'Leisure: Video Games',
			37  => 'Music',
			103 => 'Music: Music Commentary',
			104 => 'Music: Music History',
			105 => 'Music: Music Interviews',
			106 => 'News',
			38  => 'News & Politics',
			107 => 'News: Business News',
			108 => 'News: Daily News',
			109 => 'News: Entertainment News',
			110 => 'News: News Commentary',
			111 => 'News: Politics',
			112 => 'News: Sports News',
			113 => 'News: Tech News',
			59  => 'Religion & Spirituality',
			60  => 'Religion & Spirituality: Buddhism',
			61  => 'Religion & Spirituality: Christianity',
			62  => 'Religion & Spirituality: Hinduism',
			63  => 'Religion & Spirituality: Islam',
			64  => 'Religion & Spirituality: Judaism',
			65  => 'Religion & Spirituality: Other Spirituality',
			114 => 'Religion & Spirituality: Religion',
			115 => 'Religion & Spirituality: Spirituality',
			116 => 'Science',
			39  => 'Science & Medicine',
			40  => 'Science & Medicine: Medicine',
			41  => 'Science & Medicine: Natural Sciences',
			42  => 'Science & Medicine: Social Sciences',
			117 => 'Science: Astronomy',
			118 => 'Science: Chemistry',
			119 => 'Science: Earth Sciences',
			120 => 'Science: Life Sciences',
			121 => 'Science: Mathematics',
			122 => 'Science: Natural Sciences',
			123 => 'Science: Nature',
			124 => 'Science: Physics',
			125 => 'Science: Social Sciences',
			43  => 'Society & Culture',
			126 => 'Society & Culture: Documentary',
			44  => 'Society & Culture: History',
			45  => 'Society & Culture: Personal Journals',
			46  => 'Society & Culture: Philosophy',
			47  => 'Society & Culture: Places & Travel',
			127 => 'Society & Culture: Relationships',
			128 => 'Sports',
			48  => 'Sports & Recreation',
			49  => 'Sports & Recreation: Amateur',
			50  => 'Sports & Recreation: College & High School',
			51  => 'Sports & Recreation: Outdoor',
			52  => 'Sports & Recreation: Professional',
			129 => 'Sports: Baseball',
			130 => 'Sports: Basketball',
			131 => 'Sports: Cricket',
			132 => 'Sports: Fantasy Sports',
			133 => 'Sports: Football',
			134 => 'Sports: Golf',
			135 => 'Sports: Hockey',
			136 => 'Sports: Rugby',
			137 => 'Sports: Running',
			138 => 'Sports: Soccer',
			139 => 'Sports: Swimming',
			140 => 'Sports: Tennis',
			141 => 'Sports: Volleyball',
			142 => 'Sports: Wilderness',
			143 => 'Sports: Wrestling',
			53  => 'TV & Film',
			145 => 'TV & Film: After Shows',
			146 => 'TV & Film: Film History',
			147 => 'TV & Film: Film Interviews',
			148 => 'TV & Film: Film Reviews',
			149 => 'TV & Film: TV Reviews',
			54  => 'Technology',
			55  => 'Technology: Gadgets',
			56  => 'Technology: Podcasting',
			57  => 'Technology: Software How-To',
			58  => 'Technology: Tech News',
			144 => 'True Crime',
		);
	}
}
