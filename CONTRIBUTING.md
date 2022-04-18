# How to contribute

Community made patches, localisations, bug reports and contributions are always welcome and are crucial to ensure Seriously Simple Podcasting remains the highest rated podcasting plugin in the WordPress plugin repository.

When contributing please ensure you follow the guidelines below so that we can keep on top of things.

__Note:__

GitLab is for *bug reports and contributions only* - if you have a support question don't post here. Use [the plugin's support forum](http://wordpress.org/support/plugin/seriously-simple-podcasting) instead for general support, but make sure to read through the [documentation](http://www.seriouslysimplepodcasting.com/documentation/) first.

## Getting Started

* Make sure you have a [GitLab account](https://gitlab.com/users/sign_up)
* Submit a ticket for your issue, assuming one does not already exist.
  * Clearly describe the issue including steps to reproduce when it is a bug.
  * Make sure you fill in the earliest version that you know has the issue.

## Making Changes

* [Fork](https://docs.gitlab.com/ee/user/project/repository/forking_workflow.html#creating-a-fork) the repository on GitLab.
* Clone the forked repository to your local development environment
* Run `npm install`, which will install any npm and composer dependencies, as well as build any block editor assets
* Define the `SCRIPT_DEBUG` constant in your wp-config.php to use the development versions of any JavaScript and CSS assets
* Make the changes to your forked repository.
  * **Ensure you stick to the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/) for all languages.**
* When committing, reference your issue (#1234) and include a note about the fix.
* Push the changes to your fork and submit a pull request on the *develop* branch of the Seriously Simple Podcasting repository.
* Please **don't** modify the changelog - this will be maintained by the Seriously Simple Podcasting developers.

At this point you're waiting on us to merge your pull request. We'll review all pull requests, and make suggestions and changes if necessary.

## Submitting Translations

Translations for Seriously Simple Podcasting are managed directly on WordPress.org. Please visit [the localisation management page](https://translate.wordpress.org/projects/wp-plugins/seriously-simple-podcasting) to submit translations. A WordPress.org account is required in order to do this.

# Additional Resources

* [General GitLab documentation](https://docs.gitlab.com/)
* [GitLab merge request documentation](https://docs.gitlab.com/ee/user/project/merge_requests/)
* [Seriously Simple Podcasting Docs](http://www.seriouslysimplepodcasting.com/)
* [Seriously Simple Podcasting Support](http://wordpress.org/support/plugin/seriously-simple-podcasting)

If you enjoy using Seriously Simple Podcasting and find value in the plugin then please [leave a review](https://wordpress.org/support/view/plugin-reviews/seriously-simple-podcasting?rate=5#postform) to help promote continued development.
