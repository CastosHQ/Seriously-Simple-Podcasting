# Testing Gist

## Overview
The testing process includes 3 steps:
1. Plugin build.
2. Plugin delivery.
3. Running the tests.

Please check the `.gitlab-ci.yml` file for more information.


### Useful links
https://lorisleiva.com/laravel-deployment-using-gitlab-pipelines/
https://campfirecode.medium.com/debugging-gitlab-ci-pipelines-locally-e2699608f4df
https://docs.gitlab.com/ee/ci/examples/deployment/composer-npm-deploy.html

### GitLab runner (run the script locally)
To run and debug CI/CD locally, you can use GitLab runner.

#### Install

`curl -LJO "https://gitlab-runner-downloads.s3.amazonaws.com/latest/deb/gitlab-runner_amd64.deb"`

`dpkg -i gitlab-runner_amd64.deb`

#### Run

`gitlab-runner exec docker {{my_stage}}`


## Running the tests
For testing purposes, we use Codeception. It provides possibility to run any kinds of tests (Unit, Functional, Acceptance).

### Useful links
https://codeception.com/07-24-2013/testing-wordpress-plugins.html

### Run acceptance tests:
`vendor/bin/codecept run acceptance --steps`
`vendor/bin/codecept run acceptance settings-general.feature --steps`

### Enable .feature hinting in PHPStorm:
File -> Settings -> PHP -> Test Frameworks -> Add -> Behat Local
Specify Path to Behat executable: {plugin_path}/vendor/behat

### Debugging:
`codecept_debug($myVar);`
`vendor/bin/codecept run --debug acceptance --steps`

### Run unit tests:
`vendor/bin/codecept run wpunit`
`vendor/bin/codecept run --debug wpunit`


## BDD
### Links
https://codeception.com/docs/07-BDD

### Generate the code stubs by feature steps
`vendor/bin/codecept gherkin:snippets acceptance`
