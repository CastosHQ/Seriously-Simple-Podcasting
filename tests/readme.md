# Testing Gist

## Overview
The testing process includes the following steps:
1. Plugin delivery.
2. Running the tests.

Please check the `.gitlab-ci.yml` file for more information.

## Plugin delivery
Has 2 steps:
1. Building the plugin
2. Uploading plugin to automated.ssp-testing.xyz

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
`vendor/bin/codecept run acceptance`

### How to print variables:
`codecept_debug($myVar);`
`vendor/bin/codecept run --debug acceptance`

