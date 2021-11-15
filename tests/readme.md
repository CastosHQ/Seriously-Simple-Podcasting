# Testing Gist

## Useful links

https://lorisleiva.com/laravel-deployment-using-gitlab-pipelines/
https://campfirecode.medium.com/debugging-gitlab-ci-pipelines-locally-e2699608f4df
https://docs.gitlab.com/ee/ci/examples/deployment/composer-npm-deploy.html

## GitLab runner (run the script locally)

### Install

`curl -LJO "https://gitlab-runner-downloads.s3.amazonaws.com/latest/deb/gitlab-runner_amd64.deb"`

`dpkg -i gitlab-runner_amd64.deb`

### Run

`gitlab-runner exec docker {{my_stage}}`
